<?php
// https://phpunit.readthedocs.io/en/latest/assertions.html

namespace Laborious\Tests;

use PHPUnit\Framework\TestCase;



final class RelationsTest extends TestCase {

	public function setUp()
	{
		$this->db = new \Laborious\Connection("sqlite::memory:");
		$this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

		$this->db->query("CREATE TABLE `users` (
			`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
			`username` TEXT,
			`email` TEXT,
			`firstname` TEXT,
			`lastname` TEXT,
			`country_id` INTEGER
		)");
	}


	public function testGetSelectString()
	{
		$string = (new DummyUser($this->db))
			->getSelectString("users");

		$this->assertEquals(
			"`users`.`id` AS `users:id`, `users`.`email` AS `users:email`, `users`.`country_id` AS `users:country_id`",
			$string
		);
	}


	public function testGetSelectStringWithNullTable()
	{
		$string = (new DummyUser($this->db))
			->getSelectString(null);

		$this->assertEquals(
			"`id`, `email`, `country_id`",
			$string
		);
	}


	public function testGetSelectStringWithAsPrefix()
	{
		$string = (new DummyUser($this->db))
			->getSelectString("users", "hello");

		$this->assertEquals(
			"`users`.`id` AS `hello:id`, `users`.`email` AS `hello:email`, `users`.`country_id` AS `hello:country_id`",
			$string
		);
	}


	public function testGetSelectStringWithNullTableAndAsPrefix()
	{
		$string = (new DummyUser($this->db))
			->getSelectString(null, "hello");

		$this->assertEquals(
			"`id` AS `hello:id`, `email` AS `hello:email`, `country_id` AS `hello:country_id`",
			$string
		);
	}

}