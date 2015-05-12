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
$app = new Application();
$c = $app->getContainer();
// add an navigation entry
$navigationEntry = function () use ($c) {
	return [
		'id' => $c->getAppName(),
		'order' => 1,
		'name' => $c->query('L10N')->t('Tasks'),
		'href' => $c->query('URLGenerator')->linkToRoute('aufgaben.page.index'),
		'icon' => $c->query('URLGenerator')->imagePath('aufgaben', 'tasks.svg'),
	];
};
$c->getServer()->getNavigationManager()->add($navigationEntry);

  

\OC::$server->getSearch()->registerProvider('\OCA\Aufgaben\Search\Provider');

\OCP\Share::registerBackend('todo', '\OCA\Aufgaben\Share\Backend\Vtodo');

