<?php

/***************************************************

	//--------------------------------------------------
	// Site config



	//--------------------------------------------------
	// Example setup

		//--------------------------------------------------
		// Simple

			// config::set('nearest.table_sql', 'stores');

			$nearest = new nearest();

		//--------------------------------------------------
		// Profile support

			config::set('nearest.profile.table_sql', 'stores');

			$nearest = new nearest('profile');

		//--------------------------------------------------
		// Custom

			$nearest = new nearest(array(
					'table_sql' => 'stores',
					'max_results' => 10,
				));

***************************************************/

	class nearest_base extends check {

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
				// Default config

					$this->config = array(
							'table_sql' => DB_PREFIX . 'location',
							'where_sql' => 'true',
							'field_id_sql' => 'id',
							'field_latitude_sql' => 'latitude',
							'field_longitude_sql' => 'longitude',
							'field_address_sql' => array(),
							'field_postcode_sql' => 'postcode',
							'field_country_sql' => NULL,
							'extra_fields_sql' => array(),
							'max_results' => 5,
							'max_km' => 40, // 25 miles
							'min_results' => 0,
						);

				//--------------------------------------------------
				// Set config

					if (is_string($config)) {
						$profile = $config;
					} else if (isset($config['profile'])) {
						$profile = $config['profile'];
					} else {
						$profile = NULL;
					}

					if (!is_array($config)) {
						$config = array();
					}

					$prefix = 'nearest';
					if ($profile !== NULL) {
						$prefix .= '.' . $profile;
						$config['profile'] = $profile;
					}

					$site_config = config::get_all($prefix);

					if (count($site_config) > 0) {

						$config = array_merge($site_config, $config);

					} else if ($profile !== NULL) {

						exit_with_error('Cannot find nearest profile "' . $profile . '" (' . $prefix . '.*)');

					}

					$this->config_set($config);

			}

		//--------------------------------------------------
		// Configuration

			public function config_set($config, $value = NULL) {

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
						if (substr($key, 0, 6) == 'field_' && substr($key, -4) == '_sql' && is_string($value)) {
							$key_short = substr($key, 0, -4);
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
							$this->config['field_id_sql'],
							$this->config['field_latitude_sql'],
							$this->config['field_longitude_sql'],
						);

					$fields = array_merge($fields, $this->config['extra_fields_sql']);

					$sql_where = '
						' . $this->config['field_latitude_sql'] . ' != 0 AND
						' . $this->config['field_longitude_sql'] . ' != 0 AND
						' . $this->config['where_sql'];

					$db = $this->db_get();

					$rst = $db->select($this->config['table_sql'], $fields, $sql_where);
					while ($row = $rst->fetch_assoc()) {

						$radians_lat2 = $row[$this->config['field_latitude']] * $pi / 180;
						$radians_long2 = $row[$this->config['field_longitude']] * $pi / 180;

						$R = 6371; // earth's mean radius in km
						$d_lat  = $radians_lat2 - $radians_lat1;
						$d_long = $radians_long2 - $radians_long1;

						$a = sin($d_lat/2) * sin($d_lat/2) + cos($radians_lat1) * cos($radians_lat2) * sin($d_long/2) * sin($d_long/2);
						$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
						$distance = $R * $c;

						$info = array(
								'latitude' => $row[$this->config['field_latitude']],
								'longitude' => $row[$this->config['field_longitude']],
								'distance' => $distance,
							);

						foreach ($this->config['extra_fields_sql'] as $field) {
							$info[$field] = $row[$field];
						}

						$locations[$row[$this->config['field_id']]] = $info;

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

							$contents = file_get_contents(url('http://maps.google.com/maps/api/geocode/xml', array(
									'address' => $search_query,
									'sensor' => 'false',
									'region' => $country,
								)));

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

						$db->query('SELECT 1 FROM ' . DB_PREFIX . 'nearest_outcode LIMIT 1');
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

						$values_update = array(
							'search'    => $search_query,
							'country'   => $country,
							'latitude'  => $latitude,
							'longitude' => $longitude,
							'accuracy'  => $accuracy,
							'edited'    => date('Y-m-d H:i:s'),
						);

						$values_insert = $values_update;
						$values_insert['created'] = date('Y-m-d H:i:s');

						$db->insert(DB_PREFIX . 'nearest_search_cache', $values_insert, $values_update);

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
							$this->config['field_id_sql'],
							$this->config['field_postcode_sql'],
							$this->config['field_latitude'],
							$this->config['field_longitude'],
						);

					if ($this->config['field_country_sql'] !== NULL) {
						$fields[] = $this->config['field_country_sql'];
					}

					$fields = array_merge($fields, $this->config['field_address_sql']);

					$db = $this->db_get();

					$rst = $db->select($this->config['table_sql'], $fields, $this->config['where_sql']);
					while ($row = $rst->fetch_assoc()) {

						$existing_accuracy_latitude = strlen($row[$this->config['field_latitude']]);
						$existing_accuracy_longitude = strlen($row[$this->config['field_longitude']]);

						if ($existing_accuracy_latitude <= 15 && $existing_accuracy_longitude <= 15) {

							//--------------------------------------------------
							// Country

								if ($this->config['field_country_sql'] !== NULL) {
									$country = $row[$this->config['field_country']];
								} else {
									$country = NULL;
								}

							//--------------------------------------------------
							// Full address search

								$search = array();
								foreach (array_merge($this->config['field_address_sql'], array($this->config['field_postcode_sql'])) as $field) {
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
										' . $this->config['field_id'] . ' = "' . $db->escape($row[$this->config['field_id_sql']]) . '" AND
										' . $this->config['where_sql'];

									$db->update($this->config['table_sql'], $values, $sql_where);

								}

						}

					}

			}

			public function update_location($id) {

				//--------------------------------------------------
				// Update

					$old_sql_where = $this->config['where_sql'];

					$db = $this->db_get();

					$this->config['where_sql'] = '
						' . $this->config['field_id_sql'] . ' = "' . $db->escape($id) . '" AND
						' . $this->config['where_sql'];

					$this->update_locations();

					$this->config['where_sql'] = $old_sql_where;

			}

	}

//--------------------------------------------------
// Tables exist

	if (config::get('debug.level') > 0) {

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