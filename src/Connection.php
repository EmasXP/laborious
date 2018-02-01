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
			$wheres[] = "`".$key."` = ".$this->quote($val);
		}

		return " WHERE ".implode(" AND ", $wheres);
	}


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
			$updates[] = "`".$key."` = ".$this->quote($val);
		}
		$sql .= implode(", ", $updates);

		$sql .= $this->buildWhere($where);

		return $this->exec($sql);
	}


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
			$values[] = $this->quote($val);
		}
		$sql .= implode(", ", $values);

		$sql .= ")";

		$this->query($sql);

		return $this->lastInsertId();
	}


	public function executeDelete($table, $where)
	{
		$sql = "DELETE FROM `".$table."` ";

		$sql .= $this->buildWhere($where);

		return $this->exec($sql);
	}


	public function executeSelect($table, $where, $fetch_one = false)
	{
		$sql = "SELECT * FROM `".$table."` ";

		$sql .= $this->buildWhere($where);

		if ($fetch_one)
		{
			$sql .= " LIMIT 1";
		}

		$data = $this->query($sql);

		if ( ! $fetch_one)
		{
			return $data;
		}

		return $data->fetch();
	}

}
