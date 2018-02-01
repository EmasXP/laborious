<?php

namespace Laborious;


class Model {

	protected static $_fields = array();
	protected static $_primary = "id";

	public $_db;
	protected $_values;
	protected $_original_values = array();



	public static function build_select_columns($column_prefix = null, $as_prefix = null)
	{
		// TODO
	}


	public function __construct($db, $values = array(), $from_prefix = null)
	{
		// TODO: $from_prefix

		$this->_db = $db;
		$this->_values = $values;
	}


	public function set($name, $value)
	{
		if (in_array($name, static::$_fields))
		{
			if (isset($this->_original_values[$name]))
			{
				if (
					isset($this->_values[$name]) // If a field that does not exist in _values is changed, we cannot know if it's changed back.
					&& $this->_original_values[$name] === $value
				)
				{
					unset($this->_original_values[$name]);
				}
			}
			elseif ( ! isset($this->_values[$name]))
			{
				$this->_original_values[$name] = null;
			}
			elseif ($this->_values[$name] !== $value)
			{
				$this->_original_values[$name] = $this->_values[$name];
			}
		}

		$this->_values[$name] = $value;
	}


	public function get($name, $fallback = null)
	{
		if (isset($this->_values[$name]))
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

		if (in_array($name, static::$_fields))
		{
			return null;
		}

		trigger_error(
			"Undefined property: "
				.get_class($trace[0]["object"])
				."::$"
				.$name
				." in ".$trace[0]["file"]
				." on line ".$trace[0]["line"],
			E_USER_NOTICE
		);
	}


	public function __isset($name)
	{
		// This should only check _values, since a _field that does not have a row in _values
		// is NULL, and NULL values in isset() is FALSE.
		return isset($this->_values[$name]);
	}


	public function __unset($name)
	{
		unset($this->_values[$name]);
		unset($this->_original_values[$name]);
	}


	public function set_values($values, $fields = null)
	{
		foreach ($values as $key => $val)
		{
			if ($fields === null || in_array($key, $fields))
			{
				$this->set($key, $val);
			}
		}
	}


	public function validate($name, $value)
	{
		return null;
	}


	public function validate_changed()
	{
		return $this->validate_these($this->get_changed());
	}


	public function validate_existing()
	{
		return $this->validate_these($this->get_existing());
	}


	public function validate_all()
	{
		return $this->validate_these(static::$_fields);
	}


	public function validate_these($fields)
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


	public function filter_changed()
	{
		$this->filter_these($this->get_changed());
	}


	public function filter_existing()
	{
		$this->filter_these($this->get_existing());
	}


	public function filter_all()
	{
		$this->filter_these(static::$_fields);
	}


	public function filter_these($fields)
	{
		foreach ($fields as $field)
		{
			$this->set(
				$field,
				$this->filter($field, $this->get($field))
			);
		}
	}


	public function get_changed()
	{
		return array_keys($this->_original_values);
	}


	public function get_existing()
	{
		$existing = array();

		foreach (static::$_fields as $field)
		{
			if (isset($this->_values[$field]))
			{
				$existing[] = $field;
			}
		}

		return $existing;
	}


	public function clear_changed()
	{
		$this->_original_values = array();
	}


	public function iterator($rows)
	{
		return new Iterator($this, $rows);
	}


	public function rows($rows)
	{
		return new ForwardIterator($this, $rows);
	}


	public function get_keys()
	{
		return array_keys($this->_values);
	}


	public function save($validate_these = null, $save_all_fields = false)
	{
		$has_primary = $this->get(static::$_primary) !== null;


		// Filters *********************************************************************************

		if ($has_primary)
		{
			$this->filter_existing();
		}
		else
		{
			$this->filter_all();
		}

		// *****************************************************************************************


		// Validation ******************************************************************************

		if ($validate_these === null)
		{
			if ($has_primary)
			{
				$errors = $this->validate_existing();
			}
			else
			{
				$errors = $this->validate_all();
			}
		}
		else
		{
			$errors = $this->validate_these($validate_these);
		}

		if (count($errors) > 0)
		{
			throw new Laborious\Exception\ValidationException(
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
			$changed = $this->get_changed();
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
			return;
		}


		// Executing query *************************************************************************

		if ($has_primary)
		{
			$this->_db->execute_update(
				static::$_table,
				$data,
				array(
					self::$_primary => $this->get(self::$_primary),
				)
			);
		}
		else
		{
			$insert_id = $this->_db->execute_insert(
				self::$_table,
				$data
			);

			$this->set(self::$_primary, $insert_id);
		}

		// *****************************************************************************************


		$this->clear_changed();
	}


	public function delete()
	{
		$primary_id = $this->get(self::$_primary);

		if ($primary_id === null)
		{
			return false;
		}

		$resp = $this->_db->execute_delete(
			self::$_table,
			array(
				self::$_primary => $primary_id,
			)
		);

		$this->set(self::$_primary, null);

		return $resp;
	}


	public function fetch($primary_id)
	{
		$class = get_class($this);
		return new $class(
			$this->_db,
			$this->_db->execute_select(
				static::$_table,
				array(
					static::$_primary => $primary_id,
				),
				true
			)
		);
	}

}
