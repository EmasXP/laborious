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

This example is going to show some of the features of Laborious. We are going to dig deeper later. If you feel overwhelmed by this example you can feel safe the we are going to recap all of it in the sections later.

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
		"country_id",
	);

  	public function getCountryId()
	{
		return $this->country_id;
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
	."country_id: ".$user->getCountryId()
```

* We pass the `$db` to the constructor so the model knows the connection to the database if it needs to execute queries.
* We run a query and fetch one row from the result.
* We are fetching `email` as a parameter.
* We are fetching `username` as a parameter. Notice that this field is not defined in the model (but it is in the table), and we can fetch it anyway.
* We are fetching the `country_id` by calling the model's method `getCountryId()` what we defined before.

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
	`id`,
	CONCAT(`firstname`, ' ', 'lastname`) AS `fullname`
FROM `users`
LIMIT 1
";

$user = new User(
	$db,
	$db->query($sql)->fetch()
);

print $user->fullname;
print $user->country_id;

$user->display_name = "Things!";
$user->email = "another@example.com";
$user->save();
```

In this example we are only fetching a concatenated value, and we are printing that concatenated value (`fullname`).

We are also printing the `country_id` parameter. But that parameter is `NULL` since we have not fetched that column in the `SELECT` query.

After that we are changing the `fullname` property. But that will not be saved to the database since that is an undefined field in the model.

We are also changing the `email`, and that will get changed in the database.

We are not fetching the `country_id` in the `SELECT` query - so it's `NULL` in the model. But the `country_id` will _not_ be updated to `NULL` on `save()` since we did not change the parameter in the model. The same goes for the `email`. The property was changed, and therefor it will be included in the `UPDATE`.

## Inserting

```php
$user = new User($db);
$user->email = "test@example.com";

var_dump($user->id);

$user->save();

print $user->id;
```

A new object is created, and the `email` parameter is set.

When we print `$user->id` before the row is inserted, the value is `NULL`, but it contains row's auto incremented value after save.

An alternative way is to do like this:

```php
$user = new User($db);
$user->setValues(array(
	"email" => "test@example.com",
	"country_id" => 1,
));
$user->save();
```

You can also pass an array of expected values:

```php
$values = array(
	"email" => "test@example.com",
	"country_id" => 1,
	"foo" => "bar",
);

$user = new User($db);
$user->setValues(
	$values,
	array(
		"email",
		"country_id",
	)
);
$user->save();
```

In this example only `email` and `country_id` will be set on the object, but `foo` will be ignored.

Let's try to set a value that is not defined in the model (`$_fields`):

```php
$user = new User($db);
$user->email = "test@example.com";
$user->firstname = "Hello";
$user->age = 1024;
$user->save();
```

Only `email` is going to be saved to the database. `firstname` is not defined in the model, and `age` does not even exist in the table.

## Updating

```php
$user = new User(
	$db,
	$db->query("SELECT * FROM `users` LIMIT 1")->fetch()
);

$user->email = "new@example.com";
$user->save();
```

This creates a new object. We pass the database connection and the row data to the constructor.

Then we change the email and saves it. `setValues()` works exactly the same here as for inserts.

Note that `save()` is used for both inserts and updates. It figures out whether it is a "loaded" object by checking if the primary key exists.

Here's another example:

```php
$user = new User(
	$db,
	$db->query("SELECT `id`, `email` FROM `users` LIMIT 1")->fetch()
);

$user->email = "hello@example.com";
$user->save();
```

Here we are only fetching the `id` and `email` columns. We are changing the `email` and saves it. All other fields are going to be untouched by the `UPDATE` query. But it we take this example:

```php
$user = new User(
	$db,
	$db->query("SELECT `id`, `email` FROM `users` LIMIT 1")->fetch()
);

$user->country_id = 2;
$user->save();
```

We are still only fetching `id` and `email`, but we are changing the `country_id`. On save `email` is going to be untouched by the `UPDATE` query, but `country_id` will be set to `2`.

Both these fields can be used by the model when it is doing queries simply because we have defined them in the `$_fields` array. But what happens when we try to use a field that is not defined?

```php
$user = new User(
	$db,
	$db->query("SELECT `id`, `firstname` FROM `users` LIMIT 1")->fetch()
);

$user->firstname = "Newname";
$user->save();
```

`firstname` will not be updated in the database. In fact, no query at all will be sent in this example, since there are no changes.

## Iterating

Examples:

* Query result and create model inside the iteration.
* Query result and create an iterator.

## To write

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