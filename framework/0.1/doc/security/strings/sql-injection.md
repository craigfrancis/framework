
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

## The solution

To get around this problem you use parameterised queries.

This means you start by creating an SQL statement like:

	$sql = 'SELECT
				`title`,
				`body`
			FROM
				`news`
			WHERE
				`id` = ?';

That string is sent to the database first.

Then, in a second step, you tell the database the parameters.

In this case, we would say the first (and only) parameter, represented by the "?", is 123.

This means that the SQL string is never tainted with user supplied values.

---

## PHP Prime

This framework provides a simple database abstraction for the `mysqli` extension:

	$db = db_get();

	$sql = 'SELECT
				`title`,
				`body`
			FROM
				`news`
			WHERE
				`id` = ?';

	$parameters = [];
	$parameters[] = array('i', $var);

	foreach ($db->fetch_all($sql, $parameters) as $row) {
	}

	if ($row = $db->fetch_row($sql, $parameters)) {
	}

Notice that the $parameters array takes 2 values, the type and the value itself.

The types include:

- 'i' = Integer
- 'd' = Double
- 's' = String
- 'b' = Blob

See the notes about the [database helper](../../../doc/system/database.md) to find out about the $db helper.

---

## PHP PDO

[PDO](https://php.net/pdo) allows you to write raw SQL (aka prepared statements), where the variables are substituted either with positional placeholders:

	$q = $db->prepare('SELECT id FROM user WHERE name = ? AND pass = ?');
	$q->execute(array($name, $pass));

Or named parameters:

	$q = $db->prepare('SELECT id FROM user WHERE name = :name AND pass = :pass');
	$q->execute(array(':name' => $name, ':pass' => $pass));

You still have to be careful of inexperienced developers not understanding the problem, and making a mistake like:

	$db->prepare("SELECT id FROM user WHERE name = '$name' AND...

---

## Database abstractions

There are several types of database abstractions available which try to hide the SQL being generated.

Often these come in the form of ORM's (Object-Relational Mapping), which try to represent the database records as objects (can more easily be used in code).

Some believe this setup is always a good thing, but I believe you should still check every query. They often make bad assumptions, ridiculously inefficient queries (e.g. returning too much data), and can open security holes without you realising (e.g. [not escaping identifiers](http://www.codeyellow.nl/identifier-sqli.html)).

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
