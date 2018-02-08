<?php

namespace Laborious\Tests;

use PHPUnit\Framework\TestCase;


final class DatabaseExceptionsTest extends TestCase {

	public function setUp()
	{
		$this->db = new \Laborious\Connection("sqlite::memory:");
		$this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

		$this->db->query("CREATE TABLE `users` (
			`id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
			`username` TEXT,
			`email` TEXT,
			`firstname` TEXT,
			`lastname` TEXT
		)");
	}

	public function testDatabaseExceptionOnInvalidInsert()
	{
		$this->expectException(\Laborious\Exception\DatabaseException::class);

		$user = new DummyUser($this->db);
		$user->country_id = 12;
		$user->save();
	}

	public function testDatabaseExceptionOnInvalidUpdate()
	{
		$this->expectException(\Laborious\Exception\DatabaseException::class);

		$user = new DummyUser($this->db);
		$user->email = "test@example.com";
		$user->save();

		$user->country_id = 12;
		$user->save();
	}

	public function testDatabaseExceptionOnInvalidDatabase()
	{
		$this->expectException(\Laborious\Exception\DatabaseException::class);

		$db = new \Laborious\Connection("sqlite::memory:");

		$user = new DummyUser($db);
		$user->email = "test@example.com";
		$user->save();
	}

	public function testDatabaseExceptionOnFetchInvalidDatabase()
	{
		$this->expectException(\Laborious\Exception\DatabaseException::class);

		$db = new \Laborious\Connection("sqlite::memory:");

		$user = (new DummyUser($db))->fetch(1);
	}

	public function testDatabaseExceptionOnUpdateInvalidDatabase()
	{
		$this->expectException(\Laborious\Exception\DatabaseException::class);

		$db = new \Laborious\Connection("sqlite::memory:");

		$user = new DummyUser($this->db);
		$user->email = "test@example.com";
		$user->save();

		$user_again = new DummyUser($this->db);
		$user_again->id = $user->id;
		$user_again->email = "test@example.com";
		$user_again->save();
	}

	public function testDatabaseExceptionOnDeleteInvalidDatabase()
	{
		$this->expectException(\Laborious\Exception\DatabaseException::class);

		$db = new \Laborious\Connection("sqlite::memory:");

		$user = new DummyUser($this->db);
		$user->email = "test@example.com";
		$user->save();

		$user_again = new DummyUser($this->db);
		$user_again->id = $user->id;
		$user_again->delete();
	}

}
