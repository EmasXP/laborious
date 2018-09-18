<?php

namespace Laborious;



class Model {

	protected static $_fields = array();
	protected static $_primary = "id";

	public $_db;
	protected $_values;
	protected $_original_values = array();


	public function __construct($db, $values = array())
	{
		$this->_db = $db;
		$this->_values = $values;
	}


	public function set($name, $value)
	{
		if (in_array($name, static::$_fields))
		{
			if (array_key_exists($name, $this->_original_values))
			{
				/*
				 * Checks to see if the value is the same as the original value.
				 * We cannot tell if the value has changed back to it's orignal
				 * value if the original value is NonExisting.
				 */
				if (
					! $this->_original_values[$name] instanceof Internal\NonExisting
					&& $this->_original_values[$name] === $value
				)
				{
					unset($this->_original_values[$name]);
				}
			}

			/*
			 * We do not know about the this parameter and the previous value of
			 * it.
			 */
			elseif ( ! array_key_exists($name, $this->_values))
			{
				$this->_original_values[$name] = new Internal\NonExisting;
			}

			// The value has changed
			elseif ($this->_values[$name] !== $value)
			{
				$this->_original_values[$name] = $this->_values[$name];
			}
		}

		$this->_values[$name] = $value;
		return $this;
	}


	public function setRaw($name, $value)
	{
		$this->_values[$name] = $value;
		return $this;
	}


	public function get($name, $fallback = null)
	{
		if (array_key_exists($name, $this->_values))
		{
			return $this->_values[$name];
		}

		return $fallback;
	}


	public function __set($name, $value)
	{
		return $this->set($name, $value);
	}


	public function __get($name)
	{
		if (array_key_exists($name, $this->_values))
		{
			return $this->_values[$name];
		}

		if (
			$this->get(static::$_primary) === null
			&& in_array($name, static::$_fields)
		)
		{
			return null;
		}

		$trace = debug_backtrace();

		trigger_error(
			"Undefined property: "
				.get_class($trace[0]["object"])
				."::$"
				.$name
				." in ".$trace[0]["file"]
				." on line ".$trace[0]["line"],
			E_USER_NOTICE
		);

		return null;
	}


	public function __isset($name)
	{
		return isset($this->_values[$name]);
	}


	public function __unset($name)
	{
		unset($this->_values[$name]);
		unset($this->_original_values[$name]);
	}


	public function setValues($values, $fields = null)
	{
		foreach ($values as $key => $val)
		{
			if ($fields === null || in_array($key, $fields))
			{
				$this->set($key, $val);
			}
		}

		return $this;
	}


	public function setRawValues($values, $fields = null)
	{
		foreach ($values as $key => $val)
		{
			if ($fields === null || in_array($key, $fields))
			{
				$this->_values[$key] = $val;
			}
		}

		return $this;
	}


	public function validate($name, $value)
	{
		return null;
	}


	public function validateChanged()
	{
		return $this->validateThese($this->getChanged());
	}


	public function validateExisting()
	{
		return $this->validateThese($this->getExisting());
	}


	public function validateAll()
	{
		return $this->validateThese(static::$_fields);
	}


	public function validateThese($fields)
	{
		$out = array();

		foreach ($fields as $field)
		{
			$valid = $this->validate($field, $this->get($field));
			if ($valid !== null)
			{
				$out[$field] = $valid;
			}
		}

		return $out;
	}


	public function filter($name, $value)
	{
		return $value;
	}


	public function filterChanged()
	{
		$this->filterThese($this->getChanged());
	}


	public function filterExisting()
	{
		$this->filterThese($this->getExisting());
	}


	public function filterAll()
	{
		$this->filterThese(static::$_fields);
	}


	public function filterThese($fields)
	{
		foreach ($fields as $field)
		{
			$this->set(
				$field,
				$this->filter($field, $this->get($field))
			);
		}
	}


	public function getChanged()
	{
		return array_keys($this->_original_values);
	}


	public function getExisting()
	{
		$existing = array();

		foreach (static::$_fields as $field)
		{
			if (array_key_exists($field, $this->_values))
			{
				$existing[] = $field;
			}
		}

		return $existing;
	}


	public function clearChanged()
	{
		$this->_original_values = array();
		return $this;
	}


	public function iterator($rows)
	{
		return new Iterator($this, $rows);
	}


	public function rows($rows)
	{
		return new ForwardIterator($this, $rows);
	}


	public function getKeys()
	{
		if ($this->get(static::$_primary) !== null)
		{
			return array_keys($this->_values);
		}

		return array_unique(
			array_merge(
				static::$_fields,
				array_keys($this->_values)
			)
		);
	}


	public function save($validate_these = null, $save_all_fields = false)
	{
		$has_primary = $this->get(static::$_primary) !== null;


		// Filters *********************************************************************************

		if ($has_primary)
		{
			$this->filterExisting();
		}
		else
		{
			$this->filterAll();
		}

		// *****************************************************************************************


		// Validation ******************************************************************************

		if ($validate_these === null)
		{
			if ($has_primary)
			{
				$errors = $this->validateExisting();
			}
			else
			{
				$errors = $this->validateAll();
			}
		}
		else
		{
			$errors = $this->validateThese($validate_these);
		}

		if (count($errors) > 0)
		{
			throw new Exception\ValidationException(
				"Error when validating ".self::class,
				$errors
			);
		}

		// *****************************************************************************************


		// Fetching data for INSERT or UPDATE ******************************************************

		$data = array();
		if ($save_all_fields)
		{
			foreach (static::$_fields as $field)
			{
				if ($field != static::$_primary)
				{
					$data[$field] = $this->get($field);
				}
			}
		}
		else
		{
			$changed = $this->getChanged();
			if (count($changed) > 0)
			{
				foreach ($changed as $field)
				{
					if ($field != static::$_primary)
					{
						$data[$field] = $this->get($field);
					}
				}
			}
		}

		// *****************************************************************************************


		if ($has_primary && count($data) == 0)
		{
			return $this;
		}


		// Executing query *************************************************************************

		if ($has_primary)
		{
			$this->_db->executeUpdate(
				static::$_table,
				$data,
				array(
					self::$_primary => $this->get(self::$_primary),
				)
			);
		}
		else
		{
			$insert_id = $this->_db->executeInsert(
				static::$_table,
				$data
			);

			$this->set(self::$_primary, $insert_id);
		}

		// *****************************************************************************************


		$this->clearChanged();
		return $this;
	}


	public function delete()
	{
		$primary_id = $this->get(self::$_primary);

		if ($primary_id === null)
		{
			throw new Exception\LaboriousException(
				"The model is not loaded."
			);
		}

		$this->_db->executeDelete(
			self::$_table,
			array(
				self::$_primary => $primary_id,
			)
		);

		$this->set(self::$_primary, null);

		return $this;
	}


	public function fetch($primary_id)
	{
		$class = get_class($this);
		return new $class(
			$this->_db,
			$this->_db->executeSelect(
				static::$_table,
				array(
					static::$_primary => $primary_id,
				),
				true
			)
		);
	}


	public function isLoaded()
	{
		return ($this->get(static::$_primary) !== null);
	}


	public function getSelectString($table, $as_prefix = null)
	{
		$columns = array();

		foreach (static::$_fields as $field)
		{
			$select = "";

			if ($table !== null)
			{
				$select .= "`$table`.";
			}

			$select .= "`$field`";

			if ($as_prefix !== null)
			{
				$select .= " AS `$as_prefix:$field`";
			}
			elseif ($table !== null)
			{
				$select .= " AS `$table:$field`";
			}

			$columns[] = $select;
		}

		return implode(", ", $columns);
	}


	/**
	 * This is the fastest implementation of loadModel() when the $model is a string.
	 *
	 * @param string $model
	 * @param string $prefix
	 * @return \Laborious\Model
	 */
	protected function loadModelFromString($model, $prefix)
	{
		$prefix .= ":";
		$prefix_length = strlen($prefix);
		$data = array();

		foreach ($this->_values as $key => $val)
		{
			if (substr($key, 0, $prefix_length) === $prefix)
			{
				$data[substr($key, $prefix_length)] = $val;
			}
		}

		return new $model($this->_db, $data);
	}


	/**
	 * This is the fastest implementation of loadModel() when the $model is an instance of Model.
	 *
	 * @param \Laborious\Model $model
	 * @param string $prefix
	 * @return \Laborious\Model
	 */
	protected function loadModelFromObject($model, $prefix)
	{
		$prefix .= ":";
		$prefix_length = strlen($prefix);

		foreach ($this->_values as $key => $val)
		{
			if (substr($key, 0, $prefix_length) === $prefix)
			{
				$model->setRaw(
					substr($key, $prefix_length),
					$val
				);
			}
		}

		return $model;
	}


	/**
	 * Load a related model by prefix.
	 *
	 * @param string|\Laborious\Model $model
	 * @param string $prefix
	 * @return \Laborious\Model
	 */
	public function loadModel($model, $prefix)
	{
		if (is_string($model))
		{
			return $this->loadModelFromString($model, $prefix);
		}

		elseif ($model instanceof Model)
		{
			return $this->loadModelFromObject($model, $prefix);
		}

		throw new Exception\LaboriousException(
			"\$model must be a Model or a string."
		);
	}

}
