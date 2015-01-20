
# Debug

This really only comes into play when your in development mode (see below).

However there are some useful functions which are always present:

	debug($var);

	echo debug_dump($var);

	debug_exit();

---

## Development mode

You can enable this by either setting the `SERVER` constant to 'stage', or the via the [config](../../doc/setup/config.md):

	define('SERVER', 'stage');

	$config['debug.level'] = 4;
	$config['debug.show'] = true;

When enabled, all pages should:

1. Get development notes at the bottom left of the page (see below).
2. Review your SQL for required fields (see below).
3. Uses an XML header to ensure your HTML remains strict ([output.mime](../../doc/setup/config.md)).
4. Sets and enforces the [CSP header](../doc/security/csp.md).

---

## Development notes

In the bottom left of the page should be the time it took to process the HTML (should be less than 0.1 seconds, even with debug info), and 3 links:

- **[C]** To see the config variables.
- **[H]** To see general help (e.g. how the [controllers](../../doc/setup/controllers.md) were used).
- **[L]** To see a log of events (typically the SQL queries).

---

## Database required fields

In debug mode, every query sent to the database can be checked to ensure that you always specify some required fields (if present on the table).

So if you don't DELETE records (a good practice), then you will probably have a 'deleted' column on most tables.

To ensure that you always check that your always ignoring deleted columns, the following default is used:

	$config['debug.db_required_fields'] = array('deleted');

And if you do need a query to include the deleted columns, you can simply do a:

	WHERE
		deleted = deleted

This also proves to the next developer that you have purposely included the deleted records (and the database won't care).
