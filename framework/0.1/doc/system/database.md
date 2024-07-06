
# Database

First, you need to set the connection [config](../../doc/setup/config.md):

	$config['db.host'] = 'localhost';
	$config['db.name'] = 's-company-project';
	$config['db.user'] = 'stage';

	$config['db.prefix'] = 'tbl_';

	$secrets['db.pass'] = ['type' => 'value'];

As the password is stored via the secrets helper, run:

	./cli --secrets=init

You can get access to the database object though the helper function:

	$db = db_get();

---

## Alternative connections

If you need to connect to a different database, use the configuration:

	$config['db.old.host'] = 'localhost';
	$config['db.old.name'] = 's-company-project';
	$config['db.old.user'] = 'stage';

	$secrets['db.old.pass'] = ['type' => 'value'];

Then access the database object via:

	$db_old = db_get('old');
