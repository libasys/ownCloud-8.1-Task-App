<?php
/**
 * ownCloud - Pinit
 *
 * @author Sebastian Doell
 * @copyright 2014 sebastian doell sebastian@libasys.de
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
 
namespace OCA\Aufgaben\AppInfo;

use OC\AppFramework\Utility\SimpleContainer;
use \OCP\AppFramework\App;

use \OCA\Aufgaben\Controller\TaskController;
use \OCA\Aufgaben\Controller\PageController;
use \OCA\Aufgaben\Controller\PublicController;

use \OCP\Share;
use \OCP\IContainer;
use OCP\AppFramework\IAppContainer;


class Application extends App {
	
	public function __construct (array $urlParams=array()) {
		
		parent::__construct('aufgaben', $urlParams);
        $container = $this->getContainer();
	
	
		$container->registerService('PageController', function(IContainer $c) {
			return new PageController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N')
			);
		});
		
		
		$container->registerService('PublicController', function(IContainer $c) {
			return new PublicController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('L10N'),
			$c->query('Session'),
			$c->query('OCP\AppFramework\Utility\IControllerMethodReflector'),
			$c->query('ServerContainer')->getURLGenerator()
			);
		});
	
		$container->registerService('TaskController', function(IContainer $c) {
			return new TaskController(
			$c->query('AppName'),
			$c->query('Request'),
			$c->query('UserId'),
			$c->query('L10N'),
			$c->query('Settings')
			);
		});
		
	
          /**
		 * Core
		 */
		 
		 $container->registerService('URLGenerator', function(IContainer $c) {
			/** @var \OC\Server $server */
			$server = $c->query('ServerContainer');
			return $server->getURLGenerator();
		});
		 
		$container -> registerService('UserId', function(IContainer $c) {
			$server = $c->query('ServerContainer');

			$user = $server->getUserSession()->getUser();
			return ($user) ? $user->getUID() : '';
			
		});
		
		$container -> registerService('L10N', function(IContainer $c) {
			return $c -> query('ServerContainer') -> getL10N($c -> query('AppName'));
		});
		
		$container->registerService('Settings', function($c) {
			return $c->query('ServerContainer')->getConfig();
		});
		
		$container->registerService('Session', function (IAppContainer $c) {
			return $c->getServer()
					 ->getSession();
			}
		);
		 $container->registerService('Token', function (IContainer $c) {
			return $c->query('Request') ->getParam('token');
			}
		);
	}
  
    

}

