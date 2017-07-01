<?php

//phpinfo();
//exit;

include('model.php');

// Manager Class
$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$command = new MongoDB\Driver\Command(['listCollections' => 1]);
$cursor = $manager->executeCommand('demodata', $command);

$collections = [];
foreach ($cursor->toArray() as $collection) {
	array_push($collections, $collection->name);
}

// get the HTTP method, path and body of the request
$method = $_SERVER['REQUEST_METHOD'];
$request = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
$input = json_decode(file_get_contents('php://input'),true);

// retrieve the table and key from the path
$collection = $_GET['collection'];

if (in_array($collection, $collections)) {
	switch($method) {
		case 'DELETE':
			$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
			$key = array_shift($request);

			$param = ['_id' => new MongoDB\BSON\ObjectID($key)];
			$bulk->delete($param);
			$manager->executeBulkWrite('demodata.'.$collection, $bulk);
			echo json_encode(['success' => true]);
			break;
		case 'POST':
			$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
			$params = [];

			foreach ($_POST as $key => $value) {
				$params[$key] = $value;
			}
			$_id = (string)$bulk->insert($params);
			//var_dump($_id);
                        
                        try {
                            $writeConcern = new MongoDB\Driver\WriteConcern(MongoDB\Driver\WriteConcern::MAJORITY, 100);
                            $result = $manager->executeBulkWrite('demodata.'.$collection, $bulk, $writeConcern);
                            
                            header('HTTP/1.1 201 Created');
                            header('Location: http://demo.local/api/v1.0/drivers/'.$_id);
                            //header('Content-type:application/json;charset=utf-8');
                            exit;
                        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
                            header('Content-type:application/json;charset=utf-8');
                            echo json_encode(['success' => false]);
                        }
			break;
		case 'PUT':
			$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
			$params = [];

			parse_str(file_get_contents("php://input"),$_POST);

			foreach ($_POST as $key => $value) {
				if ($key == '_id') {
					$_id = $value;

					unset($params[$key]);
				} else {
					$params[$key] = $value;
				}
			}
			$bulk->update(['_id' => new MongoDB\BSON\ObjectId($_id)], ['$set'=> $params]);
			$manager->executeBulkWrite('demodata.'.$collection, $bulk);
			echo json_encode(['success' => true]);
			break;
		case 'GET':
			//$request = [];
			if (isset($_GET['_id'])) {

				$params['_id'] = new MongoDB\BSON\ObjectID($_GET['_id']);

				// Query Class
				$query = new MongoDB\Driver\Query($params);

				// Output of the executeQuery will be object of MongoDB\Driver\Cursor class
				$cursor = $manager->executeQuery('demodata.'.$collection, $query);
				$items = $cursor->toArray();
				if (count($items) > 0) {
					header('Content-type:application/json;charset=utf-8');
					echo $output = json_encode($items);
				} else {
					echo json_encode(['error' => 'No data found']);
				}
			} else {

				$params = [];
				foreach ($driver as $field) {
					if (isset($_GET[$field]) && $_GET[$field] != '') {
						$params[$field] = $_GET[$field];
					}
				}
				// Query Class
				$query = new MongoDB\Driver\Query($params);

				// Output of the executeQuery will be object of MongoDB\Driver\Cursor class
				$cursor = $manager->executeQuery('demodata.'.$collection, $query);

				$results = $cursor->toArray();

				if (count($results) > 0) {
					header('Content-type:application/json;charset=utf-8');
					echo json_encode($results);
				} else {
					echo json_encode(['error' => 'No data found']);
				}
			}
			break;
		default:
			echo json_encode(['error' => 'unknown action or not allowed']);
			break;

	}
} else {
	echo json_encode(['error' => 'Invalid call']);
}
