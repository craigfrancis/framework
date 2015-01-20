
# SQL Injection

Before continuing with this page, you need to understand SQL to the point that the following makes sense:

	SELECT
		`title`,
		`body`
	FROM
		`news`
	WHERE
		`id` = "123"

---

## Usage in PHP

One way to get this to work in PHP to to use the following (don't do this):

	$id = request('id'); // Gets the value from $_REQUEST

	$sql = 'SELECT
				`title`,
				`body`
			FROM
				`news`
			WHERE
				`id` = "' . $id . '"';

The resulting $sql string is created by naively joining the 3 strings together.

---

## The problem

But what happens if the someone loaded the page with the ID:

	123"456

It will result in the SQL:

	SELECT
		`title`,
		`body`
	FROM
		`news`
	WHERE
		`id` = "123"456"

Which isn't valid SQL, and would cause an error... so the remote user knows you have a problem, and can start trying to get more information.

	0" UNION SELECT username, password FROM admin; --

Which now creates the SQL:

	SELECT
		`title`,
		`body`
	FROM
		`news`
	WHERE
		`id` = "0" UNION SELECT username, password FROM admin; --"

Where the "--" converts the rest into a comment, and they have just found the username and password ([hopefully hashed](../../../doc/security/logins.md)) for an admin account.

---

## The basic solution

To get around this problem for MySQL you basically add a backslash to escape the character e.g.

	SELECT
		`title`,
		`body`
	FROM
		`news`
	WHERE
		`id` = "0\" UNION SELECT username, password FROM admin; --"

An automated feature that was attempted with [magic quotes](https://php.net/magic_quotes) before PHP 5.4, but has fortunately now been removed (it caused more problems than it solved).

Below we can discuss a few different options.

---

## PHP PDO

[PDO](https://php.net/pdo) allows you to write raw SQL (aka prepared statements), where the variables are substituted either with positional placeholders:

	$q = $db->prepare('SELECT id FROM user WHERE name = ? AND pass = ?');
	$q->execute(array($name, $pass));

Where you have the fun job of parameter counting (not fun if you're using 10 or more variables), and is the same reason I dislike the standard INSERT statement.

The alternative is to use named parameters:

	$q = $db->prepare('SELECT id FROM user WHERE name = :name AND pass = :pass');
	$q->execute(array(':name' => $name, ':pass' => $pass));

But have you noticed the word "name" appears 4 times now? I find the amount of repetition annoying.

And the main point of this is to stop inexperienced developers making a mistake with the variable escaping, but it doesn't, those same developers still do things like:

	$db->prepare("SELECT id FROM user WHERE name = '$name' AND...

Please note I'm not a fan of double quotes, as the variables can be easily hidden in the string (e.g. with syntax highlighting).

---

## Database abstractions

There are several types of database abstractions available which try to hide the SQL being generated.

Often these come in the form of ORM's (object-relational mapping), which try to represent the database records as objects (can more easily be used in code).

Some believe this setup is always a good thing, but you should still check every query. They often make bad assumptions, ridiculously inefficient queries (e.g. returning too much data), and can open security holes without you realising (e.g. [not escaping identifiers](http://www.codeyellow.nl/identifier-sqli.html)).

Then, with the case of [CakePHP](http://book.cakephp.org/2.0/en/models/retrieving-your-data.html), you start getting code like the following:

	$this->model->find('all', array(
		'conditions' => array(
			'OR' => array(
					array('moderation' => 'approved'),
					array(
						'user_id' => $author_id,
						array(
							'OR' => array(
									'moderation' => 'new',
									'moderation' => 'pending',
								),
						)
					),
				),
			),
	));

With a quick look over this query, its not particularly obvious it's broken, but there are technically two "moderation" keys in the "OR" array, so it will only find "pending" records for the current user - to fix, you have to wrap them in another array.

In summary, you still need to check the generated SQL, but you now need to understand the abstraction in detail as well.

---

## Alternatives to SQL

There are alternatives to SQL, often under the category of "NoSQL".

These have their own advantages, and can avoid the SQL injection and scaling issues, however they have their own problems.

They are basically a different approach to data storage, each with their own merits. In the same way that you could store tabular data as a CSV file - it works in some situations, but you have to evaluate which is the best one for your project.

---

## Raw SQL

This is still my preferred method, and yes, that does mean every variable has to be escaped properly... like how you have to escape variables for [html](../../../doc/security/strings/html-injection.md), [urls](../../../doc/security/strings/url-manipulation.md), [command line arguments](../../../doc/security/strings/cli-injection.md), [regular expressions](../../../doc/security/strings/regexp-injection.md), etc.

I believe all developers should be educated about the issues, and taught how to overcome them.

So for reference, I use code like the following:

	$sql = 'SELECT
				`id`
			FROM
				`table`
			WHERE
				`field` = "' . $db->escape($value) . '"';

	if ($row = $db->fetch_row($sql)) {
	}

And if the query needs to be constructed in code, then I use a [simple naming convention](../../../doc/security/strings.md) of using "_sql" suffix to any SQL variables.

	$where_sql = array(
			'`id` = "' . $db->escape($id) . '"',
		);

	if (condition) {
		$where_sql[] = '`field` = "' . $db->escape($value) . '"';
	}

	$where_sql = '(' . implode(') AND (', $where_sql) . ')';

	$from_sql = '`user`'; // Could be extended to include a JOIN, and be passed to multiple queries.

	$sql = 'SELECT
				`id`
			FROM
				' . $from_sql . '
			WHERE
				' . $where_sql;

	$db->query($sql);

See the notes about the [database helper](../../../doc/system/database.md) to find out about the $db object.

And I will continue to look forward to a day that either SQL is replaced, abstracted perfectly, or there is a better way of handling variables in strings.
