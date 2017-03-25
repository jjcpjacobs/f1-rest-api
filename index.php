<?php

// Manager Class
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$command = new MongoDB\Driver\Command(['listCollections' => 1]);
$cursor = $manager->executeCommand('demodata', $command);

$collections = [];
foreach ($cursor->toArray() as $collection) {
	array_push($collections, $collection);
}

$actions = ['overview', 'view'];

$database['carbrands'] = [0 => 'Audi', 1 => 'BMW', 2 => 'Mercedes'];

$tables = array_keys($database);

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

// retrieve the table and key from the path
$collection = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
$action = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));

if (in_array($collection, $collections) || in_array($action, $actions)) {	
	switch($action) {
		case 'view':
			$key = array_shift($request)+0;
			// Query Class
			$query = new MongoDB\Driver\Query(['id' => $key]);

			// Output of the executeQuery will be object of MongoDB\Driver\Cursor class
			$cursor = $manager->executeQuery('demodata.carbrands', $query);			
			$items = $cursor->toArray();
			if (count($items) > 0) {
			
				echo '<ul>';
				foreach ($items as $item) {
					echo '<li>'.$item->name.'</li>';
				}
				echo '</ul>';			
			} else {
				throw new Exception('No data found');
			} 

			break;
		case 'overview':
			// Query Class
			$query = new MongoDB\Driver\Query([]);

			// Output of the executeQuery will be object of MongoDB\Driver\Cursor class
			$cursor = $manager->executeQuery('demodata.carbrands', $query);			
			
			echo '<ul>';
			foreach ($cursor->toArray() as $item) {
				echo '<li>'.$item->name.'</li>';
			}
			echo '</ul>';
			break;
	}	
	

} else {
	throw new Exceptions('Invalid call');
}