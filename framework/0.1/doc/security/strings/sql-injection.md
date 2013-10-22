
# Introduction

Before continuing with this page, you need to understand SQL to the point that the following makes sense:

	SELECT
		`id`
	FROM
		`user`
	WHERE
		`name` = "john" AND
		`pass` = "123"

Where this could be used on a login page... please don't though, not only is it stuck in finding the account for "john" (which probably isn't going to be that useful), it doesn't cover any of the security issues related to [logins and passwords](../../../doc/security/logins.md).

So taking that the username and password is replaced with values that the user is currently trying to login with, the end result is still a single string, which is passed to the database, where it interprets it, and hopefully returns the users id.

---

## Usage in PHP

One way to get this to work in PHP to to use the following (again, please don't do this):

	$name = request('name'); // Gets the value from $_REQUEST
	$pass = request('pass');

	$sql = 'SELECT
				`id`
			FROM
				`user`
			WHERE
				`name` = "' . $name . '" AND
				`pass` = "' . $pass . '"';

The resulting SQL string is then made up from 5 individual strings joined together.

It begins with a string starting with SELECT, which is joined with the 'name' string that came from the browser, joined with a bit more SQL, the password (again from the browser), and finally another string, which is simply a double quote.

---

## The problem

But what happens if the someone tried to login in with the username:

	test"ing

It will result in the SQL string:

	SELECT
		`id`
	FROM
		`user`
	WHERE
		`name` = "test"ing" AND
		`pass` = "123"

Which isn't valid SQL, and would cause an error.

But not only that, if they are trying to break into your website, they now know that the double quote hasn't been escaped, so they could then try with the username:

	admin"; --

Which now creates the SQL:

	SELECT
		`id`
	FROM
		`user`
	WHERE
		`name` = "admin"; -- the rest is ignored as a comment

Now they have just logged in with the admin account.

---

## The basic solution

To get around this problem for MySQL you basically add a backslash to escape the character e.g.

	SELECT
		`id`
	FROM
		`user`
	WHERE
		`name` = "admin\"; --" AND
		`pass` = "123"

A feature that was done automatically with [magic quotes](http://www.php.net/magic_quotes) before PHP 5.4, but has fortunately now been removed (it caused more problems than it solved).

Below we can discuss a few different options.

---

## PHP PDO

[PDO](http://www.php.net/pdo) allows you to write raw SQL, but the variables are substituted by either replacing placeholders:

	$q = $db->prepare('SELECT id FROM user WHERE name = ? AND pass = ?');
	$q->execute(array($name, $pass));

This is fine, but you have the fun job of parameter counting, where if you're using 10 variables then its not going to be fun counting them off.

It's basically the same reason I dislike the INSERT statement (for one record), and why I prefer to use:

	$db->insert('table', array(
			'field_1' => 'value'
			'field_2' => 'value'
			'field_3' => 'value'
		));

The alternative is to use named parameters:

	$q = $db->prepare('SELECT id FROM user WHERE name = :name AND pass = :pass');
	$q->execute(array(':name' => $name, ':pass' => $pass));

But have you noticed the word "name" appears 4 times now? I find the amount of repetition is really annoying.

And the main point of this is to stop inexperienced developers making a mistake with the variable escaping, but it doesn't, those same developers still do things like:

	$db->prepare("SELECT id FROM user WHERE name = '$name' AND...

Please note I'm not a fan of double quotes, as the variables can be easily hidden in the string (e.g. with syntax highlighting).

---

## Database abstractions

There are several database abstractions available, where they will try to abstract everything, so you don't have to see the SQL being generated.

Which some believe is a good thing, but you really need to check every query, as they often make bad assumptions, ridiculously inefficient queries (returning too much data), and can open security holes without you realising.

Then, with the case of [CakePHP](http://book.cakephp.org/2.0/en/models/retrieving-your-data.html), you start getting code like the following:

	$this->model->find('all', array(
		'conditions' => array(
			'OR' => array(
					array('moderation' => 'approved'),
					array(
						'user_id' => $author_id,
						array(
							'OR' => array(
									array('moderation' => 'new'),
									array('moderation' => 'pending'),
								),
						)
					),
				),
			),
	));

Which because its using arrays, you will find issues like a condition being silently ignored as two or more use the key 'OR'... for reference, you have to wrap them in another (indexed based) array.

Basically, you still need to understand the SQL, and you now need to understand the abstraction to get it to create the SQL you could have written yourself.

And before you think your queries run efficiently enough when its on your local computer during development, you will have a nasty surprise when its Live and there is a sudden spike in traffic, and now you need to take your slow sql log and try to work out which bit of code created it.

---

## Alternatives to SQL

There are alternatives to SQL, often under the category of "NoSQL".

These have their own advantages, and can avoid the SQL injection and scaling issues, however they have their own problems.

They are basically a different approach to data storage, each with their own merits. In the same way that you could store tabular data as a CSV file - it works in some situations, but you have to evaluate which is the best one for your project.

---

## Raw SQL

This is still my preferred method, and yes, that does mean every variable has to be escaped properly... like how you have to escape variables for [html](../../../doc/security/strings/html-injection.md), [urls](../../../doc/security/strings/url-manipulation.md), [command line arguments](../../../doc/security/strings/cli-injection.md), [regular expressions](../../../doc/security/strings/regexp-injection.md), etc.

I believe all developers should be educated about the issues, and taught how to overcome them.

So for reference I use code like the following:

	$sql = 'SELECT
				`id`
			FROM
				`user`
			WHERE
				`name` = "' . $db->escape($name) . '" AND
				`pass` = "' . $db->escape($pass) . '"';

	if ($row = $db->fetch_row($sql)) {
	}

And if the query needs to be built up, then a [simple naming convention](../../../doc/security/strings.md) of using "_sql" suffix to any SQL variables.

	$where_sql = array(
			'`id` = "' . $db->escape($id) . '"',
			'`state` = "active"',
		);

	if (condition) {
		$where_sql[] = '`field` = "' . $db->escape($value) . '"';
	}

	$where_sql = '(' . implode(') AND (', $where_sql) . ')';

	$from_sql = '`user`'; // Could include a JOIN, and be passed to multiple queries.

	$sql = 'SELECT
				`id`
			FROM
				' . $from_sql . '
			WHERE
				' . $where_sql;

	$db->query($sql);

As a side note, regarding the $db object, see the [database helper](../../../doc/system/database.md).

And for now I will look forward to a day that either SQL is replaced, or there is a better way of handling variables in strings (or at least generating SQL).
