<?php
/**
 * ownCloud - Aufgaben
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
 
namespace OCA\Aufgaben\Controller;

use \OCA\Aufgaben\App as AufgabenApp;
use \OCA\Aufgaben\Timeline;
use \OCA\Calendar\App as CalendarApp;
use \OCA\Calendar\Calendar as CalendarCalendar;
use \OCA\Calendar\VObject;
use \OCA\Calendar\Object;

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\JSONResponse;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\Share;
use \OCP\IConfig;

class TaskController extends Controller {

	private $userId;
	private $l10n;
	private $configInfo;

	public function __construct($appName, IRequest $request, $userId, $l10n, IConfig $settings) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		$this->configInfo = $settings;
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function getTasks() {
		$sMode = $this -> params('mode');
		$calId = $this -> params('calid');
		$atasksModeAllowed = array('dayselect'=>1,'showall'=>1,'today'=>1,'tomorrow'=>1,'actweek'=>1,'withoutdate'=>1,'missedactweek'=>1,'alltasks'=>1,'alltasksdone'=>1,'sharedtasks'=>1,'comingsoon'=>1);
		$tasks = array();
		
		if(intval($calId) > 0 && $sMode=='') {
			$cDataTimeLine=new Timeline();
			$cDataTimeLine->setTimeLineMode('');
			$cDataTimeLine->setCalendarId($calId);
			$tasks=$cDataTimeLine->generateCalendarSingleOutput();
		}
		
		// Get Timelined tasks
		if($sMode!='' && $atasksModeAllowed[$sMode] && $sMode!='sharedtasks'){
			$calendars = CalendarCalendar::allCalendars($this->userId, true);
		   	$cDataTimeLine=new Timeline();
		   
		   	if($sMode=='dayselect'){
			   	$sDay = $this -> params('sday');	
			   	$timeStampDay=strtotime($sDay);
			   	$cDataTimeLine->setTimeLineDay($timeStampDay);
		   	}
		   
		   	$cDataTimeLine->setTimeLineMode($sMode);
		   	$cDataTimeLine->setCalendars($calendars);
		   	$tasks=$cDataTimeLine->generateTasksAllOutput();
		}
		
		//Get Shared Tasks
		if($sMode!='' && $atasksModeAllowed[$sMode] && $sMode=='sharedtasks'){
			
			$singletodos = \OCP\Share::getItemsSharedWith('todo', \OCA\Aufgaben\Share\Backend\Vtodo::FORMAT_TODO);
			if(is_array($singletodos)){
					$tasks=$singletodos;
		   }	
		}
		
		$response = new JSONResponse();
		$response -> setData($tasks);
		return $response;
		
	}

	/**
	 * @NoAdminRequired
	 */
	public function setCompleted() {
		$id = $this -> params('id');
		$checked = $this -> params('checked');
			
		$vcalendar = AufgabenApp::getVCalendar( $id, true, true );
		$vtodo = $vcalendar->VTODO;
		
		$aTask= AufgabenApp::getEventObject($id, true, true);	
		$aCalendar= CalendarCalendar::find($aTask['calendarid']);		
		AufgabenApp::setComplete($vtodo, $checked ? '100' : '0', null);
		AufgabenApp::edit($id, $vcalendar->serialize());
		$user_timezone = CalendarApp::getTimezone();
		$task_info[] = AufgabenApp::arrayForJSON($id, $vtodo, $user_timezone,$aCalendar,$aTask);		
		
		$subTaskIds='';
		if($aTask['relatedto']==''){
			$subTaskIds=AufgabenApp::getSubTasks($aTask['eventuid']);
			if($subTaskIds!=''){
			  $tempIds=explode(',',$subTaskIds);	
			  foreach($tempIds as $subIds){
			  	$vcalendar = AufgabenApp::getVCalendar( $subIds, true, true );
				$vtodo = $vcalendar->VTODO;
				AufgabenApp::setComplete($vtodo, $checked ? '100' : '0', null);
				AufgabenApp::edit($subIds, $vcalendar->serialize());
				$task_info[] = AufgabenApp::arrayForJSON($subIds, $vtodo, $user_timezone,$aCalendar,$aTask);
			  }
			}
		}
		
		$response = new JSONResponse();
		$response -> setData($task_info);
		return $response;
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function setCompletedPercentMainTask() {
			
		$id = $this -> params('id');
		
		$aTask= AufgabenApp::getEventObject($id, true, true);	
		if($aTask['relatedto']==''){
			$subTaskIds= AufgabenApp::getSubTasks($aTask['eventuid']);
			if($subTaskIds!=''){
				  $tempIds=explode(',',$subTaskIds);
				$iSubTasksComplete=0;	
				  foreach($tempIds as $subIds){
				  	$vcalendar = AufgabenApp::getVCalendar( $subIds, true, true );
					$vtodo = $vcalendar->VTODO;
					if($vtodo->{'PERCENT-COMPLETE'}){
						 $iSubTasksComplete+= (int) $vtodo->getAsString('PERCENT-COMPLETE');
					}
				  }
				  
				  if($iSubTasksComplete>0){
				  	
				  	 $gesamtCptl =  ($iSubTasksComplete * 100) / (100 * count($tempIds));
			  	 	 $gesamtCptl=round($gesamtCptl);
					 
				  	$vcalendar1 = AufgabenApp::getVCalendar( $id, true, true );
					$vtodoMain = $vcalendar1->VTODO;
					AufgabenApp::setComplete($vtodoMain,$gesamtCptl,null);
					AufgabenApp::edit($id, $vcalendar1->serialize());
					
					$params=[
						'id' => $id,
						'percentCptl' => $gesamtCptl,
					];
					$response = new JSONResponse();
					$response -> setData($params);
					return $response;
				  }
			}
		}
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function buildLeftNavigation() {
			
		$calendars = CalendarCalendar::allCalendars($this->userId, true);
		$cDataTimeLine=new Timeline();
		$cDataTimeLine->setCalendars($calendars);
		$outputTodoNav=$cDataTimeLine->generateTodoOutput();
		
		$params = [
			'calendars' => $calendars,
			'tasksCount' => $outputTodoNav['tasksCount'],
			'aTaskTime' => $outputTodoNav['aTaskTime'],
			'aCountCalEvents' => $outputTodoNav['aCountCalEvents'],
		];
		
		$response = new TemplateResponse('aufgaben', 'tasks.list', $params, '');
		
		return $response;
		
	}
	/**
	 * @NoAdminRequired
	 */
	public function addSharedTask() {
		
		$taskid = $this -> params('taskid');
		$calid = $this -> params('calid');
		
		AufgabenApp::addSharedTask($taskid,$calid);

		$response = new JSONResponse();
		return $response;
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function addCategoryTask() {
		
		$id = $this -> params('id');
		$category = $this -> params('category');
		
		if (!empty($id)) {
			$data = AufgabenApp::getEventObject($id, false, false);
			$vcalendar = VObject::parse($data['calendardata']);
			$vtodo = $vcalendar -> VTODO;
			$orgId = $data['org_objid'];
		
			if ($vtodo -> CATEGORIES) {
				$aCategory = $vtodo -> getAsArray('CATEGORIES');
				$sCatNew = '';
				$aCatNew = array();
				foreach ($aCategory as $sCat) {
					$aCatNew[$sCat] = 1;
					if ($sCatNew == '') {
						$sCatNew = $sCat;
					} else {
						$sCatNew .= ',' . $sCat;
					}
				}
				if (!array_key_exists($category, $aCatNew)) {
					$sCatNew .= ',' . $category;
				}
				$vtodo -> setString('CATEGORIES', $sCatNew);
			} else {
				$vtodo -> setString('CATEGORIES', $category);
			}
		
			$vtodo -> setDateTime('LAST-MODIFIED', 'now');
			$vtodo -> setDateTime('DTSTAMP', 'now');
		
			AufgabenApp::edit($id, $vcalendar -> serialize(), $orgId);
		
			$lastmodified = $vtodo -> __get('LAST-MODIFIED') -> getDateTime();
			
			$params=[
				'lastmodified' =>  (int)$lastmodified -> format('U')
			];
			
			$response = new JSONResponse();
			$response -> setData($params);
			
			return $response;
		}

		
		
	}
	
	/**
	 * @NoAdminRequired
	 */
	public function newTask() {
		//relatedto,hiddenfield, read_worker,$_POST,mytaskcal, mytaskmode
		$relatedto = $this -> params('relatedto');
		$hiddenPostField = $this -> params('hiddenfield');
		$myTaskCal = $this -> params('mytaskcal');
		$myTaskMode = $this -> params('mytaskmode');
		
		if(isset($hiddenPostField) && $hiddenPostField === 'newitTask'){
			$cid = $this -> params('read_worker');	
			$postRequestAll = $this -> getParams();
			$vcalendar = AufgabenApp::createVCalendarFromRequest($postRequestAll);
			
			$id=Object::add($cid, $vcalendar->serialize());
			
			$vcalendar1 = AufgabenApp::getVCalendar( $id, true, true );
			$vtodo = $vcalendar1->VTODO;
				
			$aTask= AufgabenApp::getEventObject($id, true, true);	
			$aCalendar= CalendarCalendar::find($aTask['calendarid']);
			$user_timezone = CalendarApp::getTimezone();
			$task_info = AufgabenApp::arrayForJSON($id, $vtodo, $user_timezone,$aCalendar,$aTask);
			
			$response = new JSONResponse();
			$response -> setData($task_info);
			return $response;
		}
		
		if(isset($relatedto) && $relatedto !== ''){
			$calMainId=AufgabenApp::getCalIdByUID($relatedto);
		}
		
		$calendarsArrayTmp = CalendarCalendar::allCalendars($this->userId, true);
		//Filter Importent Values
		$calendar_options = array();
		$checkArray=array();
		$checkShareArray=array();
		$bShareCalId='';
		foreach($calendarsArrayTmp as $calendar) {
			if($calendar['userid'] != $this->userId) {
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource('calendar', 'calendar-'.$calendar['id']);
				if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_CREATE)) {
					array_push($calendar_options, $calendar);
					$checkShareArray[$calendar['id']]=$calendar['id'];	
				}
			} else {
				array_push($calendar_options, $calendar);
				$checkArray[$calendar['id']]=$calendar['id'];	
			}
		}
		
		$priorityOptionsArray= AufgabenApp::getPriorityOptionsFilterd();
		$access_class_options = CalendarApp::getAccessClassOptions();
		
		$priorityOptions= AufgabenApp::generateSelectFieldArray('priority','',$priorityOptionsArray,false);
		
		$activeCal='';
		if($this->configInfo->getUserValue($this->userId, 'calendar', 'choosencalendar')){
			$activeCal=$this->configInfo->getUserValue($this->userId, 'calendar', 'choosencalendar');
		}
		
		$reminder_options = CalendarApp::getReminderOptions();
		$reminder_advanced_options = CalendarApp::getAdvancedReminderOptions();
		$reminder_time_options = CalendarApp::getReminderTimeOptions();
		$activeCal=$this->configInfo->getUserValue($this->userId, 'calendar', 'choosencalendar');
		if(intval($activeCal) > 0 && $activeCal!=''){
			if($myTaskMode != 'calendar' || $myTaskCal==0) {
				$activeCal = $activeCal;
			}else {
				$activeCal = $myTaskCal;
			}
		}
		
		//reminder
		$reminderdate='';
		$remindertime='';
		
		
		$params = [
			'priorityOptions' => $priorityOptions,
			'relatedToUid' => $relatedto,
			'access_class_options' => $access_class_options,
			'calendar_options' => $calendar_options,
			'calendar' => $activeCal,
			'mymode' => $myTaskMode,
			'mycal' => $myTaskCal,
			'bShareCalId' => $bShareCalId,
			'accessclass' => '',
			'reminder_options' => $reminder_options,
			'reminder' => 'none',
			'reminder_time_options' => $reminder_time_options,
			'reminder_advanced_options' => $reminder_advanced_options,
			'reminder_advanced' => 'DISPLAY',
			'remindertimeselect' => '',
			'remindertimeinput' => '',
			'reminderemailinput' => '',
			'reminder_rules' => '',
			'reminderdate' => '',
			'remindertime' => '',
		];
		
		$response = new TemplateResponse('aufgaben', 'event.new',$params, '');
		
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function editTask() {
		//relatedto,hiddenfield, read_worker,$_POST,mytaskcal, mytaskmode
		$id = $this -> params('tid');
		$hiddenPostField = $this -> params('hiddenfield');
		$myTaskCal = $this -> params('mytaskcal');
		$myTaskMode = $this -> params('mytaskmode');
		
		$data = AufgabenApp::getEventObject($id, false, false);
		$object = VObject::parse($data['calendardata']);
		$calId = Object::getCalendarid($id); 
		$orgId=$data['org_objid'];
		
		//Search for Main Task
		$mainTaskId='';
		if($data['relatedto']!=''){
			$mainTaskId=AufgabenApp::getEventIdbyUID($data['relatedto']);
		}
		//Search for Sub Tasks
		$subTaskIds='';
		if($data['relatedto']==''){
			$subTaskIds=AufgabenApp::getSubTasks($data['eventuid']);
		}
		
		if(isset($hiddenPostField) && $hiddenPostField==='edititTask' && $id > 0){
			$cid = $this -> params('read_worker');		
			$postRequestAll = $this -> getParams();	
			AufgabenApp::updateVCalendarFromRequest($postRequestAll, $object);
			AufgabenApp::edit($id, $object->serialize(),$orgId);
			
			if($mainTaskId==''){
				$mainTaskId=$id;
			}
			
			if($calId != intval($cid)){
				OCA\Calendar\Object::moveToCalendar($id, intval($cid));
				 if($subTaskIds!=''){
				 	$tempIds=explode(',',$subTaskIds);
					  foreach($tempIds as $subIds){
					  	OCA\Calendar\Object::moveToCalendar($subIds, intval($cid));
					  }
				 }
			}
			$params=[
				'mainid' => $mainTaskId
			];
			
			$response = new JSONResponse();
			$response -> setData($params);
			
			return $response;
		}
		
		    $vtodo = $object -> VTODO;
			$object = Object::cleanByAccessClass($id, $object);
			$accessclass = $vtodo -> getAsString('CLASS');
			$permissions = AufgabenApp::getPermissions($id, AufgabenApp::TODO, $accessclass);
			$link = strtr($vtodo -> getAsString('URL'), array('\,' => ',', '\;' => ';'));
		 
			$TaskDate=''; 
			$TaskTime='';
			if($vtodo->DUE){
				 	$dateDueType=$vtodo->DUE->getValueType();
						    
					 if($dateDueType=='DATE'){
					 	$TaskDate = $vtodo->DUE -> getDateTime() -> format('d.m.Y');
						$TaskTime ='';
					 }
					 if($dateDueType=='DATE-TIME'){
					 	$TaskDate = $vtodo->DUE -> getDateTime() -> format('d.m.Y');
						$TaskTime = $vtodo->DUE -> getDateTime() -> format('H:i');
					 }
				
			}
			
			$TaskStartDate='';
			$TaskStartTime='';
			if ( $vtodo->DTSTART) {
					 $dateStartType=$vtodo->DTSTART->getValueType();	
					if($dateStartType=='DATE'){
					 	$TaskStartDate = $vtodo->DTSTART -> getDateTime() -> format('d.m.Y');
						$TaskStartTime ='';
					 }
					 if($dateStartType=='DATE-TIME'){
					 	$TaskStartDate = $vtodo->DTSTART -> getDateTime() -> format('d.m.Y');
						$TaskStartTime = $vtodo->DTSTART -> getDateTime() -> format('H:i');
					 }
			}
		
		$accessclass = $vtodo -> getAsString('CLASS');
		$priority= $vtodo->getAsString('PRIORITY');
		
		$calendarsArrayTmp = CalendarCalendar::allCalendars($this->userId, true);
		//Filter Importent Values
		$calendar_options = array();
		$checkArray=array();
		$checkShareArray=array();
		$bShareCalId='';
		
		foreach($calendarsArrayTmp as $calendar) {
	
			if($calendar['userid'] != $this->userId || $mainTaskId!='') {
				
				$sharedCalendar = \OCP\Share::getItemSharedWithBySource('calendar', 'calendar-'.$calendar['id']);
				if ($sharedCalendar && ($sharedCalendar['permissions'] & \OCP\PERMISSION_UPDATE) && $mainTaskId=='') {
					array_push($calendar_options, $calendar);
					$checkShareArray[$calendar['id']]=$sharedCalendar['permissions'];	
				}
			} else {
				$checkShareArray[$calendar['id']]= \OCP\PERMISSION_ALL;	
			
				array_push($calendar_options, $calendar);
			}
		}
		
		
		if(!array_key_exists($calId,$checkShareArray)){
			$bShareCalId='hide';
		}
		
		$priorityOptionsArray= AufgabenApp::getPriorityOptionsFilterd();
		$priorityOptions= AufgabenApp::generateSelectFieldArray('priority', (string)$vtodo->priority, $priorityOptionsArray,false);
		$access_class_options = CalendarApp::getAccessClassOptions();
		//NEW Reminder
		$reminder_options = CalendarApp::getReminderOptions();
		$reminder_advanced_options = CalendarApp::getAdvancedReminderOptions();
		$reminder_time_options = CalendarApp::getReminderTimeOptions();
		
		//reminder
		$vtodosharees = array();
		$sharedwithByVtodo = \OCP\Share::getItemShared('todo','todo-'.$id);
		if(is_array($sharedwithByVtodo)) {
			foreach($sharedwithByVtodo as $share) {
				if($share['share_type'] == \OCP\Share::SHARE_TYPE_USER || $share['share_type'] == \OCP\Share::SHARE_TYPE_GROUP) {
					$vtodosharees[] = $share;
				}
			}
		}
		
		$percentCompleted='0';	
		if($vtodo->{'PERCENT-COMPLETE'}){
			$percentCompleted = $vtodo -> getAsString('PERCENT-COMPLETE');
		}

		$aAlarm = $this->setAlarmTask($vtodo, $reminder_options);
		
			$params = [
			'id' => $id,
			'calId' => $calId,
			'orgId' => $orgId,
			'permissions' => $permissions,
			'priorityOptions' => $priorityOptions,
			'access_class_options' => $access_class_options,
			'calendar_options' => $calendar_options,
			'calendar' => $calId,
			'mymode' => $myTaskMode,
			'mycal' => $myTaskCal,
			'bShareCalId' => $bShareCalId,
			'subtaskids' => $subTaskIds,
			'cal_permissions' => $checkShareArray,
			'accessclass' => $accessclass,
			'reminder_options' => $reminder_options,
			'reminder_rules' => (array_key_exists('triggerRequest',$aAlarm)) ? $aAlarm['triggerRequest']:'',
			'reminder' =>  $aAlarm['action'],
			'reminder_time_options' => $reminder_time_options,
			'reminder_advanced_options' => $reminder_advanced_options,
			'reminder_advanced' => 'DISPLAY',
			'remindertimeselect' => (array_key_exists('reminder_time_select',$aAlarm)) ? $aAlarm['reminder_time_select']:'',
			'remindertimeinput' => (array_key_exists('reminder_time_input',$aAlarm)) ? $aAlarm['reminder_time_input']:'',
			'reminderemailinput' => (array_key_exists('email',$aAlarm)) ? $aAlarm['email']:'',
			'reminderdate' => (array_key_exists('reminderdate',$aAlarm)) ? $aAlarm['reminderdate']:'',
			'remindertime' => (array_key_exists('remindertime',$aAlarm)) ? $aAlarm['remindertime']:'',
			'mailNotificationEnabled' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_mail_notification', 'yes'),
			'allowShareWithLink' => \OC::$server->getAppConfig()->getValue('core', 'shareapi_allow_links', 'yes'),
			'link' => $link,
			'priority' => $priority,
			'TaskDate' => $TaskDate,
			'TaskTime' => $TaskTime,
			'TaskStartDate' => $TaskStartDate,
			'TaskStartTime' => $TaskStartTime,
			'vtodosharees' => $vtodosharees,
			'percentCompleted' => $percentCompleted,
			'vtodo' => $vtodo,
		];
		
			
		$response = new TemplateResponse('aufgaben', 'event.edit',$params, '');	
		
		return $response;
		
	}

	private function setAlarmTask($vtodo, $reminder_options){
		
		$aAlarm='';
		
		if($vtodo -> VALARM){
			$valarm=$vtodo -> VALARM;
			$aAlarm['action']=$valarm -> getAsString('ACTION');
			$aAlarm['triggerRequest']=$valarm ->getAsString('TRIGGER');
			$tempTrigger=$aAlarm['triggerRequest'];
			if(strstr($tempTrigger,'TRIGGER')){
				$temp=explode('TRIGGER:',$tempTrigger);
				$aAlarm['trigger']=$temp[1];
			}else{
				$aAlarm['trigger']=$tempTrigger;
				$aAlarm['triggerRequest']='TRIGGER;VALUE=DATE-TIME:'.$tempTrigger;
			}
			
			$aAlarm['email']='';
			if($valarm ->ATTENDEE){
				$aAlarm['email']=$valarm -> getAsString('ATTENDEE');
				if(stristr($aAlarm['email'],'mailto:')) $aAlarm['email']=substr($aAlarm['email'],7,strlen($aAlarm['email']));
			}
		
		   if(array_key_exists($aAlarm['trigger'],$reminder_options)){
		   	   $aAlarm['action']=$aAlarm['trigger'];
			   $aAlarm['reminderdate'] ='';
			   $aAlarm['remindertime'] = '';
			   
		   }else{
		   	  $aAlarm['action']='OWNDEF';
		  
				if(stristr($aAlarm['trigger'],'PT')){
						$tempDescr='';
					    $aAlarm['reminderdate'] ='';
			   			$aAlarm['remindertime'] = '';
						if(stristr($aAlarm['trigger'],'-PT')){
							$tempDescr='before';
						}
						if(stristr($aAlarm['trigger'],'+PT')){
							$tempDescr='after';
						}
						
						//GetTime
						$TimeCheck=substr($aAlarm['trigger'],3,strlen($aAlarm['trigger']));
						
						$aAlarm['reminder_time_input']=substr($TimeCheck,0,(strlen($TimeCheck)-1));
						
						//returns M,H,D
						$alarmTimeDescr=substr($aAlarm['trigger'],-1,1);
						if($alarmTimeDescr=='H'){
							$aAlarm['reminder_time_select']='hours'.$tempDescr;
							
						}
						if($alarmTimeDescr=='M'){
							$aAlarm['reminder_time_select']='minutes'.$tempDescr;
						}
						if($alarmTimeDescr=='D'){
							$aAlarm['reminder_time_select']='days'.$tempDescr;
						}
				}else{
				   
				    $dttriggertime=$valarm->TRIGGER;
					if($dttriggertime->getValueType()=='DATE'){
						$aAlarm['reminderdate'] = $dttriggertime -> getDateTime() -> format('d-m-Y');
						$aAlarm['remindertime'] ='';
					}
					if($dttriggertime->getValueType()=='DATE-TIME'){
						$aAlarm['reminderdate'] = $dttriggertime -> getDateTime() -> format('d-m-Y');
						$aAlarm['remindertime'] = $dttriggertime -> getDateTime() -> format('H:i');
					}
					$aAlarm['reminder_time_input']='';
					$aAlarm['reminder_time_select']='ondate';
				}
		       
			}
			
		}else{
			$aAlarm['action']='none';
		}
		
		return $aAlarm;
		
	}

	/**
	 * @NoAdminRequired
	 */
	public function deleteTask() {
			
		$id = $this -> params('id');
		
		$task = CalendarApp::getEventObject( $id );
		//Search for Sub Tasks
			$subTaskIds='';
			if($task['relatedto']==''){
				$subTaskIds= AufgabenApp::getSubTasks($task['eventuid']);
				if($subTaskIds!=''){
				  $tempIds=explode(',',$subTaskIds);	
				  foreach($tempIds as $subIds){
				  	Object::delete($subIds);
				  }
				}
			}
			
		Object::delete($id);
		
		$params=[
			'id' => $id,
		];
		
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
		
	}
	/**
	 * @NoAdminRequired
	 */
	public function getDefaultValuesTasks() {
			
		$calendars = CalendarCalendar::allCalendars($this->userId);
		$myCalendars=array();
		
		foreach($calendars as $calendar) {
			if(!array_key_exists('active', $calendar)){
				$calendar['active'] = 1;
			}
			if($calendar['active'] == 1) {
				//$calendarInfo[$calendar['id']]=array('bgcolor'=>$calendar['calendarcolor'],'color'=>OCA\Calendar\Calendar::generateTextColor($calendar['calendarcolor']));
				$myCalendars[$calendar['id']]=array('id'=>$calendar['id'],'name'=>$calendar['displayname']);
			}
		}
		
			$checkCat=CalendarApp::loadTags();
			$checkCatTagsList='';
			$checkCatCategory='';
			
			
			foreach($checkCat['categories'] as $category){
					$checkCatCategory[]=$category;
			}
			
			
			foreach($checkCat['tagslist'] as $tag){
					$checkCatTagsList[$tag['name']]=array('name'=>$tag['name'],'color'=>$tag['color'],'bgcolor'=>$tag['bgcolor']);
			}
		
		$params=[
			'mycalendars' => $myCalendars,
			'categories' => $checkCatCategory,
			'tags' => $checkCatTagsList
		];
		
		$response = new JSONResponse();
		$response -> setData($params);
		return $response;
	}
	
}
