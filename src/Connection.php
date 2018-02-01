<?php

namespace Laborious;


class Connection extends \PDO {

	public function _build_where($where)
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


	public function execute_update($table, $data, $where)
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

		$sql .= $this->_build_where($where);

		return $this->exec($sql);
	}


	public function execute_insert($table, $data)
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
			$values[] = $this->quote($value);
		}
		$sql .= implode(", ", $values);

		$sql .= ")";

		$this->query($sql);

		return $this->lastInsertId();
	}


	public function execute_delete($table, $where)
	{
		$sql = "DELETE FROM `".$table."` ";

		$sql .= $this->_build_where($where);

		return $this->exec($sql);
	}


	public function execute_select($table, $where, $fetch_one = false)
	{
		$sql = "SELECT * FROM `".$table."` ";

		$sql .= $this->_build_where($where);

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
