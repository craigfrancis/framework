# Nearest

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/nearest/nearest.php).

	$nearest = new nearest('profile');

	$nearest = new nearest(array(
			'table_sql' => 'stores',
			'max_results' => 10,
		));

---

## Example config

	$config['nearest.users.table_sql'] = $config['db.prefix'] . 'user';
	$config['nearest.users.where_sql'] = 'address_postcode != "" AND deleted = "0000-00-00 00:00:00"';
	$config['nearest.users.field_postcode_sql'] = 'address_postcode';
	$config['nearest.users.field_latitude_sql'] = 'location_latitude';
	$config['nearest.users.field_longitude_sql'] = 'location_longitude';
	$config['nearest.users.extra_fields_sql'] = array();
	$config['nearest.users.max_results'] = 0;
	$config['nearest.users.max_km'] = 0;

	$config['nearest.gm_key'] = 'XXX';
		// https://code.google.com/apis/console/

---

To initialise the lat/long values for a table with only a postcode field:

	$nearest = new nearest('users');
	$nearest->update_init(30);

This could go in as a temporary [gateway script](../../doc/setup/gateways.md), and will process 30 users at a time.
