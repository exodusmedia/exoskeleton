<?php
/**
 * Example Application
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 */

namespace Example;
use Exo\Application as App;
use Exo\Database\Connection;
class Application extends App
{
	static $restful = TRUE;

	public function get_index()
	{
		return $this->render('example/index');
	}

	public function get_database()
	{
		$db = new Connection();

		$sql = 'SELECT * FROM test';
		$query = $db->prepare($sql);
		$result = $query->execute();

		$this->data['results'] = $query->fetchAll();

		return $this->render('example/database');
	}

	public function get_response()
	{
		$response = $this->render('example/response');
		$response->content = 'Removed to reduce annoyance';
		$this->data['response'] = $response;
		return $this->render('example/response');
	}

	public function get_request()
	{
		$this->data['request'] = $this->request;
		return $this->render('example/request');
	}

	public function get_config()
	{
		return $this->render('example/config');
	}

	public function get_format()
	{
		$this->data = array(
			'example' => (object)array(
				'name' => 'Guillaume VanderEst',
				'email' => 'gui@exodus.io',
				'position' => 'Developer'

			)
		);
		return $this->render('example/format');
	}

	public function get_cache()
	{
		sleep(1);
		return $this->render('example/cache');
	}

	public function hello_world()
	{
		return 'Hello World';
	}
}
