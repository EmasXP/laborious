<?php
// https://phpunit.readthedocs.io/en/latest/assertions.html

namespace Laborious\Tests;

use PHPUnit\Framework\TestCase;



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
		$user = new DummyUser($this->db);
		$user->email = "test@example.com";
		$user->save();

		$this->assertTrue(isset($user->id));
		$this->assertTrue($user->id !== null);
	}

	public function testHavePrimaryIdAfterSavedEmptyModel()
	{
		$user = new DummyUser($this->db);
		$user->save();

		$this->assertTrue(isset($user->id));
		$this->assertTrue($user->id !== null);
	}

	public function testHaveNotPrimaryIdBeforeSave()
	{
		$user = new DummyUser($this->db);
		$this->assertFalse(isset($user->id));
		$this->assertNull($user->id);
	}

	public function testNotNonSetParamsIsNullAfterSave()
	{
		$user = new DummyUser($this->db);
		$user->email = "test@example.com";
		$user->save();
		$this->assertNull($user->country_id);
	}

	public function testSameValueAfterSave()
	{
		$user = new DummyUser($this->db);
		$user->email = "foobar@example.com";
		$user->save();

		$this->assertEquals("foobar@example.com", $user->email);
	}

	public function testHaveSetParamsAfterSave()
	{
		$user = new DummyUser($this->db);
		$user->email = "test@example.com";
		$user->save();
		$this->assertTrue(isset($user->email));
		$this->assertTrue($user->email !== null);
	}

	public function testDoesNotHaveUndefinedFieldBeforeSave()
	{
		$user = new DummyUser($this->db);
		$this->assertFalse(isset($user->undefined_field));
	}

	public function testDoesNotHaveUndefinedFieldAfterSave()
	{
		$user = new DummyUser($this->db);
		$user->save();
		$this->assertFalse(isset($user->undefined_field));
	}

	public function testSetValues()
	{
		$user = new DummyUser($this->db);
		$user->setValues(array(
			"email" => "1234@example.com",
			"country_id" => 12,
		));

		$this->assertEquals("1234@example.com", $user->email);
		$this->assertEquals(12, $user->country_id);

		$user->save();

		$this->assertEquals("1234@example.com", $user->email);
		$this->assertEquals(12, $user->country_id);
	}

	public function testSetValuesWithExpectedValues()
	{
		$user = new DummyUser($this->db);
		$user->setValues(
			array(
				"email" => "98765@example.com",
				"country_id" => 12,
			),
			array(
				"email",
			)
		);

		$this->assertEquals("98765@example.com", $user->email);
		$this->assertTrue(isset($user->email));
		$this->assertNull($user->country_id);
		$this->assertFalse(isset($user->country_id));

		$user->save();

		$this->assertEquals("98765@example.com", $user->email);
		$this->assertTrue(isset($user->email));
		$this->assertNull($user->country_id);
		$this->assertFalse(isset($user->country_id));
	}

}
