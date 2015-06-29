
# Nearest helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/nearest/nearest.php).

To initialise with a profile:

	$nearest = new nearest('users');

Or to create with a config array:

	$nearest = new nearest(array(
			'max_results' => 10,
		));

---

## Exmple 1

To find the records closest to a particular location, just call:

	$results = $nearest->locations_nearest($postcode);

By default this will use the Google Geocode service.

Where the config could be something like:

	$config['nearest.users.table_sql'] = $config['db.prefix'] . 'user';
	$config['nearest.users.where_sql'] = 'address_postcode != "" AND deleted = "0000-00-00 00:00:00"';
	$config['nearest.users.field_postcode_sql'] = 'address_postcode';
	$config['nearest.users.field_latitude_sql'] = 'location_latitude';
	$config['nearest.users.field_longitude_sql'] = 'location_longitude';
	$config['nearest.users.extra_fields_sql'] = array();
	$config['nearest.users.max_results'] = 10;
	$config['nearest.users.max_km'] = 0; // Unlimited

	$config['nearest.gm_key'] = 'XXX';
		// https://code.google.com/apis/console/

---

## Example 2

If you have a more complicated source, then you can pass in an array of results:

	$nearest = new nearest(array(
			'field_latitude' => 'latitude',
			'field_longitude' => 'longitude',
			'max_results' => 0, // Unlimited
			'max_km' => 0, // Unlimited
		));

	// $results = array(array(
	// 		'name' => 'A',
	// 		'latitude' => 50.54,
	// 		'longitude' => -2.596,
	// 	));

	$results = $nearest->locations_distance($results, $postcode);

	array_key_sort($locations, 'distance', SORT_NUMERIC, SORT_ASC);

The results are just modified to set a 'distance' key (either in km, or NULL for unknown).

And because we have not set a limit on the results, we could use the paginator:

	$paginator = new paginator();

	$results = $paginator->limit_array($results);

Or if you do want to limit the results:

	$results = $nearest->locations_limited($results, $postcode);

---

To initialise the lat/long values for a table with only a postcode field:

	$nearest = new nearest('users');
	$nearest->update_init(30);

Note that this will only update 30 records at a time, as the external API will probably be rate limited.

This could go in as a temporary [maintenance job](../../doc/setup/jobs.md), to be removed when all records have been updated.
