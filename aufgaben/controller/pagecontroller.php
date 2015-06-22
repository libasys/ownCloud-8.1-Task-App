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

use \OCP\AppFramework\Controller;
use \OCP\AppFramework\Http\TemplateResponse;
use \OCP\IRequest;
use \OCP\IL10N;
/**
 * Controller class for main page.
 */
class PageController extends Controller {
	
	private $userId;
	private $l10n;
	
	

	public function __construct($appName, IRequest $request,  $userId, IL10N $l10n) {
		parent::__construct($appName, $request);
		$this -> userId = $userId;
		$this->l10n = $l10n;
		
	}
	
	public function getLanguageCode() {
        return $this->l10n->getLanguageCode();
    }

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		\OCP\Util::addScript('calendar', '3rdparty/jquery.webui-popover');
		\OCP\Util::addStyle('calendar', '3rdparty/jquery.webui-popover');
		\OCP\Util::addScript('calendar','timepicker');
		\OCP\Util::addScript('aufgaben', 'aufgaben');
		\OCP\Util::addScript('calendar','jquery.nicescroll.min');
		\OCP\Util::addStyle('calendar', '3rdparty/fontello/css/animation');
		\OCP\Util::addStyle('calendar', '3rdparty/fontello/css/fontello');
		\OCP\Util::addStyle('aufgaben', 'style');
		\OCP\Util::addScript('calendar', '3rdparty/tag-it');
		\OCP\Util::addStyle('calendar', '3rdparty/jquery.tagit');
		
		
		$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
		$csp->addAllowedImageDomain(':data');
		
		$config = \OC::$server->getConfig();	
		
		$response = new TemplateResponse('aufgaben', 'index');
		$response->setParams(array(
			'allowShareWithLink' => $config->getAppValue('core', 'shareapi_allow_links', 'yes'),
			'mailNotificationEnabled' => $config->getAppValue('core', 'shareapi_allow_mail_notification', 'no'),
			'mailPublicNotificationEnabled' => $config->getAppValue('core', 'shareapi_allow_public_notification', 'no'),
		));
		$response->setContentSecurityPolicy($csp);
		

		return $response;
	}
}