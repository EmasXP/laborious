<?php

namespace Laborious;


class Connection extends \PDO {

	protected function buildWhere($where)
	{
		if (count($where) == 0)
		{
			return "";
		}

		$wheres = array();

		foreach ($where as $key => $val)
		{
			if ($val === null)
			{
				$wheres[] = "`".$key."` IS NULL";
			}
			else
			{
				$wheres[] = "`".$key."` = ".$this->quote($val);
			}
		}

		return " WHERE ".implode(" AND ", $wheres);
	}


	/**
	 * @return int Number of affected rows.
	 */
	public function executeUpdate($table, $data, $where)
	{
		if (count($data) == 0)
		{
			return false;
		}

		$sql = "UPDATE `".$table."` SET ";

		$updates = array();
		foreach ($data as $key => $val)
		{
			if ($val === null)
			{
				$updates[] = "`".$key."` = NULL";
			}
			else
			{
				$updates[] = "`".$key."` = ".$this->quote($val);
			}
		}
		$sql .= implode(", ", $updates);

		$sql .= $this->buildWhere($where);

		$result = $this->exec($sql);

		if ($result === false)
		{
			$error = $this->errorInfo();
			throw new Exception\DatabaseException(
				$error[2],
				$error[1],
				$error[0]
			);
		}

		return $result;
	}


	/**
	 * @return string The ID of the inserted row or sequence value.
	 */
	public function executeInsert($table, $data)
	{
		if (count($data) == 0)
		{
			return false;
		}

		$sql = "INSERT INTO `".$table."` (";

		$columns = array();
		foreach (array_keys($data) as $key)
		{
			$columns[] = "`".$key."`";
		}
		$sql .= implode(", ", $columns);

		$sql .= ") VALUES (";

		$values = array();
		foreach (array_values($data) as $val)
		{
			if ($val === null)
			{
				$values[] = "NULL";
			}
			else
			{
				$values[] = $this->quote($val);
			}
		}
		$sql .= implode(", ", $values);

		$sql .= ")";

		$result = $this->query($sql);

		if ($result === false)
		{
			$error = $this->errorInfo();
			throw new Exception\DatabaseException(
				$error[2],
				$error[1],
				$error[0]
			);
		}

		return $this->lastInsertId();
	}

	/**
	 * @return int Number of affected rows.
	 */
	public function executeDelete($table, $where)
	{
		$sql = "DELETE FROM `".$table."` ";

		$sql .= $this->buildWhere($where);

		$result = $this->exec($sql);

		if ($result === false)
		{
			$error = $this->errorInfo();
			throw new Exception\DatabaseException(
				$error[2],
				$error[1],
				$error[0]
			);
		}

		return $result;
	}


	/**
	 * @return PDOStatement|mixed Depending on if $fetch_one is true or false.
	 */
	public function executeSelect($table, $where, $fetch_one = false)
	{
		$sql = "SELECT * FROM `".$table."` ";

		$sql .= $this->buildWhere($where);

		if ($fetch_one)
		{
			$sql .= " LIMIT 1";
		}

		$data = $this->query($sql);

		if ($data === false)
		{
			$error = $this->errorInfo();
			throw new Exception\DatabaseException(
				$error[2],
				$error[1],
				$error[0]
			);
		}

		if ( ! $fetch_one)
		{
			return $data;
		}

		return $data->fetch();
	}

}
