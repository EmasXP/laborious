<?php

namespace Laborious\Exception;


class DatabaseException extends \Exception {

	protected $error_code;


	public function __construct(
		$message,
		$code = 0,
		$error_code = null,
		Throwable $previous = null
	)
	{
		parent::__construct($message, $code, $previous);

		$this->error_code = $error_code;
	}


	public function errorCode()
	{
		return $this->errorCode;
	}

}
