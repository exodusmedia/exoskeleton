<?php
/**
 * ExoSkeleton Database Connection
 * PDO wrapper with some additional helper functions
 * @author Guillaume VanderEst <guillaume@vanderest.org>
 */

namespace Exo\Database;

use stdClass;
use Exo\Database\RPDO as PDO;
use Exo\Database;
use Exo\Environment;
use Exo\Exception;

class Connection extends PDO
{
	/**
	 * Instantiate the connection
	 * @param string $id
	 * @see /app/conf/databases.php for configuration
	 */
	public function __construct($id = NULL)
	{
		// if there is no $id specified, try to detect it based on environment
		if (is_null($id))
		{
			$env = Environment::get();
			$id = $env->database;
		}

		$database = Database::get($id);
		$dsn = $database->type . ':dbname=' . $database->name . ';host=' . $database->host;
		parent::__construct($dsn, $database->user, $database->password);
	}

	/**
	 * Perform a query
	 * @param string $sql
	 * @param array $values (optional)
	 * @return bool
	 */
	public function query($sql, $values = array())
	{
		$query = $this->prepare($sql);
		$result = $query->execute($values);
		return $result;
	}

	/**
	 * Get a where filter, based on options array
	 * @param array $options
	 * @return object with 'wheres' and 'values'
	 */
	public function get_where_filters($filters = array())
	{
		if (is_numeric($filters)) {
			$filters = array('id' => $filters);
		} elseif (is_string($filters)) {
			$filters = array('slug' => $filters);
		}

		$values = array();
		$wheres = array();

		foreach ($filters as $field => $value)
		{
			if (is_array($value))
			{
				switch (@$value[1])
				{
				case 'starts_with':
					$wheres[] = sprintf('%1$s LIKE :%1$s', $value[0]);
					$values[':' . $value[0]] = $value[2] . '%';
					break;
				case 'contains':
					$wheres[] = sprintf('%1$s LIKE :%1$s', $value[0]);
					$values[':' . $value[0]] = '%' . $value[2] . '%';
					break;
				case 'ends_with':
					$wheres[] = sprintf('%1$s LIKE :%1$s', $value[0]);
					$values[':' . $value[0]] = '%' . $value[2];
					break;
				default:
					$recurse = $this->get_where_filters($value);
					$wheres[] = $recurse->wheres;
					$return->values = array_merge($return->values, $recurse->values);
				}
			} else {
				$wheres[] = sprintf('%1$s = :%1$s', $field);
				$values[':' . $field] = $value;
			}
		}

		$return = new stdClass;
		$return->string = '(' . implode(') AND (', $wheres) . ')';
		$return->values = $values;
		return $return;
	}

	public function insert($table, $data, $options = array())
	{
		// TODO: allow options array to specify fields

		$parts = array();
		$parts[] = sprintf('INSERT INTO %s', $table);

		if (count($data) == 0)
		{
			return TRUE;
		}

		$updates = array();
		foreach ($data as $key => $value)
		{
			$updates[] = sprintf('%1$s = :%1$s', $key);
			$values[':' . $key] = $value;
		}
		$parts[] = 'SET ' . implode(', ', $updates);

		$sql = implode(' ', $parts);

		$query = $this->prepare($sql);
		$result = $query->execute($values);

		if ($result)
		{
			return $this->lastInsertId();
		}
		return FALSE;
	}

	public function get_non_array_options($options)
	{
		$field = is_numeric($options) ? 'id' : 'slug';
		$options = array(
			'where' => array($field => $options),
			'amount' => 1
		); 
		return $options;
	}

	public function update($table, $options, $data)
	{
		// TODO: make 'amount' option work
		$parts = array();
		$parts[] = sprintf('UPDATE %s', $table);

		if (count($data) == 0)
		{
			return TRUE;
		}

		if (!is_array($options)) 
		{ 
			$options = $this->get_non_array_options($options);
		}

		$updates = array();
		foreach ($data as $key => $value)
		{
			$updates[] = sprintf('%1$s = :%1$s', $key);
			$values[':' . $key] = $value;
		}
		$parts[] = 'SET ' . implode(', ', $updates);

		if ($options['where'] !== NULL)
		{

			$where = $this->get_where_filters($options['where']);
			if (!empty($where->string))
			{
				$parts[] = 'WHERE ' . $where->string;
				$values = array_merge($values, $where->values);
			}
		}

		$sql = implode(' ', $parts);

		$query = $this->prepare($sql);
		$result = $query->execute($values);

		if ($result)
		{
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Select one result from a query
	 * @param string $table
	 * @param array $options (optional)
	 * @return object or NULL on failure
	 */
	public function select_one($table, $options = array())
	{
		if (is_array($options))
		{
			$options = array_merge($options, array(
				'amount' => 1
			));
		}
		return $this->select($table, $options);
	}

	/**
	 * Delete
	 * @param string $table
	 * @param array $options (optional) array(
	 * )
	 */
	public function delete($table, $options = array())
	{
		if (!is_array($options)) 
		{ 
			$options = $this->get_non_array_options($options);
		}

		$options = array_merge(array(
			'where' => null,
			'amount' => null,
			'fields' => null
		), $options);

		$values = array();

		$parts = array();
		$parts[] = sprintf('DELETE FROM %s', $table);

		if ($options['where'] !== NULL)
		{
			$where = $this->get_where_filters($options['where']);
			if (!empty($where->string))
			{
				$parts[] = 'WHERE ' . $where->string;
				$values = array_merge($values, $where->values);
			}
		}

		$sql = implode(' ', $parts);

		$query = $this->prepare($sql);
		return $query->execute($values);
	}

	/**
	 * Perform a select
	 * @param string $table
	 * @param array $options (optional) array(
	 * )
	 */
	public function select($table, $options = array())
	{
		if (!is_array($options)) 
		{ 
			$options = $this->get_non_array_options($options);
		}

		$options = array_merge(array(
			'where' => null,
			'amount' => null,
			'offset' => null,
			'fields' => null
		), $options);

		$values = array();

		$parts = array();
		$parts[] = sprintf('SELECT %s FROM %s', 
			($options['fields'] === null ? '*' : implode(',', $options['fields'])),
			$table
		);

		if ($options['where'] !== NULL)
		{
			$where = $this->get_where_filters($options['where']);
			if (!empty($where->string))
			{
				$parts[] = 'WHERE ' . $where->string;
				$values = array_merge($values, $where->values);
			}
		}

		$sql = implode(' ', $parts);

		if ($options['amount'] == 1)
		{
			return $this->get_one($sql, $values);
		}
		return $this->get_all($sql, $values);
	}

	/**
	 * Get all records from a query
	 * @param string $sql
	 * @param array $values (optional);
	 * @return array of objects
	 */
	public function get_all($sql, $values = array())
	{
		$query = $this->prepare($sql);
		$result = $query->execute($values);
		if ($result)
		{
			$results = $query->fetchAll();
			return $results;
		}
		return null;
	}

	/**
	 * Get One Record from a Query
	 * @param string $sql
	 * @param array $values (optional);
	 * @return object or null
	 */
	public function get_one($sql, $values = array())
	{
		$query = $this->prepare($sql);
		$result = $query->execute($values);
		if ($result)
		{
			return $query->fetch();
		}
		return null;
	}
}
