<?php

/***************************************************
// Example setup
//--------------------------------------------------

	//--------------------------------------------------
	// Simple

		// config::set('nearest.sql_table', 'stores');

		$nearest = new nearest();

	//--------------------------------------------------
	// Profile support

		config::set('nearest.profile.sql_table', 'stores');

		$nearest = new nearest('profile');

	//--------------------------------------------------
	// Custom

		$nearest = new nearest(array(
				'sql_table' => 'stores',
				'max_results' => 10,
			));

//--------------------------------------------------
// End of example setup
***************************************************/

	class nearest extends check {

		//--------------------------------------------------
		// Variables

			private $config = array();
			private $db_link;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->_setup($config);
			}

			protected function _setup($config) {

				//--------------------------------------------------
				// Defaults

					$this->config['profile'] = NULL;
					$this->config['sql_table'] = DB_PREFIX . 'location';
					$this->config['sql_where'] = 'true';
					$this->config['sql_field_id'] = 'id';
					$this->config['sql_field_latitude'] = 'latitude';
					$this->config['sql_field_longitude'] = 'longitude';
					$this->config['sql_field_address'] = array();
					$this->config['sql_field_postcode'] = 'postcode';
					$this->config['sql_field_country'] = NULL;
					$this->config['max_results'] = 5;
					$this->config['max_km'] = 40; // 25 miles
					$this->config['min_results'] = 0;

				//--------------------------------------------------
				// Set config

					if (is_string($config)) {

						$config = array('profile' => $config);

					} else if (!is_array($config)) {

						$config = array();

					}

					$prefix = 'nearest';
					if (isset($config['profile']) && $config['profile'] != '') {
						$prefix .= '.' . $config['profile'];
					}

					$site_config = config::get_all($prefix);
					if (count($site_config) > 0) {
						$config = array_merge($site_config, $config);
					}

					$this->config($config);

			}

		//--------------------------------------------------
		// Configuration

			public function config($config, $value = NULL) {

				//--------------------------------------------------
				// Set

					if (is_array($config)) {
						foreach ($config as $key => $value) {
							$this->config[$key] = $value;
						}
					} else {
						$this->config[$config] = $value;
					}

				//--------------------------------------------------
				// Get page variables to be re-calculated

					foreach ($this->config as $key => $value) {
						if (substr($key, 0, 9) == 'sql_field' && is_string($value)) {
							$key_short = substr($key, 4);
							if (!isset($this->config[$key_short])) {
								$this->config[$key_short] = preg_replace('/^[^\.]+\./', '', $value);
							}
						}
					}

			}

			public function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

		//--------------------------------------------------
		// Return nearest locations based on search

			public function locations_nearest($search, $country = NULL) {

				//--------------------------------------------------
				// Find location

					$result = $this->search($search, $country);

					if ($result == NULL) {
						return array();
					}

				//--------------------------------------------------
				// Return all locations

					$locations = $this->locations_distance($result['latitude'], $result['longitude']);

				//--------------------------------------------------
				// Results

					$results = array();
					$k = 0;

					$distances = array();
					foreach ($locations as $id => $info) {
						$distances[$id] = $info['distance'];
					}

					asort($distances, SORT_NUMERIC);

					foreach ($distances as $location => $distance) {

						$k++;

						if ((($this->config['max_results'] > 0 && $k > $this->config['max_results']) || ($this->config['max_km'] > 0 && $distance > $this->config['max_km'])) && ($this->config['min_results'] == 0 || $k > $this->config['min_results'])) {
							break;
						}

						$results[$location] = $locations[$location];

					}

				//--------------------------------------------------
				// Return

					return $results;

			}

			public function locations_distance($latitude, $longitude) {

				//--------------------------------------------------
				// Return locations

					$pi = pi();

					$radians_lat1 = ($latitude * $pi / 180);
					$radians_long1 = ($longitude * $pi / 180);

					$locations = array();

					$fields = array(
							$this->config['sql_field_id'],
							$this->config['sql_field_latitude'],
							$this->config['sql_field_longitude'],
						);

					$sql_where = '
						' . $this->config['sql_field_latitude'] . ' != 0 AND
						' . $this->config['sql_field_longitude'] . ' != 0 AND
						' . $this->config['sql_where'];

					$db = $this->db_get();

					$rst = $db->select($this->config['sql_table'], $fields, $sql_where);
					while ($row = $db->fetch_assoc($rst)) {

						$radians_lat2 = $row[$this->config['field_latitude']] * $pi / 180;
						$radians_long2 = $row[$this->config['field_longitude']] * $pi / 180;

						$R = 6371; // earth's mean radius in km
						$d_lat  = $radians_lat2 - $radians_lat1;
						$d_long = $radians_long2 - $radians_long1;

						$a = sin($d_lat/2) * sin($d_lat/2) + cos($radians_lat1) * cos($radians_lat2) * sin($d_long/2) * sin($d_long/2);
						$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
						$distance = $R * $c;

						$locations[$row[$this->config['field_id']]] = array(
								'latitude' => $row[$this->config['field_latitude']],
								'longitude' => $row[$this->config['field_longitude']],
								'distance' => $distance,
							);

					}

				//--------------------------------------------------
				// Return

					return $locations;

			}

		//--------------------------------------------------
		// Search to return co-ordinates

			public function search($search, $country = NULL) {

				//--------------------------------------------------
				// Country

					if ($country == NULL || $country == 'uk') {
						$country = 'gb';
					}

				//--------------------------------------------------
				// Setup

					$latitude = NULL;
					$longitude = NULL;
					$accuracy = 0;
					$cached = false;

				//--------------------------------------------------
				// Cleanup

					$search = preg_replace('/ +/', ' ', trim($search));

					if ($search == '') {
						return NULL;
					}

					if ($country == 'gb') {
						$postcode = format_british_postcode($search);
					} else {
						$postcode = NULL;
					}

					$search_query = ($postcode === NULL ? $search : $postcode);

				//--------------------------------------------------
				// Exceptions

					if ($latitude === NULL) {

						if ($postcode === NULL) {
							$part = strtoupper($search);
						} else {
							$part = strtoupper(substr($postcode, 0, 2));
						}

						if ($part == 'IM') { // Isle of man

							$latitude = 54.22169;
							$longitude = -4.520531;
							$accuracy = 1;

						} else if ($part == 'JE') { // Jersey

							$latitude = 49.219531;
							$longitude = -2.133751;
							$accuracy = 1;

						} else if ($part == 'GY') { // Guernsey

							$latitude = 49.453187;
							$longitude = -2.573183;
							$accuracy = 1;

						}

					}

				//--------------------------------------------------
				// Cache search

					$db = $this->db_get();

					if ($latitude === NULL) {

						$db->query('SELECT
										latitude,
										longitude,
										accuracy
									FROM
										' . DB_PREFIX . 'nearest_search_cache
									WHERE
										search = "' . $db->escape($search_query) . '" AND
										country = "' . $db->escape($country) . '" AND
										edited > "' . $db->escape(date('Y-m-d H:i:s', strtotime('-1 week'))) . '"');

						if ($row = $db->fetch_assoc()) {
											$latitude = $row['latitude'];
											$longitude = $row['longitude'];
											$accuracy = $row['accuracy'];
											$cached = true;
										}

					}

				//--------------------------------------------------
				// Try Google - will also pick up towns, etc.

					if ($latitude === NULL) {

						//--------------------------------------------------
						// Ask

							$contents = '';

							$path = 'http://maps.google.com/maps/api/geocode/xml?address=' . urlencode($search_query) . '&sensor=false&region=' . urlencode($country);

							$handle = fopen($path, 'rb');
							if ($handle) {
								while ($handle && !feof($handle)) {
									$contents .= fread($handle, 8192);
								}
								fclose($handle);
							}

							usleep(500000); // Half a second

						//--------------------------------------------------
						// Extract

							$result = simplexml_load_string($contents);
							if (isset($result->status) && $result->status == 'OK') {
								if (isset($result->result) >= 1) {
									$latitude = floatval($result->result->geometry->location->lat);
									$longitude = floatval($result->result->geometry->location->lng);
									$accuracy = ($result->result->geometry->location_type == 'ROOFTOP' ? 5 : 4);
								}
							}

					}

				//--------------------------------------------------
				// Try outer-code

					if ($latitude === NULL) {

						$db->query('SELECT 1 FROM ' . DB_T_PREFIX . 'nearest_outcode LIMIT 1');
						if ($db->num_rows() == 0) {
							// TODO: Load from sql file
						}

						$db->query('SELECT
										latitude,
										longitude
									FROM
										' . DB_PREFIX . 'nearest_outcode
									WHERE
										outcode = "' . $db->escape($postcode === NULL ? $search : substr($postcode, 0, -4)) . '"
									LIMIT
										1');

						if ($row = $db->fetch_assoc()) {
							$latitude = $row['latitude'];
							$longitude = $row['longitude'];
							$accuracy = 3;
						}

					}

				//--------------------------------------------------
				// Cache

					if ($latitude !== NULL && $cached == false) {

						$db->query('INSERT INTO ' . DB_PREFIX . 'nearest_search_cache (
										search,
										country,
										latitude,
										longitude,
										accuracy,
										created,
										edited
									) VALUES (
										"' . $db->escape($search_query) . '",
										"' . $db->escape($country) . '",
										"' . $db->escape($latitude) . '",
										"' . $db->escape($longitude) . '",
										"' . $db->escape($accuracy) . '",
										"' . $db->escape(date('Y-m-d H:i:s')) . '",
										"' . $db->escape(date('Y-m-d H:i:s')) . '"
									) ON DUPLICATE KEY UPDATE
										latitude = "' . $db->escape($latitude) . '",
										longitude = "' . $db->escape($longitude) . '",
										accuracy = "' . $db->escape($accuracy) . '",
										edited = "' . $db->escape(date('Y-m-d H:i:s')) . '"');

					}

				//--------------------------------------------------
				// Return

					if ($latitude === NULL) {
						return NULL;
					} else {
						return array(
								'latitude' => $latitude,
								'longitude' => $longitude,
								'accuracy' => $accuracy,
								'country' => $country,
							);
					}

			}

			public function update_locations() {

				//--------------------------------------------------
				// For each record

					$fields = array(
							$this->config['sql_field_id'],
							$this->config['sql_field_postcode'],
						);

					if ($this->config['sql_field_country'] !== NULL) {
						$fields[] = $this->config['sql_field_country'];
					}

					$fields = array_merge($fields, $this->config['sql_field_address']);

					$db = $this->db_get();

					$rst = $db->select($this->config['sql_table'], $fields, $this->config['sql_where']);
					while ($row = $db->fetch_assoc($rst)) {

						//--------------------------------------------------
						// Country

							if ($this->config['sql_field_country'] !== NULL) {
								$country = $row[$this->config['field_country']];
							} else {
								$country = NULL;
							}

						//--------------------------------------------------
						// Full address search

							$search = array();
							foreach (array_merge($this->config['sql_field_address'], array($this->config['field_postcode'])) as $field) {
								$val = trim($row[$field]);
								if ($val != '') {
									$search[] = $val;
								}
							}
							$search = implode($search, ', ');

							$result = $this->search($search, $country);

						//--------------------------------------------------
						// Postcode only search

							if ($result === NULL) {
								$result = $this->search($row[$this->config['field_postcode']], $country);
							}

						//--------------------------------------------------
						// Update

							if ($result !== NULL) {

								$values = array();
								$values[$this->config['field_latitude']] = $result['latitude'];
								$values[$this->config['field_longitude']] = $result['longitude'];

								$sql_where = '
									' . $this->config['sql_field_id'] . ' = "' . $db->escape($row[$this->config['field_id']]) . '" AND
									' . $this->config['sql_where'];

								$db->update($this->config['sql_table'], $values, $sql_where);

							}

					}

			}

			public function update_location($id) {

				//--------------------------------------------------
				// Update

					$old_sql_where = $this->config['sql_where'];

					$db = $this->db_get();

					$this->config['sql_where'] = '
						' . $this->config['sql_field_id'] . ' = "' . $db->escape($id) . '" AND
						' . $this->config['sql_where'];

					$this->update_locations();

					$this->config['sql_where'] = $old_sql_where;

			}

	}

//--------------------------------------------------
// Tables exist

	if (SERVER == 'stage') {

		debug_require_db_table('nearest_search_cache', '
				CREATE TABLE [TABLE] (
					search varchar(200) NOT NULL,
					country varchar(10) NOT NULL,
					latitude double NOT NULL,
					longitude double NOT NULL,
					accuracy int(11) NOT NULL,
					created datetime NOT NULL,
					edited datetime NOT NULL,
					PRIMARY KEY (search,country)
				);');

		debug_require_db_table('nearest_outcode', '
				CREATE TABLE [TABLE] (
					outcode varchar(5) NOT NULL default \'\',
					x int(11) NOT NULL default \'0\',
					y int(11) NOT NULL default \'0\',
					latitude int(11) NOT NULL default \'0\',
					longitude int(11) NOT NULL default \'0\',
					district varchar(40) NOT NULL default \'\',
					PRIMARY KEY (outcode),
					KEY x (x,y)
				);');

	}

?>