<?php
/**
 * ownCloud - Aufgaben
 *
 * @author Bart Visscher
 * @copyright 2011 Bart Visscher bartv@thisnet.nl
 * 
 * @author Sebastian Doell
 * @copyright 2015 sebastian doell sebastian@libasys.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * This class manages our tasks
 */
namespace OCA\Aufgaben;
use Sabre\VObject\Recur\EventIterator;

App::$l10n = \OC::$server->getL10N('aufgaben');
class App{
	
	const CALENDAR = 'calendar';
	const TODO = 'todo';
		
	public static $l10n;

	public static function getPriorityOptions()
	{
		return array(
			''  => self::$l10n->t('Unspecified'),
			'1' => self::$l10n->t('1=highest'),
			'2' => '2',
			'3' => '3',
			'4' => '4',
			'5' => self::$l10n->t('5=medium'),
			'6' => '6',
			'7' => '7',
			'8' => '8',
			'9' => self::$l10n->t('9=lowest'),
		);
	}
	
	/**
	 * @brief Returns all  shared events where user is owner
	 * 
	 * @return array
	 */
	 
    public static function getTodoSharees(){
   	
		$SQL='SELECT item_source, item_type FROM `*PREFIX*share` WHERE `uid_owner` = ? AND `item_type` = ?';
		$stmt = \OCP\DB::prepare($SQL);
		$result = $stmt->execute(array(\OCP\User::getUser(),'todo'));
		$aSharees = '';
		while( $row = $result->fetchRow()) {
			$itemSource =\OCA\Calendar\App::validateItemSource($row['item_source'],(string)$row['item_type'].'-');	
			$aSharees[$itemSource]=1;	
			
		}
		
		if(is_array($aSharees)) return $aSharees;
		else return false;
    }
	public static function allInPeriodCalendar($aCalendar,$MODE='today',$sDay='') {
			//today,tomorrow,actweek,withoutdate,missedtasks
			$sharedwithByTodo = self::getTodoSharees();
			
			$cDataTimeLine=new \OCA\Aufgaben\Timeline();
			$cDataTimeLine->setTimeLineMode($MODE);
			if($MODE=='dayselect'){
				$cDataTimeLine->setTimeLineDay($sDay);
			}
			$cDataTimeLine->setCalendars($aCalendar);
			
			$aSQL=$cDataTimeLine->getTimeLineDB();
		
		  $startDate=$cDataTimeLine->getStartDate();
		  $endDate=$cDataTimeLine->getEndDate();
		   
		\OCP\Util::writeLog('aufgaben','sql->where: '.$aSQL['wheresql'], \OCP\Util::DEBUG);
			
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `*PREFIX*clndr_objects` WHERE  `objecttype`= ? '.$aSQL['wheresql'].' ORDER BY relatedto ASC, id ASC');
		$result = $stmt->execute($aSQL['execsql']);

		$calendarobjects = array();
		while( $row = $result->fetchRow()) {
			//$row['permissions'] = \OCP\PERMISSION_ALL;
				
			$row['shared']=0;
			if(is_array($sharedwithByTodo) && isset($sharedwithByTodo[$row['id']])){
				 $row['shared']=1;
				//\OCP\Util::writeLog('calendar','Events Shared Found: ->'.$row['id'], \OCP\Util::DEBUG);
			}
			if($row['repeating'] == 1){
			//	\OCP\Util::writeLog('aufgaben','Start lala:'. $startDate, \OCP\Util::DEBUG);
				
				$start = new \DateTime($row['startdate']);
				$end = new \DateTime($row['enddate']);
				
				$startDB = new \DateTime($startDate);
				$endDB = new \DateTime($endDate);
				
				$object = \OCA\Calendar\VObject::parse($row['calendardata']);
				$vtodo = $object->VTODO;
				$rrule = explode(';', $vtodo -> getAsString('RRULE'));
				foreach ($rrule as $rule) {
					list($attr, $val) = explode('=', $rule);
					if($attr=='FREQ'){
						if($val=='WEEKLY'){
							$endTS=$end->format('U');
						
							//Get day of Week
							$dayOfWeekrRule=date('w',$start->format('U'));
							$dayOfWeek=date('w',$startDB->format('U'));
							$startDBU=$startDB->format('U');
							if($MODE!='actweek'){
								if($dayOfWeekrRule == $dayOfWeek && $startDBU < $endTS){
									$calendarobjects[] = $row;
								}
							}else{
								$endDBU=$endDB->format('U');	
								if( $endTS > $endDBU){
									$calendarobjects[] = $row;
									//\OCP\Util::writeLog('calendar','Events Shared Found: ->'.$MODE, \OCP\Util::DEBUG);
								}
							}
						}
						if($val=='DAILY'){
							 $dayOfWeek=date('w',$startDB->format('U'));
							 $endTS=$end->format('U');
							 $startDBU=$startDB->format('U');
							 if($MODE!='actweek'){
								if($dayOfWeek!=0 && $dayOfWeek!=6 && $startDBU < $endTS){
									$calendarobjects[] = $row;
								}
							 }else{
							 	for($i=1;$i<6;$i++){
							 		$row['day']=$i;	
							 		$calendarobjects[] = $row;
							 	}
							 }
						}
					}
				}
						
				
			}else{
				$calendarobjects[] = $row;
			}
		}
       
	   $tasksFiltered=array();
	    foreach( $calendarobjects as $taskHaupt ) {
	 	 	$bsub=false;
			
		 	 foreach( $calendarobjects as $taskSub ) {
		 		if($taskHaupt['eventuid']!='' && $taskHaupt['eventuid']==$taskSub['relatedto']){
		 			$bsub=true;
					
		 		}
		 	}
		
		$taskHaupt['subtask']=$bsub;
		$tasksFiltered[]=$taskHaupt;
	 }
		
		return $tasksFiltered;
	}

	/**
	 * @brief edits an object
	 * @param integer $id id of object
	 * @param string $data  object
	 * @return boolean
	 */
	public static function edit($id, $data,$orgid=0) {
		$oldobject = \OCA\Calendar\Object::find($id);
		$calid = \OCA\Calendar\Object::getCalendarid($id);
		
		
		$calendar = \OCA\Calendar\Calendar::find($calid);
		$oldvobject = \OCA\Calendar\VObject::parse($oldobject['calendardata']);
		
		if ($calendar['userid'] != \OCP\User::getUser()) {
				
			$shareMode=\OCA\Calendar\Object::checkShareMode($calid);
			if($shareMode){
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource('calendar','calendar-'. $calid); //calid, not objectid !!!! 1111 one one one eleven
			}else{
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource('todo','todo-'. $id); 
			}
			
			$sharedAccessClassPermissions = \OCA\Calendar\Object::getAccessClassPermissions($oldvobject);
			if (!$sharedCalendar || !($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE) || !($sharedAccessClassPermissions & \OCP\PERMISSION_UPDATE)) {
				throw new \Exception(
					\OCA\Calendar\App::$l10n->t(
						'You do not have the permissions to edit this todo. Fehler'.$sharedCalendar.$id
					)
				);
			}
		} 
		
		$object = \OCA\Calendar\VObject::parse($data);
		\OCA\Calendar\App::loadCategoriesFromVCalendar($id, $object);
		list($type,$startdate,$enddate,$summary,$repeating,$uid,$isAlarm,$relatedTo) = \OCA\Calendar\Object::extractData($object);
      
        //check Share
        if($orgid>0){
        	$stmtShareUpdate = \OCP\DB::prepare( "UPDATE `*PREFIX*share` SET `item_target`= ? WHERE `item_source` = ? AND `item_type` = ? ");
		    $stmtShareUpdate->execute(array($summary,'todo-'.$orgid,'todo'));
			
			$stmt = \OCP\DB::prepare( 'UPDATE `*PREFIX*clndr_objects` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ? ,`isalarm`= ? ,`eventuid`= ?,`relatedto`= ?   WHERE `id` = ?' );
		    $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$isAlarm,$uid,$relatedTo,$orgid));
			
        }
        $stmtShare = \OCP\DB::prepare("SELECT COUNT(*) AS COUNTSHARE FROM `*PREFIX*share` WHERE `item_source` = ? AND `item_type`= ? ");
        $result=$stmtShare->execute(array('todo-'.$id,'todo'));
		$row = $result->fetchRow();
		
        if($row['COUNTSHARE']>=1){
        		$stmtShareUpdate = \OCP\DB::prepare( "UPDATE `*PREFIX*share` SET `item_target`= ? WHERE `item_source` = ? AND `item_type` = ? ");
		        $stmtShareUpdate->execute(array($summary,'todo-'.$id,'todo'));
				
				$stmt = \OCP\DB::prepare( 'UPDATE `*PREFIX*clndr_objects` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ?,`isalarm`= ? WHERE `org_objid` = ?' );
		        $stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$isAlarm,$id));
				
        }
		$stmt = \OCP\DB::prepare( 'UPDATE `*PREFIX*clndr_objects` SET `objecttype`=?,`startdate`=?,`enddate`=?,`repeating`=?,`summary`=?,`calendardata`=?,`lastmodified`= ? ,`isalarm`= ?,`eventuid`= ?,`relatedto`= ?  WHERE `id` = ?' );
		$stmt->execute(array($type,$startdate,$enddate,$repeating,$summary,$data,time(),$isAlarm,$uid,$relatedTo,$id));

		\OCA\Calendar\Calendar::touchCalendar($oldobject['calendarid']);
		\OCP\Util::emitHook('OC_Calendar', 'editTodo', $id);
		
		/****Activity New ***/
		$link =\OC::$server->getURLGenerator()->linkToRoute('aufgaben.page.index').'#'.urlencode($id);
		
		$params=array(
		    'mode'=>'edited',
		    'link' =>$link,
		    'trans_type' =>App::$l10n->t($type),
		    'summary' => $summary,
		    'cal_user'=>$calendar['userid'],
		    'cal_displayname'=>$calendar['displayname'],
		    );
			
		\OCA\Calendar\ActivityData::logEventActivity($params);
		
		/**END***/

		return true;
	}

	public static function all($aCalendar) {
			
		//\OCP\Util::writeLog('calendar','AlarmDB ID :'.$aCalendar ,\OCP\Util::DEBUG);	
		
		$sharedwithByTodo = self::getTodoSharees();	
		$addWhereSql='';
		
		$aExec=array('VTODO');
		
		foreach($aCalendar as $calInfo){
			if($addWhereSql=='') {
				$addWhereSql="`calendarid` = ? ";
				array_push($aExec,$calInfo['id']);
			}else{
				$addWhereSql.="OR `calendarid` = ? ";
				array_push($aExec,$calInfo['id']);
			}
			
		}
		//$addWhereSql.=' AND ( '.$addWhereSql.' )';
		
		$stmt = \OCP\DB::prepare( 'SELECT * FROM `*PREFIX*clndr_objects` WHERE `objecttype`= ? AND ( '.$addWhereSql.' ) ORDER BY relatedto ASC, id ASC' );
		$result = $stmt->execute($aExec);

		$calendarobjects = array();
		while( $row = $result->fetchRow()) {
				
			$row['shared']=0;
			if(is_array($sharedwithByTodo) && isset($sharedwithByTodo[$row['id']])){
				 $row['shared']=1;
			}	
			
			$calendarobjects[] = $row;
		}

		$tasksFiltered=array();
	    foreach( $calendarobjects as $taskHaupt ) {
	 	 	$bsub=false;
		 	 foreach( $calendarobjects as $taskSub ) {
		 		if($taskHaupt['eventuid']!='' && $taskHaupt['eventuid']==$taskSub['relatedto']){
		 			$bsub=true;
		 		}
		 	}
		
		$taskHaupt['subtask']=$bsub;	 
		$tasksFiltered[]=$taskHaupt;
	 }
		return $tasksFiltered;
	}
	
   public static function getPriorityOptionsFilterd(){
		return array(
			''  => self::$l10n->t('Unspecified'),
			'1' => self::$l10n->t('1=highest'),
			'5' => self::$l10n->t('5=medium'),
			'9' => self::$l10n->t('9=lowest'),
		);
	}
   
	public static function arrayForJSON($id, $vtodo, $user_timezone,$aCalendar,$aTask)	{
   
   	$output=array();
		
		if(\OCA\Calendar\Object::getowner($id) !== \OCP\USER::getUser()) {
			// do not show events with private or unknown access class
			 // \OCP\Util::writeLog('calendar','Sharee ID: ->'.$event['calendarid'].':'.$event['summary'], \OCP\Util::DEBUG);
			 $aTask['summary'] = strtr($vtodo->getAsString('SUMMARY'), array('\,' => ',', '\;' => ';'));
			if (isset($vtodo->CLASS) && ($vtodo->CLASS->getValue() === 'PRIVATE' || $vtodo->CLASS->getValue() === ''))
			{
				return $output;
			}
			 if (isset($vtodo->CLASS) && ($vtodo->CLASS->getValue() === 'CONFIDENTIAL')){
			
			    $aTask['summary'] = (string) App::$l10n->t('Busy');
			} 
			$vtodo = \OCA\Calendar\Object::cleanByAccessClass($id, $vtodo);
		}
		
		 $aTask['id'] = $id;
		 $aTask['privat']=false;
		
		if (isset($vtodo->CLASS) && ($vtodo->CLASS->getValue() === 'PRIVATE')){
			$aTask['privat']='private';
		}
		if (isset($vtodo->CLASS) && ($vtodo->CLASS->getValue() === 'CONFIDENTIAL')){
			$aTask['privat']='confidential';
		}
				
		$aTask['bgcolor']=$aCalendar['calendarcolor'];
		$aTask['displayname']=$aCalendar['displayname'];
		$aTask['color']=\OCA\Calendar\Calendar::generateTextColor($aCalendar['calendarcolor']);
		$aTask['permissions']=$aCalendar['permissions'];
		
		$aTask['rightsoutput']=\OCA\Calendar\Calendar::permissionReader($aCalendar['permissions']);
		if($aTask['rightsoutput'] === 'full access') $aTask['rightsoutput'] = '';
		
		
		
		if(array_key_exists('calendarowner', $aCalendar) && $aCalendar['calendarowner']!=''){
			 $aTask['summary'].=' (by '.$aCalendar['calendarowner'].')';
		}
		$aTask['description'] = strtr($vtodo->getAsString('DESCRIPTION'), array('\,' => ',', '\;' => ';'));
		$aTask['location'] = strtr($vtodo->getAsString('LOCATION'), array('\,' => ',', '\;' => ';'));
		$aTask['categories'] = $vtodo->getAsArray('CATEGORIES');
		$aTask['isOnlySharedTodo']=false;
		
		if(array_key_exists('isOnlySharedTodo', $aCalendar) && $aCalendar['isOnlySharedTodo']!=''){
			 $aTask['isOnlySharedTodo']=true;
		}
		
	    $aTask['orgevent']=false;
	    if(array_key_exists('org_objid', $aTask) && $aTask['org_objid']>0){
         	  $aTask['orgevent']=true;
			  $aTask['permissions']=self::getPermissions($aTask['org_objid'],self::TODO);
			 
			   $aTask['summary'].=' ('.self::$l10n->t('by').' '.\OCA\Calendar\Object::getowner($aTask['org_objid']).')';
			  
			//\OCP\Util::writeLog('aufgaben','shared Event Perm: '.$aTask['permissions'], \OCP\Util::DEBUG);
         }
       
		 if(isset($aTask['isalarm'])) $aTask['isalarm']=$aTask['isalarm'];
         if(isset($aTask['shared'])) $aTask['shared']=$aTask['shared'];
		 
		 $aTask['sAlarm']='';
		 if($vtodo->VALARM){
			   $counter=0;
			   $valarm='';
				foreach ($vtodo ->getComponents() as $param) {
					if($param->name == 'VALARM'){
						$attr = $param->children();
						foreach($attr as $attrInfo){
							$valarm[$counter][$attrInfo->name]= (string) $attrInfo->getValue();
						}
						$counter++;
					}
				}
				
				foreach($valarm as $vInfo){
	                if($vInfo['ACTION']=='DISPLAY' && strstr($vInfo['TRIGGER'],'P')){
	                    if(substr_count($vInfo['TRIGGER'],'PT') == 1){
	                    	 $aTask['sAlarm'][]='TRIGGER:'.$vInfo['TRIGGER'];
	                    }
						
					    if(substr_count($vInfo['TRIGGER'],'-P') == 1 && substr_count($vInfo['TRIGGER'],'PT') == 0){
	                    	 $temp=explode('-P',(string)$vInfo['TRIGGER']);
							 $aTask['sAlarm'][]='TRIGGER:-PT'.$temp[1];
	                    }
						if(substr_count($vInfo['TRIGGER'],'+P') == 1 && substr_count($vInfo['TRIGGER'],'PT') == 0){
	                    	$temp=explode('+P',$vInfo['TRIGGER']);
							 $aTask['sAlarm'][]='TRIGGER:+PT'.$temp[1];
	                    }
					   
	                }
	                if($vInfo['ACTION']=='DISPLAY' && !strstr($vInfo['TRIGGER'],'P')){
	                    if(!strstr($vInfo['TRIGGER'],'DATE-TIME'))  {
	                        $aTask['sAlarm'][]='TRIGGER;VALUE=DATE-TIME:'.$vInfo['TRIGGER'];
	                    }else{
	                        $aTask['sAlarm'][]=$vInfo['TRIGGER'];
	                    }
	                    
	                }
	            }
				
		 }
		 $today = strtotime(date('d.m.Y'));
		 	
		 	if ( $vtodo->DTSTART) {
			
				 $dateStartType=$vtodo->DTSTART->getValueType();
			     
			     
			     $timestamp = strtotime($vtodo->DTSTART -> getDateTime() -> format('d.m.Y'));
				
			    
				 if($dateStartType=='DATE'){
				 	$aTask['startdate']=$vtodo->DTSTART -> getDateTime() -> format('d.m.Y');
					 if($timestamp==$today){
				 		 $aTask['startdate']=self::$l10n->t('today');
				 	}
					if($timestamp==($today-(3600*24))){
				 		 $aTask['startdate']=self::$l10n->t('yesterday');
				 	}
                    if($timestamp==($today+(3600*24))){
				 		 $aTask['startdate']=self::$l10n->t('tomorrow');
				 	}
				 }
				 if($dateStartType=='DATE-TIME'){
				 	$aTask['startdate']=$vtodo->DTSTART -> getDateTime() -> format('d.m.Y H:i');
					 if($timestamp==$today){
				 	 	$aTask['startdate']=self::$l10n->t('today').' '.$vtodo->DTSTART -> getDateTime() -> format('H:i');
				 	}
				 	if($timestamp==($today-(3600*24))){
				 		 $aTask['startdate']=self::$l10n->t('yesterday').' '.$vtodo->DTSTART -> getDateTime() -> format('H:i');
				 	}
					if($timestamp==($today+(3600*24))){
				 		 $aTask['startdate']=self::$l10n->t('tomorrow').' '.$vtodo->DTSTART -> getDateTime() -> format('H:i');
				 	}
				 }
            
		}
		else {
			$aTask['startdate'] =false;
		}
		
		if ( $vtodo->DUE) {
			
				 $dateDueType=$vtodo->DUE->getValueType();
			     
			     
			     $timestamp = strtotime($vtodo->DUE -> getDateTime() -> format('d.m.Y'));
				
			    
				 if($dateDueType=='DATE'){
				 	$aTask['due']=$vtodo->DUE -> getDateTime() -> format('d.m.Y');
					 if($timestamp==$today){
				 		 $aTask['due']=self::$l10n->t('today');
				 	}
					if($timestamp==($today-(3600*24))){
				 		 $aTask['due']=self::$l10n->t('yesterday');
				 	}
                    if($timestamp==($today+(3600*24))){
				 		 $aTask['due']=self::$l10n->t('tomorrow');
				 	}
				 }
				 if($dateDueType=='DATE-TIME'){
				 	$aTask['due']=$vtodo->DUE -> getDateTime() -> format('d.m.Y H:i');
					 if($timestamp==$today){
				 	 	$aTask['due']=self::$l10n->t('today').' '.$vtodo->DUE -> getDateTime() -> format('H:i');
				 	}
				 	if($timestamp==($today-(3600*24))){
				 		 $aTask['due']=self::$l10n->t('yesterday').' '.$vtodo->DUE -> getDateTime() -> format('H:i');
				 	}
					if($timestamp==($today+(3600*24))){
				 		 $aTask['due']=self::$l10n->t('tomorrow').' '.$vtodo->DUE -> getDateTime() -> format('H:i');
				 	}
				 }
                 
				   
			if($vtodo -> getAsString('RRULE')!=''){
				$rrule = explode(';', $vtodo -> getAsString('RRULE'));
				$aRrule=array();
				foreach ($rrule as $rule) {
					list($attr, $val) = explode('=', $rule);
					if($attr=='UNTIL'){
						$aRrule['enddate']=$val;
					}
					if($attr=='FREQ'){
						$aRrule['freq']=$val;
					}
				}
					
				
			}
		}
		else {
			$aTask['due'] = false;
		}
		
		$aTask['priority'] = $vtodo->getAsString('PRIORITY');
		
		if(!isset($aCalendar['iscompleted'])) $aCalendar['iscompleted']=false;
		
		$aTask['iscompleted']=$aCalendar['iscompleted'];
		$completed = $vtodo->COMPLETED;
		if ($completed) {
			$completed = $completed->getDateTime();
			$completed->setTimezone(new \DateTimeZone($user_timezone));
			$timestamp = strtotime($vtodo->COMPLETED -> getDateTime() -> format('d.m.Y'));
			 
			$aTask['completed'] = $completed->format('d.m.Y');
			if($timestamp==$today){
				$aTask['completed'] =self::$l10n->t('today').' '.$completed->format('H:i');
			}
			if($timestamp==($today-(3600*24))){
				  $aTask['completed']=self::$l10n->t('yesterday').' '.$completed->format('H:i');
			}
            
			
			$aTask['iscompleted']=true;
		}
		else {
			$aTask['completed'] = false;
		}
		$aTask['complete'] = $vtodo->getAsString('PERCENT-COMPLETE');
		$output=$aTask;
		
		return $output;
	}

	public static function validateRequest($request)	{
		$errors = array();
		if($request['summary'] == '') {
			$errors['summary'] = self::$l10n->t('Empty Summary');
		}

		try {
			$timezone = \OCA\Calendar\App::getTimezone();
			$timezone = new \DateTimeZone($timezone);
			new \DateTime($request['due'], $timezone);
		} catch (\Exception $e) {
			$errors['due'] = self::$l10n->t('Invalid date/time');
		}

		if ($request['percent_complete'] < 0 || $request['percent_complete'] > 100) {
			$errors['percent_complete'] = self::$l10n->t('Invalid percent complete');
		}
		if ($request['percent_complete'] == 100 && !empty($request['completed'])) {
			try {
				$timezone = \OCA\Calendar\App::getTimezone();
				$timezone = new \DateTimeZone($timezone);
				new \DateTime($request['completed'], $timezone);
			} catch (\Exception $e) {
				$errors['completed'] = self::$l10n->t('Invalid date/time');
			}
		}

		$priority_options = self::getPriorityOptions();
		if (!in_array($request['priority'], array_keys($priority_options))) {
			$errors['priority'] = self::$l10n->t('Invalid priority');
		}
		return $errors;
	}

      /**
	 * @brief Returns true or false if event is an shared event
	 * @param integer $id
	 * 
	 * @return true or false
	 *
	 */

    public static function checkSharedTodo($id){
    	  
		   $stmt = \OCP\DB::prepare('
    	   SELECT id FROM `*PREFIX*clndr_objects`
     	   WHERE `org_objid` = ? AND `userid` = ? AND `objecttype` = ?');
		   $result = $stmt->execute(array($id,\OCP\User::getUser(),'VTODO'));
		   $row = $result->fetchRow();
		   if(is_array($row)) return $row;
		   else return false;
    }

      /**
	 * @brief Adds an object
	 * @param integer $id Calendar id
	 * @param string $data  object
	 * @return insertid
	 */
	public static function addSharedTask($id,$calid) {
		$shareevent = \OCA\Calendar\Object::find($id);
		
		$stmt = \OCP\DB::prepare( 'INSERT INTO `*PREFIX*clndr_objects` (`calendarid`,`objecttype`,`startdate`,`enddate`,`repeating`,`summary`,`calendardata`,`uri`,`lastmodified`,`isalarm`,`org_objid`,`userid`) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)' );
		$stmt->execute(array($calid,$shareevent['objecttype'],$shareevent['startdate'],$shareevent['enddate'],$shareevent['repeating'],$shareevent['summary'],$shareevent['calendardata'],$shareevent['uri'],time(),$shareevent['isalarm'],$id,\OCP\User::getUser()));
		$object_id = \OCP\DB::insertid('*PREFIX*clndr_objects');

		//\OCA\Calendar\App::loadCategoriesFromVCalendar($object_id, $object);
		\OCA\Calendar\Calendar::touchCalendar($calid);
		//\OCP\Util::emitHook('OC_Calendar', 'addEvent', $object_id);
		return $object_id;
	}

	public static function createVCalendarFromRequest($request){
		$vcalendar = new \OCA\Calendar\VObject('VCALENDAR');
		$vcalendar->add('PRODID', 'ownCloud Calendar');
		$vcalendar->add('VERSION', '2.0');

		$vtodo = new \OCA\Calendar\VObject('VTODO');
		$vcalendar->add($vtodo);

		$vtodo->setDateTime('CREATED', 'now');

		$vtodo->setUID();
		
		return self::updateVCalendarFromRequest($request, $vcalendar);
	}

	public static function updateVCalendarFromRequest($request, $vcalendar){
		$cid=$request["read_worker"];
			
		$accessclass = $request["accessclass"];	
		$summary = $request['tasksummary'];
		$categories = $request["taskcategories"];
		$priority = $request['priority'];
		$description = $request['noticetxt'];
		$location = $request['tasklocation'];
		$link = $request['link'];
		$dueDate=$request['sWV'];
		$dueTime=$request['sWV_time'];
		
		$startDate=$request['startdate'];
		$startTime=$request['startdate_time'];
		$start='';
		
		$relatedTo=(isset($request['relatedto'])?$request['relatedto']:'');
		
		if($startTime!='' && $startDate!=''){
	    	 $start=$startDate.' '.$startTime;
		}
		if($startTime=='' && $startDate!=''){
	    	 $start=$startDate;
		}
		
		$percentComplete=$request['percCompl'];
		$due='';
		//$percent_complete = $request['percent_complete'];
		//$completed = $request['completed'];
		if($dueTime!='' && $dueDate!=''){
	    	 $due=$dueDate.' '.$dueTime;
		}
		if($dueTime=='' && $dueDate!=''){
	    	 $due=$dueDate;
		}

		$vtodo = $vcalendar->VTODO;
			
		$vtodo->setDateTime('LAST-MODIFIED', 'now');
		$vtodo->setDateTime('DTSTAMP', 'now');
		
		$vtodo->SUMMARY= $summary;
		$vtodo->setString('URL', $link);
		//PERCENT-COMPLETE
		$vtodo->setString('PERCENT-COMPLETE', $percentComplete);
		if($percentComplete > 0 && $percentComplete < 100){
			$vtodo->setString('STATUS', 'IN-PROCESS');
		}
		if($percentComplete == 100){
			$vtodo->setString('STATUS', 'COMPLETED');
		}
		if($relatedTo!=''){
			$vtodo->setString('RELATED-TO', $relatedTo);
		}
		$vtodo->setString('LOCATION', $location);
		$vtodo->setString('DESCRIPTION', $description);
		$vtodo->setString('CATEGORIES', $categories);
		$vtodo->setString('PRIORITY', $priority);
		$vtodo->setString('CLASS', $accessclass);
		$CALINFO=\OCA\Calendar\Calendar::find($cid);
		if($CALINFO['userid']!= \OCP\User::getUser() && $accessclass != 'PUBLIC'){
           $vtodo->setString('CLASS', 'PUBLIC');
		}
		
		if ($due) {
	      
		    if ($dueTime) {
		        $sTimezone = \OCA\Calendar\App::getTimezone();
	            $timezone = new \DateTimeZone($sTimezone);
				$due = new \DateTime($due, $timezone);
 				 $vtodo->setDateTime('DUE', $due);
			}else{
				  $due1 = new \DateTime($due);
				  $vtodo->setDateTime('DUE', $due1);
				  $vtodo->DUE['VALUE']='DATE';
			}
		} else {
			unset($vtodo->DUE);
		}
		
		if ($start) {
	      
		    if ($startTime) {
		        $sTimezone = \OCA\Calendar\App::getTimezone();
	            $timezone = new \DateTimeZone($sTimezone);
				$start= new \DateTime($start, $timezone);
 				 $vtodo->setDateTime('DTSTART', $start);
			}else{
				  $start1 = new \DateTime($start);
				  $vtodo->setDateTime('DTSTART', $start1);
				  $vtodo->DTSTART['VALUE']='DATE';
			}
		} else {
			unset($vtodo->DTSTART);
		}
		
		//Alarm New
		/*REMINDER NEW*/
		/*REMINDER NEW*/
		if($request['reminder']!='none'){
			//$aTimeTransform=self::getReminderTimeParsingOptions();	
			if($vtodo -> VALARM){
				$valarm=$vtodo -> VALARM;
			}else{
				$valarm = new \OCA\Calendar\VObject('VALARM');
                $vtodo->add($valarm);
			}
			//sReminderRequest
			
			
			if($request['reminder']=='OWNDEF' && ($request['reminderAdvanced']=='DISPLAY' || $request['reminderAdvanced']=='EMAIL')){
				
				$valarm->setString('ATTENDEE','');
					
				if($request['remindertimeselect']!='ondate') {
				    $valarm->setString('TRIGGER',$request['sReminderRequest']);
				}
				if($request['remindertimeselect']=='ondate') {
					$valarm->setString('TRIGGER',$request['sReminderRequest']);
				}
				if($request['reminderAdvanced']=='EMAIL'){
					$valarm->setString('ATTENDEE','mailto:'.$request['reminderemailinput']);
				}
			   $valarm->setString('DESCRIPTION', 'owncloud');
			   $valarm->setString('ACTION', $request['reminderAdvanced']);
			}else{
				$valarm->setString('ATTENDEE','');
				$valarm->setString('TRIGGER',$request['reminder']);
				 $valarm->setString('DESCRIPTION', 'owncloud');
			     $valarm->setString('ACTION','DISPLAY');
			}
			
		}

		if($request['reminder']=='none'){
			if($vtodo -> VALARM){
				$vtodo->setString('VALARM','');
			}
		}

		return $vcalendar;
	}

	public static function setComplete($vtodo, $percent_complete, $completed)	{
		if (!empty($percent_complete)) {
			$vtodo->setString('PERCENT-COMPLETE', $percent_complete);
		}else{
			$vtodo->__unset('PERCENT-COMPLETE');
		}

		if ($percent_complete == 100) {
			if (!$completed) {
				$completed = 'now';
			}
		} else {
			$completed = null;
		}
		if ($completed) {
			$sTimezone = \OCA\Calendar\App::getTimezone();
			$timezone = new \DateTimeZone($sTimezone);
			$completed = new \DateTime($completed, $timezone);
			$vtodo->setDateTime('COMPLETED', $completed);
			\OCP\Util::emitHook('OC_Task', 'taskCompleted', $vtodo);
		} else {
			unset($vtodo->COMPLETED);
		}
	}
   
   
   
   public static function generateSelectFieldArray($NAME,$WERT,$ARRAYDATA,$MULTIPLE=false){
        

        if(isset($_POST[$NAME])){
           $aSelectValue[$NAME]=$_POST[$NAME];
        }
	  
      
      if(is_array($ARRAYDATA)){
      $OUTPUT='<select name="'.$NAME.'" size="1">';
      foreach ($ARRAYDATA as $KEY => $VALUE) {
            ($KEY == $WERT) ? ($selected = 'selected') : ($selected = '');
            $OUTPUT .= '<option value="' . $KEY . '" ' . $selected . '>'. $VALUE .'</option>';
        }
      $OUTPUT.='</select>';
      
       return $OUTPUT;
    }else return false;
    
  }
   
   /**
	 * @brief Get the permissions for a calendar / an event
	 * @param (int) $id - id of the calendar / event
	 * @param (string) $type - type of the id (calendar/event)
	 * @return (int) $permissions - CRUDS permissions
	 * @param (string) $accessclass - access class (rfc5545, section 3.8.1.3)
	 * @see \OCP\Share
	 */
	public static function getPermissions($id, $type, $accessclass = '') {
		 $permissions_all = \OCP\PERMISSION_ALL;

		if($type == self::CALENDAR) {
			$calendar = self::getCalendar($id, false, false);
			if($calendar['userid'] == \OCP\USER::getUser()) {
				return $permissions_all;
			} else {
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource('calendar','calendar-'. $id);
				if ($sharedCalendar) {
					return $sharedCalendar['permissions'];
				}
			}
		}
		elseif($type == self::TODO) {
			if(\OCA\Calendar\Object::getowner($id) == \OCP\USER::getUser()) {
				return $permissions_all;
			} else {
				$object = \OCA\Calendar\Object::find($id);
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource('calendar','calendar-'. $object['calendarid']);
				$sharedEvent = \OCP\Share::getItemSharedWithBySource('todo','todo-'. $id);
				$calendar_permissions = 0;
				$event_permissions = 0;
				if ($sharedCalendar) {
					$calendar_permissions = $sharedCalendar['permissions'];
				}
				if ($sharedEvent) {
					$event_permissions = $sharedEvent['permissions'];
					
				}
				if ($accessclass === 'PRIVATE') {
					return 0;
				} elseif ($accessclass === 'CONFIDENTIAL') {
					return \OCP\PERMISSION_READ;
				} else {
					return max($calendar_permissions, $event_permissions);
				}
			}
		}
		return 0;
	}
   /**
	 * @brief returns informations about an event
	 * @param int $id - id of the event
	 * @param bool $security - check access rights or not
	 * @param bool $shared - check if the user got access via sharing
	 * @return mixed - bool / array
	 */
	public static function getEventObject($id, $security = true, $shared = false) {
		$event = \OCA\Calendar\Object::find($id);
		if($shared === true || $security === true) {
			$permissions = self::getPermissions($id, self::TODO);
			\OCP\Util::writeLog('aufgaben', __METHOD__.' id: '.$id.', permissions: '.$permissions, \OCP\Util::DEBUG);
			if(self::getPermissions($id, self::TODO)) {
				return $event;
			}
		} else {
			return $event;
		}

		return false;
	}
	
	/**
	 * @brief Returns an object
	 * @param string $uid
	 * @return associative array
	 */
	public static function getEventIdbyUID($uid) {
		$stmt = \OCP\DB::prepare( 'SELECT `id` FROM `*PREFIX*clndr_objects` WHERE  `objecttype`= ? AND `eventuid` = ?' );
		$result = $stmt->execute(array('VTODO',$uid));
		$row=$result->fetchRow();
		return $row['id'];
	}
	
	/**
	 * @brief Returns an object
	 * @param string $uid
	 * @return associative array
	 */
	public static function getCalIdByUID($uid) {
		$stmt = \OCP\DB::prepare( 'SELECT `calendarid` FROM `*PREFIX*clndr_objects` WHERE  `objecttype`= ? AND `eventuid` = ?' );
		$result = $stmt->execute(array('VTODO',$uid));
		$row=$result->fetchRow();
		return $row['calendarid'];
	}
	
	/**
	 * @brief Returns an object
	 * @param string $uid
	 * @return  string
	 */
	public static function getSubTasks($uid) {
		$stmt = \OCP\DB::prepare( 'SELECT `id` FROM `*PREFIX*clndr_objects` WHERE  `objecttype`= ? AND `relatedto` = ?' );
		$result = $stmt->execute(array('VTODO',$uid));
		$sIds='';
		while( $row = $result->fetchRow()) {
			if($sIds=='') {
				$sIds=$row['id'];
			}else{
				$sIds.=','.$row['id'];
			}
		}
		
		return $sIds;
	}
	

	/**
	 * @brief returns the parsed calendar data
	 * @param int $id - id of the event
	 * @param bool $security - check access rights or not
	 * @return mixed - bool / object
	 */
	public static function getVCalendar($id, $security = true, $shared = false) {
		$event_object = self::getEventObject($id, $security, $shared);
		if($event_object === false) {
			return false;
		}
		
		$vobject = \OCA\Calendar\VObject::parse($event_object['calendardata']);
		if(is_null($vobject)) {
			return false;
		}
		return $vobject;
	}
}
