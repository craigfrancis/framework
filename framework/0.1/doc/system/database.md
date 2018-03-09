
# Database

First, you need to set the connection [config](../../doc/setup/config.md):

	$config['db.host'] = 'localhost';
	$config['db.user'] = 'stage';
	$config['db.pass'] = 'password';
	$config['db.name'] = 's-craig-framework';

	$config['db.prefix'] = 'tpl_';

You can get access to the database object though the helper function:

	$db = db_get();

---

## Alternative connections

If you need to connect to a different database, use the configuration:

	$config['db.old.host'] = 'localhost';
	$config['db.old.user'] = 'stage';
	$config['db.old.pass'] = 'password';
	$config['db.old.name'] = 's-craig-framework';

Then access the database object via:

	$db_old = db_get('old');
