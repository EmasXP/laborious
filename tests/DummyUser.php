<?php

namespace Laborious\Tests;


class DummyUser extends \Laborious\Model {

	protected static $_table = "users";

	protected static $_fields = array(
		"id",
		"email",
		"country_id",
	);

	public function getCountryId()
	{
		return $this->country_id;
	}
}
