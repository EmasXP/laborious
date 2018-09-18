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

		$this->db->query("CREATE TABLE `countries` (
			`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
			`name` TEXT
		)");

		$this->db->query("INSERT INTO `countries` (`name`) VALUES ('Nowhere')");
		$nowhere_id = $this->db->lastInsertId();

		$this->db->query("INSERT INTO `countries` (`name`) VALUES ('Somewhere')");
		$somewhere_id = $this->db->lastInsertId();

		$this->db->query("INSERT INTO `users` (`username`, `country_id`) VALUES ('UserA', '$nowhere_id')");
		$this->db->query("INSERT INTO `users` (`username`, `country_id`) VALUES ('UserB', '$nowhere_id')");
		$this->db->query("INSERT INTO `users` (`username`, `country_id`) VALUES ('UserC', '$somewhere_id')");
		$this->db->query("INSERT INTO `users` (`username`, `country_id`) VALUES ('UserD', '$somewhere_id')");
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


	public function testEagerLoadFromString()
	{
		$sql = "
		SELECT
			`users`.*,
			".(new DummyCountry($this->db))->getSelectString("countries")."
		FROM `users`
		LEFT JOIN `countries` ON `countries`.`id` = `users`.`country_id`
		LIMIT 1
		";

		$user = new DummyUser(
			$this->db,
			$this->db->query($sql)->fetch()
		);

		$this->assertTrue($user->id !== null);

		$country = $user->loadModel(DummyCountry::class, "countries");

		$this->assertTrue($country->id !== null);
	}


	public function testEagerLoadFromEmptyModel()
	{
		$sql = "
		SELECT
			`users`.*,
			".(new DummyCountry($this->db))->getSelectString("countries")."
		FROM `users`
		LEFT JOIN `countries` ON `countries`.`id` = `users`.`country_id`
		LIMIT 1
		";

		$user = new DummyUser(
			$this->db,
			$this->db->query($sql)->fetch()
		);

		$country = $user->loadModel(new DummyCountry($this->db), "countries");

		$this->assertTrue($country->id !== null);
	}


	/**
	 * @expectedException \Laborious\Exception\LaboriousException
	 */
	public function testEagerLoadFromEmptyStdClass()
	{
		$sql = "
		SELECT
			`users`.*,
			".(new DummyCountry($this->db))->getSelectString("countries")."
		FROM `users`
		LEFT JOIN `countries` ON `countries`.`id` = `users`.`country_id`
		LIMIT 1
		";

		$user = new DummyUser(
			$this->db,
			$this->db->query($sql)->fetch()
		);

		$user->loadModel(new \stdClass, "countries");
	}


	/**
	 * @expectedException \Laborious\Exception\LaboriousException
	 */
	public function testEagerLoadFromInvalid()
	{
		$sql = "
		SELECT
			`users`.*,
			".(new DummyCountry($this->db))->getSelectString("countries")."
		FROM `users`
		LEFT JOIN `countries` ON `countries`.`id` = `users`.`country_id`
		LIMIT 1
		";

		$user = new DummyUser(
			$this->db,
			$this->db->query($sql)->fetch()
		);

		$user->loadModel(123, "countries");
	}

}