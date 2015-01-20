
# Database

First, you need to set the connection [config](../../doc/setup/config.md):

	$config['db.host'] = 'localhost';
	$config['db.user'] = 'stage';
	$config['db.pass'] = 'st8ge';
	$config['db.name'] = 's-craig-framework';

	$config['db.prefix'] = 'tpl_';

You can get access to the database object though the helper function:

	$db = db_get();

---

## Alternative connections

If you need to connect to a different database, use the configuration:

	$config['db.old.host'] = 'localhost';
	$config['db.old.user'] = 'stage';
	$config['db.old.pass'] = 'st8ge';
	$config['db.old.name'] = 's-craig-framework';

Then access the database object via:

	$db_old = db_get('old');


---

## Notes

ORM: Black magic vs knowing how to actually write SQL and the issues that come with that.

http://www.codeyellow.nl/identifier-sqli.html

Consider [Nette](http://doc.nette.org/en/2.1/database-table), which caches the used columns, so will avoid "SELECT *", but will probably not handle multiple code paths (e.g. one for simple a HTML table, then additional fields for a CSV download).

-

Could use "yield" in PHP 5.5 for:

	foreach ($db->fetch_all($sql) as $row) {
	}

-

Trying to add conditions?

	$table->where("field > ?", $val);

Good to avoid a massive array of variables, Not so good with:

	WHERE
		type = "X" AND
		(
			date < "Y" OR
			date > "Z"
		)

Or a keyword search splitting on whitespace, creating a condition with AND to combine all of the words, and OR to go over multiple columns.

-
