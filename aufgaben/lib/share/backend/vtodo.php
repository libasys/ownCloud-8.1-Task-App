<?php


namespace OCA\Aufgaben\Share\Backend;

class Vtodo implements \OCP\Share_Backend {

	const FORMAT_TODO = 0;
	
	private static $vtodo;
	
	public function isValidSource($itemSource, $uidOwner) {
	     $itemSource = \OCA\Calendar\App::validateItemSource($itemSource,'todo-');	
	     self::$vtodo = \OCA\Calendar\Object::find($itemSource);
		if (self::$vtodo) {
			return true;
		}
		return false;
		
		return true;
	}
	
	public function isShareTypeAllowed($shareType) {
		return true;
	}

	public function generateTarget($itemSource, $shareWith, $exclude = null) {
		if(!self::$vtodo) {
			$itemSource = \OCA\Calendar\App::validateItemSource($itemSource,'todo-');		
			self::$vtodo = \OCA\Calendar\Object::find($itemSource);
		}
	
		return self::$vtodo['summary'];
	}

	public function formatItems($items, $format, $parameters = null) {
		$vtodos = array();
		if ($format == self::FORMAT_TODO) {
			$user_timezone = \OCA\Calendar\App::getTimezone();
			foreach ($items as $item) {
				$item['item_source'] = \OCA\Calendar\App::validateItemSource($item['item_source'],'todo-');		
				if(!\OCA\Aufgaben\App::checkSharedTodo($item['item_source'])){	
					$event = \OCA\Aufgaben\App::getEventObject( $item['item_source'] );
					$vcalendar = \OCA\Calendar\VObject::parse($event['calendardata']);
					$vtodo = $vcalendar->VTODO;
				    $accessclass = $vtodo -> getAsString('CLASS');
				    
					if($accessclass=='' || $accessclass=='PUBLIC'){
						$permissions['permissions'] =$item['permissions'];
						$permissions['calendarcolor'] ='#cccccc';
						$permissions['isOnlySharedTodo'] =true;
						$permissions['calendarowner'] =\OCA\Calendar\Object::getowner($item['item_source']);
						$permissions['displayname']=$item['uid_owner'];
						//\OCP\Util::writeLog('calendar','Cal Owner :'.$permissions['calendarowner'].$vtodo -> getAsString('SUMMARY') ,\OCP\Util::DEBUG);
						$permissions['iscompleted'] =false;
						if($vtodo->COMPLETED) {
							$permissions['iscompleted'] =true;
							 $vtodos['done'][]=\OCA\Aufgaben\App::arrayForJSON($item['item_source'], $vtodo, $user_timezone,$permissions,$event);
						}else{
							 $vtodos['open'][]=\OCA\Aufgaben\App::arrayForJSON($item['item_source'], $vtodo, $user_timezone,$permissions,$event);
						}
						
					    
					}
				}	
				//$vtodos[] = $vtodo;
			}
		}
		return $vtodos;
	}
	
}