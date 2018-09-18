<?php

namespace Laborious\Tests;


class DummyCountry extends \Laborious\Model {

	protected static $_table = "countries";

	protected static $_fields = array(
		"id",
		"name",
	);
}
