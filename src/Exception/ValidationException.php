<?php

namespace Laborious/Exception;


class ValidationException extends Exception {

	protected $_errors;


	public function __construct($message = "", $errors, Throwable $previous = NULL)
	{
		parent::__construct($message, 0, $previous);

		$this->_errors = $errors;
	}


	public function errors()
	{
		if ( ! is_array($this->_errors))
		{
			$this->_errors = array($this->_errors);
		}

		return $this->_errors;
	}

}
