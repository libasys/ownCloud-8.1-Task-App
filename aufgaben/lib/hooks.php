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
/**
 * This class contains all hooks.
 */
 namespace OCA\Aufgaben;
 
class Hooks{
	
	/**
	 * @brief Add a new notifyType 
	 * @param paramters parameters from OC_Activity notification_types-Hook
	 * @return array
	 */
	 public static function addNotifyType($parameters) {
		
		$aNewNotifyTyp=array(
		
		'shared_aufgaben'=>$parameters['language']->t('A todo has been <strong>shared</strong>')
		);
		
		$parameters['types']=array_merge($aNewNotifyTyp,$parameters['types']);
		
		return true;
		
	}
	 public static function addIconType($parameters) {
			
		switch($parameters['type']){
			
			case 'shared_aufgaben':
				$parameters['icon']= 'icon-share';
			break;		
		}	
		
		return true;
		
	}
	 
	 
	
}
