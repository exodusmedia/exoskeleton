<?php
/**
 * Reconnecting PDO Class-Extender
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 */
namespace Exo\Database;
use PDO;
class RPDO
{
	protected $connection;

	public function __construct($dsn, $username = NULL, $password = NULL, $driver_options = NULL)
	{
		$this->connection = new PDO($dsn, $username, $password, $driver_options);
		$this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
		$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function __call($method, $arguments)
	{
		try
		{
			return call_user_func_array(array($this->connection, $method), $arguments);
		} catch (Exception $e) {
			throw Exception($e);
		}
	}
}
