<?php
// https://phpunit.readthedocs.io/en/latest/assertions.html

namespace Laborious\Tests;

use PHPUnit\Framework\TestCase;


class User extends \Laborious\Model {

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


final class InsertTest extends TestCase {

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

	public function testHavePrimaryIdAfterSave()
	{
		$user = new User($this->db);
		$user->email = "test@example.com";
		$user->save();

		$this->assertTrue(isset($user->id));
		$this->assertTrue($user->id !== null);
	}

	public function testHaveNotPrimaryIdBeforeSave()
	{
		$user = new User($this->db);
		$this->assertFalse(isset($user->id));
	}

	public function testHaveNotNonSetParamsAfterSave()
	{
		$user = new User($this->db);
		$user->email = "test@example.com";
		$user->save();
		$this->assertFalse(isset($user->username));
		$this->assertFalse(isset($user->country_id));
	}

	public function testHaveSetParamsAfterSave()
	{
		$user = new User($this->db);
		$user->email = "test@example.com";
		$user->save();
		$this->assertTrue(isset($user->email));
		$this->assertTrue($user->email !== null);
	}

}
