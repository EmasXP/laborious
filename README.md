# Laborious

Laborious is a micro PHP database model layer. It has two main goals:

* Do as little around-stuff as possible
* Be extremely flexible


It's built for applications with a high load, and we want to spend as little resources as possible for creating abstractions during run time. We want to have the abstractions when we need it. 

You can see the model layer as a complement to your database result, for example when you need more logic (DRY) or want to automate filters and validation. The models does not care about how or when it gets its data. You can create objects from the PDO result when you find that you need it.

Some things in laborious might not have been built yet. We want to build functionality when we find a need for it, so we really know the use case, not speculating what we need in before hand and guess what the best implementation is.

## Status

This project is under development. All features are not implemented yet and the API might change.

## Example

This example is going to show some of the features of Laborious. We are going to dig deeper later.

First we need to create the connection:

```php
$db = new Laborious\Connection("mysql:host=localhost;dbname=database;charset=utf8", "user", "pass");
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
```

`Laborious\Connection` extends `PDO`, and adds a some methods to make it easier for the model layer to execute queries.

And we will create our first model:

```php
class User extends \Laborious\Model {

	protected static $_table = "users";

	protected static $_fields = array(
		"id",
		"email",
		"customer_id",
	);

  	public function get_customer_id()
	{
		return $this->customer_id;
	}
}
```

We specify that the table name is `users` and we specify which fields should be in the model. And now we'll create an object from the database result:

```php
$user = new User(
	$db,
	$db->query("SELECT * FROM `users` LIMIT 1")->fetch()
);

print "Email: ".$user->email.", "
	."username: ".$user->username.", "
	."customer_id: ".$user->get_customer_id()
```

* We pass the `$db` to the constructor so the model knows the connection to the database if it needs to execute queries.
* We run a query and fetch one row from the result.
* We are fetching `email` as a parameter.
* We are fetching `username` as a parameter. Notice that this field is not defined in the model (but it is in the table), and we can fetch it anyway.
* We are fetching the `customer_id` by calling the model's method `get_customer_id()` what we defined before.

Let's continue with some more code:

```php
$user->username = "New username";
$user->email = "new@example.com";
$user->save();
```

We are changing the `username` and `email` parameters and call `save()` to run an `UPDATE` query. This will use the `$db` we passed earlier, and will use the helper methods I talked about earlier to create the query.

Since `username` is not defined in the model (`$_fields`), the `username` will *not* get updated in the database even though we changed it and the column exists in the table. This is because you can be very flexible when using Laborious.

Let's continue with a new example:

```php
$sql = "
SELECT
	CONCAT(`username`, ' (', `email`, ')') AS `display_name`
FROM `users`
LIMIT 1
";

$user = new User(
	$db,
	$db->query($sql)->fetch()
);

print $user->display_name;
print $user->customer_id;

$user->display_name = "Things!";
$user->email = "another@example.com";
$user->save();
```

In this example we are only fetching a concatenated value, and we are printing that concatenated value (`display_name`).

We are also printing the `customer_id` parameter. But that parameter is `NULL` since we have not fetched that column in the `SELECT` query.

After that we are changing the `display_name` property. But that will not be saved to the database since that is an undefined field in the model.

We are also changing the `email`, and that will get changed in the database.

We are not fetching the `customer_id` in the `SELECT` query - so it's `NULL` in the model. But the `customer_id` will _not_ be updated to `NULL` on `save()` since we did not change the parameter in the model. The same goes for the `email`. The property was changed, and therefor it will be included in the `UPDATE`.

## Iterating

Examples:

* Query result and create model inside the iteration.
* Query result and create an iterator.

## To write

* Insert
  * New primary key
* Update
* Filter
* Validation
* Delete
* Select
  * fetch
  * Iterator
  * ForwardIterator
* Relationships
* Tips and tricks
* isset and unset
* setValues
* getChanged
* getExisting (Defined fields that exists in the model)
* clearChanged
* getKeys (The keys of the array, even if defined or not)