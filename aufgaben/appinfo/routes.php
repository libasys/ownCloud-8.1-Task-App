<?php

namespace OCA\Aufgaben;


use \OCA\Aufgaben\AppInfo\Application;

$application = new Application();
$application->registerRoutes($this, ['routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'public#index', 'url' => '/s/{token}', 'verb' => 'GET'],
		['name' => 'public#index','url'  => '/s/{token}', 'verb' => 'POST', 'postfix' => 'auth'],
		['name' => 'task#newTask',	'url' => '/newtask',	'verb' => 'POST'],
		['name' => 'task#editTask',	'url' => '/edittask',	'verb' => 'POST'],
		['name' => 'task#deleteTask',	'url' => '/deletetask',	'verb' => 'POST'],
		['name' => 'task#getTasks',	'url' => '/gettasks',	'verb' => 'POST'],
		['name' => 'task#addSharedTask',	'url' => '/addsharedtask',	'verb' => 'POST'],
		['name' => 'task#addCategoryTask',	'url' => '/addcategorytask',	'verb' => 'POST'],
		['name' => 'task#buildLeftNavigation',	'url' => '/buildleftnavigation',	'verb' => 'POST'],
		['name' => 'task#setCompleted',	'url' => '/setcompleted',	'verb' => 'POST'],
		['name' => 'task#setCompletedPercentMainTask',	'url' => '/setcompletedpercentmaintask',	'verb' => 'POST'],
		['name' => 'task#getDefaultValuesTasks',	'url' => '/getdefaultvaluestasks',	'verb' => 'GET'],
		]
	]);
	
	
