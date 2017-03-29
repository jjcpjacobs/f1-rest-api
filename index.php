<?php

// Manager Class
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$command = new MongoDB\Driver\Command(['listCollections' => 1]);
$cursor = $manager->executeCommand('demodata', $command);

$collections = [];
foreach ($cursor->toArray() as $collection) {
	array_push($collections, $collection);
}

$actions = ['add', 'overview', 'view'];

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['PATH_INFO'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

// retrieve the table and key from the path
$collection = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));
$action = preg_replace('/[^a-z0-9_]+/i','',array_shift($request));

if (in_array($collection, $collections) || in_array($action, $actions)) {	
	switch($method) {
		case 'DELETE':
			break;
		case 'POST':
			if ($action == 'add') {
				$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
				$params = [];

				foreach ($_POST as $key => $value) {
					$params[$key] = $value;
				}
				$bulk->insert($params);
				$manager->executeBulkWrite('demodata.carbrands', $bulk);
			} else {
				echo json_encode(['error' => 'Wrong arguments']);
			}
			break;
		case 'PUT':
			break;
		case 'GET':
			switch($action) {
				case 'view':
					$key = array_shift($request);
					// Query Class
					$query = new MongoDB\Driver\Query(['_id' => new MongoDB\BSON\ObjectID($key)]);

					// Output of the executeQuery will be object of MongoDB\Driver\Cursor class
					$cursor = $manager->executeQuery('demodata.carbrands', $query);			
					$items = $cursor->toArray();
					if (count($items) > 0) {
						header('Content-type:application/json;charset=utf-8');
						echo $output = json_encode($items);
					} else {
						echo json_encode(['error' => 'No data found']);
					} 

					break;
			}
		case 'overview':
			// Query Class
			$query = new MongoDB\Driver\Query([]);

			// Output of the executeQuery will be object of MongoDB\Driver\Cursor class
			$cursor = $manager->executeQuery('demodata.carbrands', $query);			
			
			header('Content-type:application/json;charset=utf-8');
			echo json_encode($cursor->toArray());
			break;			
		default:
			break;
	}
} else {
	echo json_encode(['error' => 'Invalid call']);
}