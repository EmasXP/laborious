<?php
// https://phpunit.readthedocs.io/en/latest/assertions.html

namespace Laborious\Tests;

use PHPUnit\Framework\TestCase;



final class ModelTest extends TestCase {

	public function setUp()
	{
		$this->db = new \Laborious\Connection("sqlite::memory:");
		$this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
		$this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

		$this->db->query("CREATE TABLE `users` (
			`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
			`username` TEXT,
			`email` TEXT,
			`firstname` TEXT,
			`lastname` TEXT,
			`country_id` INTEGER
		)");
	}


	public function testNotLoadedBeforeInsert()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";

		$this->assertFalse($user->isLoaded());
	}


	public function testIsLoadedAfterInsert()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$this->assertTrue($user->isLoaded());
	}


	public function testIsLoadedAfterFind()
	{
		$this->db->query("INSERT INTO `users` (`username`) VALUES ('UserA')");

		$user = new DummyUser(
			$this->db,
			$this->db->query("SELECT * FROM `users` LIMIT 1")->fetch()
		);

		$this->assertTrue($user->isLoaded());
	}


	public function testIsLoadedAfterManualData()
	{
		$user = new DummyUser(
			$this->db,
			array(
				"id" => 123,
			)
		);

		$this->assertTrue($user->isLoaded());
	}


	public function testIsLoadedAfterSet()
	{
		$user = new DummyUser($this->db);

		$user->id = 1;

		$this->assertTrue($user->isLoaded());
	}


	public function testIsLoadedAfterSetRaw()
	{
		$user = new DummyUser($this->db);

		$user->setRaw("id", 1);

		$this->assertTrue($user->isLoaded());
	}


	public function testIsNotLoadedAfterManualData()
	{
		$user = new DummyUser(
			$this->db,
			array(
				"email" => "donald@duck.com",
			)
		);

		$this->assertFalse($user->isLoaded());
	}


	public function testIsNotLoadedAfterSet()
	{
		$user = new DummyUser($this->db);

		$user->email = "donald@duck.com";

		$this->assertFalse($user->isLoaded());
	}


	public function testIsNotLoadedAfterSetRaw()
	{
		$user = new DummyUser($this->db);

		$user->setRaw("email", "donald@duck.com");

		$this->assertFalse($user->isLoaded());
	}


	/**
	 * @expectedException \Laborious\Exception\LaboriousException
	 */
	public function testExceptionDeletingNonLoaded()
	{
		$user = new DummyUser($this->db);
		$user->delete();
	}


	/**
	 * @expectedException \Laborious\Exception\LaboriousException
	 */
	public function testExceptionReloadingNonLoaded()
	{
		$user = new DummyUser($this->db);
		$user->reload();
	}


	public function testLoadedAfterReload()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$user->reload();

		$this->assertTrue($user->isLoaded());
	}


	public function testSameValueAfterReload()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$user->reload();

		$this->assertEquals("donald@duck.com", $user->email);
	}


	public function testChangedValueBeforeReload()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$this->db->query(
			"UPDATE `users` SET `email` = 'daisy@duck.com' WHERE `id` = "
				.$this->db->quote($user->id)
		);

		$user->reload();

		$this->assertEquals("daisy@duck.com", $user->email);
	}


	public function testSameIdAfterReload()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$id = $user->id;

		$user->reload();

		$this->assertEquals($id, $user->id);
	}


	public function testSameAfterReloadMultipleRows()
	{
		$this->db->query("INSERT INTO `users` (`email`) VALUES ('UserA@test.com')");

		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$id = $user->id;

		$this->db->query("INSERT INTO `users` (`email`) VALUES ('UserB@test.com')");

		$user->reload();

		$this->assertEquals($id, $user->id);
		$this->assertEquals("donald@duck.com", $user->email);
	}


	public function testChangedOtherValueBeforeReload()
	{
		$user = new DummyUser($this->db);
		$user->email = "donald@duck.com";
		$user->save();

		$this->db->query(
			"UPDATE `users` SET `username` = 'Donald' WHERE `id` = "
				.$this->db->quote($user->id)
		);

		$user->reload();

		$this->assertEquals("Donald", $user->username);
	}

}