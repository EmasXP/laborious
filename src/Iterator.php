<?php

namespace Laborious;


class Iterator implements \Iterator {

	protected $db;
	protected $rows;
	protected $cache = array();
	protected $current;
	protected $classname;


	public function __construct($model, $rows)
	{
		$this->db = $model->_db;
		$this->rows = $rows;
		$this->classname = get_class($model);
	}


	public function rewind()
	{
		reset($this->cache);
		$this->next();
	}


	public function valid()
	{
		return $this->current !== false;
	}


	public function current()
	{
		return $this->current[1]; // Or "value"
	}


	public function key()
	{
		return $this->current[0]; // Or "key"
	}


	public function next()
	{
		$this->current = each($this->cache);

		if ($this->current !== false)
		{
			return;
		}

		$data = $this->rows->fetch();

		if ($data === false)
		{
			return;
		}

		$class = $this->classname;
		$this->cache[] = new $class($this->db, $data);

		$this->current = each($this->cache);
	}


	// TODO: as_array()
}
