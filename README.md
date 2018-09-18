# Laborious

Laborious is a micro PHP database model layer. It has two main goals:

* Do as little around-stuff as possible
* Be extremely flexible and extensible


It's built for applications with a high load, and we want to spend as little resources as possible for creating abstractions during run time. We want to have the abstractions when we need it. 

You can see the model layer as a complement to your database result, for example when you need more logic (DRY) or want to automate filters and validation. The models does not care about how or when it gets its data. You can create objects from the PDO result when you find that you need it.

Some things in laborious might not have been built yet. We want to build functionality when we find a need for it, so we really know the use case, not speculating what we need in before hand and guess what the best implementation is.

## Status

This project is under development. All features are not implemented yet and the API might change.

## Example

This example is going to show some of the features of Laborious. We are going to dig deeper later. If you feel overwhelmed by this example you can feel safe the we are going to recap all of it in the sections later.

First we need to create the connection:

```php
$db = new \Laborious\Connection("mysql:host=localhost;dbname=database;charset=utf8", "user", "pass");
$db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
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
	CONCAT(`firstname`, ' ', `lastname`) AS `fullname`
FROM `users`
LIMIT 1
";

$user = new User(
	$db,
	$db->query($sql)->fetch()
);

print $user->fullname;
print $user->country_id;

$user->fullname = "Things!";
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

## Relations

Laborious does not have model relations per se, but have the ability to load related models by prefix.

Let's say we have three models:

* **Country**
* **User** - A User belongs to a country (`users.county_id = countries.id`)
* **Post** - A Post belongs to a user (`posts.user_id = users.id`)

If we wan't to eagerly load `User` while we are fetching `Post` object, we need to specify this:

```php
$sql = "
	SELECT
		`posts`.*,
		".(new User($db))->getSelectString("users")."
	FROM `posts`
	LEFT JOIN `users` ON `users`.`id` = `posts`.`user_id`
";
```

We are here using the `getSelectString()` helper method to build parts of the `SELECT` statement.

We are calling that method on the `User` object. We are passing `"users"` to tell the method what the table is joined as. This parameter is also used as a "prefix". If we take a look on the string that the `getSelectString()` produces, it would look something like this:

```sql
`users`.`id` AS `users:id`, `users`.`country_id` AS `users:country_id`
```

As you see the `AS` is `<tablename>:<columnname>`, but we can change that by using the second (`$as_prefix`) parameter:

```php
print (new User($db))->getSelectString("users", "hello");
```

Would produce:

```sql
`users`.`id` AS `hello:id`, `users`.`country_id` AS `hello:country_id`
```

And you can set the first (`$table`) parameter to `null`:

```php
print (new User($db))->getSelectString(null);
```

Would produce:

```sql
`id`, `country_id`
```

Or set `$table` to `null` _and_ specify a `$as_prefix`:

```php
print (new User($db))->getSelectString(null, "hello");
```

Would produce:

```sql
`id` AS `hello:id`, `country_id` AS `hello:country_id`
```

These two examples might be a bit dangerous if you are not careful, and in most (if not all) scenarios, they will not be used.

### Loading the model

```php
$sql = "
	SELECT
		`posts`.*,
		".(new User($db))->getSelectString("users").",
		".(new Country($db))->getSelectString("users:countries").",
	FROM `posts`
	LEFT JOIN `users` 
		ON `users`.`id` = `posts`.`user_id`
	LEFT JOIN `countries` AS `users:countries` 
		ON `users:countries`.`id` = `users`.`country_id`
	LIMIT 1
";

$post = new Post(
	$db,
	$db->query($sql)->fetch()
);

print $post->title;

$user = $post->loadModel(
	\User::class,
	"users"
);

print $user->email;

$country = $country->loadModel(
	\Country::class,
	"countries"
);

print $country->name;

```

Let's go through what's happening:

* We are fetching all columns from `posts`, all columns needed for `User` and `Country`.
  * We are telling the `User` to look for the `"users"` prefix, since we are joining `users` without an `AS` alias. The selects are going to be `users.id AS users:id` and so on.
  * We are telling `Country` to look for the `"users:conntries"` prefix, since we are joining `countries AS users:countries`. This is a good convention to have multiple suffixes when joining multiple levels. The selects are going to be `users:countries.id AS users:countries:id` and so on.
* We are creating a `Post` object by executing the query.
* We are calling `loadModel()`  on the `$post` object to load a `User` by passing `\User:class`. We are specifying that all the fields prefixed with `"users"` will be passed over to the new `User` object.
* We are calling `loadModel()` on the `$country` object to load a `Country` by passing `\Country:class`. We are specifying that all the fields prefixed with `"countries"` will be passed over to the new `Country` object.

In this example above we are passing all the `Country` (`users:countries`) fields to the `User` object when we are loading the `User` object. This works because both `users:*` and `users:coutries:*` (where the `*` is all the field names) have the prefix `users`. The fields in the `User` object will be (for example) `id` and `countries:id` since the prefix is stripped.

This means that we can load the `Country` object directly from the `Post` object:

```php
$post = new Post(
	$db,
	$db->query($sql)->fetch()
);

print $post->title;

$country = $post->loadModel(
	\Country::class,
	"users:countries"
);

print $country->name;
```

### Loading by string or object

In the examples above we are using `\User::class`  and `\Country::class` when calling `loadModel()`. Those are simply generating strings that becomes `"\User"` and `"\Country"`. Using the `::class` helper only makes sure that the string is correct.

You can also pass an object to `loadModel()` if you want to. This is a tiny bit slower than passing the string, and you need to make sure that the object passed is not loaded (with previous information).

```php
// By string
$user = $post->loadModel(
	\User::class,
	"users"
);

// By object
$user = $post->loadModel(
	new User($db),
	"users"
);
```

## API

### Model

#### isLoaded()

Checks whether a model is "loaded". A loaded model is a model who has the primary key populated.

```php
$user = new User($db);

$user->isLoaded(): // false

$user->email = "donald@duck.com";
$user->save();
$user->isLoaded(); // true

$found_user = (new User($db))
    ->fetch(1024);
$found_user->isLoaded(); // true (if the row is found, false if not)

$new_user = new User($db);
$new_user->id = 12; // if "id" is the primary key
$new_user->isLoaded(); // true
```

## Contribute

There are two main areas where help is needed:

* **Writing tests** - Not everything is covered by tests yet.
* **Writing documentation** - This `README.md` needs a lot more love.

## To write

* Insert
  * Fetching non-set defined values (returns `NULL`)
* Filter
* Validation
* Delete
* Select
  * fetch
  * Iterator
  * ForwardIterator
* Exceptions
* Relationships
* Tips and tricks
* isset and unset
* setValues
* getChanged
* getExisting (Defined fields that exists in the model)
* clearChanged
* getKeys (The keys of the array, even if defined or not)
  * Before and after INSERT.