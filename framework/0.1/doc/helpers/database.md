
# Database

First, you need to set the connection [config](/doc/setup/config/):

	$config['db.host'] = 'localhost';
	$config['db.user'] = 'stage';
	$config['db.pass'] = 'st8ge';
	$config['db.name'] = 's-craig-framework';

	$config['db.prefix'] = 'tpl_';

You can get access to the database object though the helper function:

	$db = db_get();
