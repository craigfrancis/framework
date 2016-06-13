<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/nearest/
//--------------------------------------------------

	class nearest_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = array();
			private $db_link;

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Config

					$default_config = array_merge(array(
							'profile'             => NULL,
							'table_sql'           => DB_PREFIX . 'table',
							'where_sql'           => 'true',
							'field_id_sql'        => 'id',
							'field_latitude_sql'  => 'latitude',
							'field_longitude_sql' => 'longitude',
							'field_address_sql'   => array(),
							'field_postcode_sql'  => 'postcode',
							'field_country_sql'   => NULL,
							'extra_fields_sql'    => array(),
							'max_results'         => 5,
							'max_km'              => NULL,
							'max_miles'           => NULL,
							'min_results'         => 0,
						), config::get_all('nearest.default'));

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

					if ($profile) {

						$profile_prefix = 'nearest.' . $profile;
						$profile_config = config::get_all($profile_prefix);

						if (count($profile_config) > 0) {
							$config = array_merge($profile_config, $config);
						} else {
							exit_with_error('Cannot find config for nearest profile "' . $profile . '" (' . $profile_prefix . '.*)');
						}

					}

					$this->config = array_merge($default_config, $config);

					if (!$this->config['max_km'] === NULL) { // Could be 0 to get all locations.
						if ($this->config['max_miles'] > 0) {
							$this->config['max_km'] = ($this->config['max_miles'] * 1.60934);
						} else {
							$this->config['max_km'] = 40; // 25 miles
						}
					}

				//--------------------------------------------------
				// Create 'field_x' values from 'field_x_sql'

					foreach ($this->config as $key => $value) {
						if (substr($key, 0, 6) == 'field_' && substr($key, -4) == '_sql' && is_string($value)) {
							$key_short = substr($key, 0, -4);
							if (!isset($this->config[$key_short])) {
								if (($pos = strpos($value, '.')) !== false) { // Remove table alias "a.name"
									$value = substr($value, ($pos + 1));
								}
								$this->config[$key_short] = $value;
							}
						}
					}

			}

			private function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

		//--------------------------------------------------
		// Return nearest locations based on search

			public function locations_nearest($search, $country = NULL) {

				$locations = $this->locations_get();

				return $this->locations_limited($locations, $search, $country);

			}

			public function locations_limited($locations, $search, $country = NULL) {

				$k = 0;
				$results = array();
				$locations = $this->locations_distance($locations, $search, $country);

				array_key_sort($locations, 'distance', SORT_NUMERIC, SORT_ASC);

				foreach ($locations as $location) {

					if ($location['distance'] === NULL) {
						continue;
					}

					$k++;

					if ((($this->config['max_results'] > 0 && $k > $this->config['max_results']) || ($this->config['max_km'] > 0 && $location['distance'] > $this->config['max_km'])) && ($this->config['min_results'] == 0 || $k > $this->config['min_results'])) {
						break;
					}

					$results[$location[$this->config['field_id']]] = $location;

				}

				return $results;

			}

			public function locations_distance($locations, $search, $country = NULL) {

				//--------------------------------------------------
				// Find location

					if (is_array($search)) {

						$point = $search; // Already a known point.

					} else {

						$point = $this->search($search, $country);

						if ($point === NULL) {
							return array(); // Invalid search, no results
						}

					}

				//--------------------------------------------------
				// Apply distance to $locations

					$pi = pi();
					$radius = 6371; // earth's mean radius in km

					$radians_lat1 = ($point['latitude'] * $pi / 180);
					$radians_long1 = ($point['longitude'] * $pi / 180);

					foreach ($locations as $id => $location) {

						$latitude = $location[$this->config['field_latitude']];
						$longitude = $location[$this->config['field_longitude']];

						if ($latitude == 0 && $longitude == 0) { // Technically valid, but more likely to be unknown.

							$locations[$id]['distance'] = NULL;

						} else {

							$radians_lat2 = ($latitude * $pi / 180);
							$radians_long2 = ($longitude * $pi / 180);

							$d_lat  = $radians_lat2 - $radians_lat1;
							$d_long = $radians_long2 - $radians_long1;

							$a = sin($d_lat/2) * sin($d_lat/2) + cos($radians_lat1) * cos($radians_lat2) * sin($d_long/2) * sin($d_long/2);
							$c = 2 * atan2(sqrt($a), sqrt(1 - $a));

							$locations[$id]['distance'] = ($radius * $c);

						}

					}

				//--------------------------------------------------
				// Return

					return $locations;

			}

			private function locations_get() {

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

				$db->select($this->config['table_sql'], $fields, $sql_where);

				return $db->fetch_all();

			}

		//--------------------------------------------------
		// Search to return co-ordinates

			public function search($search, $country = NULL, $slow = false) {

				//--------------------------------------------------
				// Country

					$country = strtolower($country);

					if ($country == NULL || $country == 'uk') {
						$country = 'gb'; // Google Maps
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
						$postcode = format_postcode($search, 'UK');
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
										' . DB_PREFIX . 'system_nearest_cache
									WHERE
										search = "' . $db->escape($search_query) . '" AND
										country = "' . $db->escape($country) . '" AND
										edited > "' . $db->escape(date('Y-m-d H:i:s', strtotime('-1 week'))) . '"');

						if ($row = $db->fetch_row()) {
							$latitude = $row['latitude'];
							$longitude = $row['longitude'];
							$accuracy = $row['accuracy'];
							$cached = true;
						}

					}

				//--------------------------------------------------
				// Try Google

					$google_maps_key = config::get('nearest.gm_key'); // https://developers.google.com/maps/documentation/geocoding/#api_key

					if ($latitude === NULL && $google_maps_key !== NULL) {

						//--------------------------------------------------
						// Ask

							$start = microtime(true);

							$contents = file_get_contents(url('https://maps.google.com/maps/api/geocode/xml', array(
									'address' => $search_query,
									'sensor' => 'false',
									'components' => 'country:' . $country,
									'region' => $country,
									'key' => ($google_maps_key === '' ? NULL : $google_maps_key),
								)));

							if ($slow) {
								usleep(200000); // 200ms, to keep Google happy (limited to 5 requests per second, or 2500 per day).
							}

							if (function_exists('debug_log_time')) {
								debug_log_time('GEO', round((microtime(true) - $start), 3));
							}

						//--------------------------------------------------
						// Extract

							$result = simplexml_load_string($contents);

							if (isset($result->status) && isset($result->result) && $result->status == 'OK') {
								if (strval($result->result->type) == 'country') {
									// A query containing a component filter will only return the geocoding results that match the filter. If no matches are found, the geocoder will return a result that matches the filter itself.
								} else {
									$latitude = floatval($result->result->geometry->location->lat);
									$longitude = floatval($result->result->geometry->location->lng);
									$accuracy = ($result->result->geometry->location_type == 'ROOFTOP' ? 5 : 4);
								}
							}

					}

				//--------------------------------------------------
				// Cache 3rd party response

					if ($latitude !== NULL && $cached == false) {

						$now = new timestamp();

						$values_update = array(
							'search'    => $search_query,
							'country'   => $country,
							'latitude'  => $latitude,
							'longitude' => $longitude,
							'accuracy'  => $accuracy,
							'edited'    => $now,
						);

						$values_insert = $values_update;
						$values_insert['created'] = $now;

						$db->insert(DB_PREFIX . 'system_nearest_cache', $values_insert, $values_update);

					}

				//--------------------------------------------------
				// Try outer-code (backup)

					if ($latitude === NULL) {

						$db->query('SELECT 1 FROM ' . DB_PREFIX . 'system_nearest_outcode LIMIT 1');
						if ($db->num_rows() == 0) {

							$fields = array('outcode', 'latitude', 'longitude');
							$field_count = count($fields);

							if (($handle = fopen(FRAMEWORK_ROOT . '/library/class/nearest/outcode.csv', 'r')) !== false) {
								while (($data = fgetcsv($handle, 1000, ',')) !== false) {
									if (count($data) == $field_count) {
										$db->insert(DB_PREFIX . 'system_nearest_outcode', array_combine($fields, $data));
									} else {
										exit_with_error('Invalid number of fields in "outcode.csv"', debug_dump($data));
									}
								}
								fclose($handle);
							}

						}

						$db->query('SELECT
										latitude,
										longitude
									FROM
										' . DB_PREFIX . 'system_nearest_outcode
									WHERE
										outcode = "' . $db->escape($postcode === NULL ? $search : substr($postcode, 0, -4)) . '"
									LIMIT
										1');

						if ($row = $db->fetch_row()) {
							$latitude = $row['latitude'];
							$longitude = $row['longitude'];
							$accuracy = 3;
						}

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

			public function update_init($limit = 30) {
				return $this->update_locations(0, $limit);
			}

			public function update_all() {
				return $this->update_locations(-1);
			}

			public function update_locations($min_accuracy = 3, $limit = NULL) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

				//--------------------------------------------------
				// Fields

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

				//--------------------------------------------------
				// Where

					$where_sql = $this->config['where_sql'];

					$where_sql .= ' AND
						' . $db->escape_field($this->config['field_postcode_sql']) . ' != ""';

					if ($min_accuracy == 0) {
						$where_sql .= ' AND
							' . $db->escape_field($this->config['field_latitude']) . ' = 0 AND
							' . $db->escape_field($this->config['field_longitude']) . ' = 0';
					}

				//--------------------------------------------------
				// Search

					$options = array();
					if ($limit !== NULL) {
						$options['limit_sql'] = $limit;
					}

					$k = 0;
					$updates = array();

					$db->select($this->config['table_sql'], $fields, $where_sql, $options);
					foreach ($db->fetch_all() as $row) {

						$k++;
						$update = -1; // No change

						$existing_accuracy_latitude  = strlen(substr(strrchr($row[$this->config['field_latitude']],  '.'), 1));
						$existing_accuracy_longitude = strlen(substr(strrchr($row[$this->config['field_longitude']], '.'), 1));

						if (($min_accuracy == -1) || ($existing_accuracy_latitude <= $min_accuracy && $existing_accuracy_longitude <= $min_accuracy)) {

							//--------------------------------------------------
							// Country

								if ($this->config['field_country_sql'] !== NULL) {
									$country = $row[$this->config['field_country']];
								} else {
									$country = NULL;
								}

							//--------------------------------------------------
							// Full address search

								if (count($this->config['field_address_sql']) > 0) {

									$search = array();
									foreach (array_merge($this->config['field_address_sql'], array($this->config['field_postcode_sql'])) as $field) {
										$val = trim($row[$field]);
										if ($val != '') {
											$search[] = $val;
										}
									}
									$search = implode($search, ', ');

									$result = $this->search($search, $country, ($k > 1));

								} else {

									$result = NULL;

								}

							//--------------------------------------------------
							// Postcode only search

								if ($result === NULL) {
									$result = $this->search($row[$this->config['field_postcode']], $country, ($k > 1));
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

									$update = 1;

								} else {

									$update = 0;

								}

						}

						$updates[$row[$this->config['field_id_sql']]] = $update;

					}

				//--------------------------------------------------
				// Return

					return $updates;

			}

			public function update_location($id) {

				//--------------------------------------------------
				// Update

					$old_sql_where = $this->config['where_sql'];

					$db = $this->db_get();

					$this->config['where_sql'] = '
						' . $this->config['field_id_sql'] . ' = "' . $db->escape($id) . '" AND
						' . $this->config['where_sql'];

					$results = $this->update_locations(-1);

					$this->config['where_sql'] = $old_sql_where;

				//--------------------------------------------------
				// Return

					return $results;

			}

	}

//--------------------------------------------------
// Tables exist

	if (config::get('debug.level') > 0) {

		debug_require_db_table(DB_PREFIX . 'system_nearest_cache', '
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

		debug_require_db_table(DB_PREFIX . 'system_nearest_outcode', '
				CREATE TABLE [TABLE] (
					outcode varchar(5) NOT NULL,
					latitude double NOT NULL,
					longitude double NOT NULL,
					PRIMARY KEY (outcode)
				);');

	}

?>