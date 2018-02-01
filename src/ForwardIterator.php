<?php

namespace Laborious;


class ForwardIterator implements \Iterator {

	protected $db;
	protected $rows;
	protected $current;
	protected $classname;
	protected $i = 0;


	public function __construct($model, $rows)
	{
		$this->db = $model->_db;
		$this->rows = $rows;
		$this->classname = get_class($model);
	}


	public function rewind()
	{
	}


	public function valid()
	{
		return $this->current !== false;
	}


	public function current()
	{
		if ($this->current === null)
		{
			$this->next();
		}

		return $this->current;
	}


	public function key()
	{
		return $this->i;
	}


	public function next()
	{
		$data = $this->rows->fetch();

		if ($data === false)
		{
			$this->current = false;
			return;
		}

		$class = $this->classname;
		$this->current = new $class($this->db, $data);
		$this->i ++;
	}


	// TODO: as_array()
}
