<?php

	// require_once(FRAMEWORK_ROOT . '/library/tests/class-timestamp.php');

	class timestamp_base extends datetime {

		//--------------------------------------------------
		// Variables

			protected $null = false;

		//--------------------------------------------------
		// Setup

			public function __construct($time = 'now', $timezone = NULL) {
				try {
					if ($time === NULL || $time === '0000-00-00 00:00:00' || $time === '0000-00-00') {
						$this->null = ($time === NULL ? true : $time);
					} else if ($timezone == 'db') {
						parent::__construct($time, new DateTimeZone(self::getDbTimezone()));
						parent::setTimezone(new DateTimeZone(config::get('output.timezone')));
					} else {
						if ($timezone === NULL) {
							$timezone = config::get('output.timezone');
						}
						if (is_numeric($time)) {
							parent::__construct('@' . $time); // Numbers should always be timestamps (ignore '20080701')
							parent::setTimezone(new DateTimeZone($timezone)); // Timezone is ignored on construct.
						} else {
							parent::__construct($time, new DateTimeZone($timezone));
						}
					}
				} catch (exception $e) {
					$this->null = true;
				}
			}

		//--------------------------------------------------
		// Output

			public function null() {
				return $this->null; // Can be false, true, or the value that caused it to be NULL (e.g. 0000-00-00)
			}

			#[ReturnTypeWillChange]
			public function format($format, $null_value = NULL) {
				if ($this->null) {
					return $null_value;
				} else if ($format == 'db' || $format == 'db-date') {
					$timezone = parent::getTimezone();
					parent::setTimezone(new DateTimeZone(self::getDbTimezone()));
					$output = parent::format($format == 'db' ? 'Y-m-d H:i:s' : 'Y-m-d');
					@parent::setTimezone($timezone); // Avoid PHP 5.3 bug 45543 (can not set timezones without ID)
					return $output;
				} else {
					return parent::format(config::array_get('timestamp.formats', $format, $format));
				}
			}

			public function html($format_text, $null_html = NULL, $attributes = []) {
				if ($this->null) {
					return $null_html;
				} else {
					if (!is_array($attributes)) {
						$attributes = ['title' => $attributes];
					}
					$attributes['datetime'] = $this->format('c');
					if (($pos = strpos($attributes['datetime'], 'T00:00:00')) !== false) {
						$attributes['datetime'] = substr($attributes['datetime'], 0, $pos);
					}
					$html = html_tag('time', $attributes) . html($this->format($format_text)) . '</time>';
					return new html_safe_value($html); // Not exactly safe, as the attributes are not checked.
				}
			}

			function __call($method, $arguments) {
				if ($method === 'clone') {
					return call_user_func_array(array($this, '_clone'), $arguments);
				}
			}

			public function _clone($modify = NULL) { // TODO: Rename to "clone", and remove the "__call" method when Steve isn't using PHP5
				$clone = clone $this;
				if ($modify && !$this->null) {
					$clone->modify($modify, false);
				}
				return $clone;
			}

			#[ReturnTypeWillChange]
			public function modify($modify, $show_error = true) {
				// if (SERVER == 'stage' && $show_error) {
				// 	$called_from = debug_backtrace();
				// 	exit_with_error('Please use $timestamp = $now->clone(xxx); instead of $timestamp->modify(xxx)'); // TODO: Enable when most sites have been converted (2018-06-14)
				// }
				if (!$this->null) {
					return parent::modify($modify);
				} else {
					return new timestamp(NULL);
				}
			}

			public function _debug_dump() {
				if ($this->null) {
					return 'timestamp(NULL)';
				} else {
					return $this->format('Y-m-d H:i:s (e)');
				}
			}

			public function __toString() {
				if ($this->null) {
					return ($this->null === true ? '0000-00-00 00:00:00' : $this->null); // Cannot return NULL, but try to return the original NULL-ish value (e.g. "0000-00-00")
				} else {
					return $this->format('db');
				}
			}

		//--------------------------------------------------
		// Create from format

			#[ReturnTypeWillChange]
			static function createFromFormat($format, $time, $timezone = NULL) {
				if ($timezone === NULL) {
					$timezone = config::get('output.timezone');
				}
				$parsed = parent::createFromFormat($format, $time, new DateTimeZone($timezone));
				if ($parsed) {
					$parsed->setTimezone(new DateTimeZone(self::getDbTimezone()));
					return new timestamp($parsed->format('Y-m-d H:i:s'), 'db');
				} else {
					return false;
				}
			}

			static function getDbTimezone() { // Match formatting of getTimezone
				return config::get('timestamp.db_timezone', 'UTC');
			}

		//--------------------------------------------------
		// Business days

			public function business_days_add($business_days, $country = NULL) {
				$business_days = intval($business_days); // Decrement does not work on strings
				$holidays = timestamp::holidays_get($country);
				$timestamp = $this->clone();
				while ($business_days != 0) {
					$timestamp = $timestamp->clone($business_days > 0 ? '+1 day' : '-1 day');
					if ($timestamp->format('N') < 6 && !in_array($timestamp->format('Y-m-d'), $holidays)) {
						$business_days += ($business_days > 0 ? -1 : 1);
					}
				}
				return $timestamp;
			}

			public function business_day_next($country = NULL) {
				$holidays = timestamp::holidays_get($country);
				$timestamp = $this->clone();
				while ($timestamp->format('N') >= 6 || in_array($timestamp->format('Y-m-d'), $holidays)) {
					$timestamp = $timestamp->clone('+1 day');
				}
				return $timestamp;
			}

			public function business_days_diff($end, $country = NULL) {

				if (!($end instanceof timestamp)) {
					$end = new timestamp($end);
				}

				if ($this > $end) {
					return 0;
				}

				$diff = $this->diff($end);
				if ($diff->days > 365*15) {
					exit_with_error('Max diff exceeded ("' . $this->format('Y-m-d') . '" to "' . $end->format('Y-m-d') . '")');
				}

				$business_days = 0;
				$holidays = timestamp::holidays_get($country);
				$timestamp = $this->clone();

				while ($timestamp < $end) {
					if ($timestamp->format('N') < 6 && !in_array($timestamp->format('Y-m-d'), $holidays)) {
						$business_days++;
					}
					$timestamp = $timestamp->clone('+1 day');
				}

				return $business_days;

			}

		//--------------------------------------------------
		// Holiday support functions

			public static function holidays_update($countries = []) {

				//--------------------------------------------------
				// Config

					$now = new timestamp();

					$db = db_get();

					timestamp::holidays_check_table();

					if (count($countries) == 0) {
						$countries = $db->enum_values(DB_PREFIX . 'system_holiday', 'country');
					}

				//--------------------------------------------------
				// Return

					$data_json = file_get_contents('https://www.gov.uk/bank-holidays.json');

					if (!$data_json) {
						throw new error_exception('Cannot return Bank Holidays from gov.uk');
					}

					$data_array = json_decode($data_json, true);

					if (!is_array($data_array)) {
						throw new error_exception('Cannot parse Bank Holidays from gov.uk', $data_json);
					}

				//--------------------------------------------------
				// Parse

					$holidays = [];
					$earliest = NULL;

					foreach ($data_array as $country => $data) {
						if (in_array($country, $countries)) {
							foreach ($data['events'] as $event) {

								$timestamp = new timestamp($event['date'], 'db');
								if ($earliest === NULL || $timestamp < $earliest) {
									$earliest = $timestamp;
								}

								$name = ucwords($event['title']);
								$name = str_replace("\u{2019}", "'", $name); // Replace "Right Single Quotation Mark"
								$name = trim($name);
								if (stripos($event['notes'], 'substitute') !== false) {
									$name .= ' (substitute)';
								}

								$holidays[$country][$timestamp->format('Y-m-d')] = $name;

							}
						}
					}

					if ($earliest === NULL) {
						throw new error_exception('Cannot find the first Bank Holidays from gov.uk', $data_json);
					}

					foreach ($countries as $country) {
						if (!isset($holidays[$country])) {
							throw new error_exception('Cannot find any Bank Holidays for "' . $country . '" from gov.uk', debug_dump(array_keys($holidays)));
						}
					}

				//--------------------------------------------------
				// Existing

					$matched_dates = array_fill_keys($countries, []);
					$unmatched_dates = [];

					$sql = 'SELECT
								*
							FROM
								' . DB_PREFIX . 'system_holiday AS sh
							WHERE
								sh.date >= ? AND
								sh.source = "gov.uk" AND
								sh.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = $earliest;

					foreach ($db->fetch_all($sql, $parameters) as $row) {

						if (isset($holidays[$row['country']][$row['date']]) && $holidays[$row['country']][$row['date']] == $row['name']) {

							$matched_dates[$row['country']][] = $row['date'];

						} else {

							$unmatched_dates[$row['country']][$row['date']] = $row;

						}

					}

				//--------------------------------------------------
				// Remove un-matched

					if (count($unmatched_dates) > 0) {

						$where_sql = [];
						$parameters = [];
						$parameters[] = $now;

						foreach ($unmatched_dates as $country => $dates) {
							foreach ($dates as $date => $row) {

								$where_sql[] = 'sh.country = ? AND sh.date = ?';

								$parameters[] = $country;
								$parameters[] = $date;

							}
						}

						$where_sql = $db->sql_implode($where_sql, 'OR');

						$sql = 'UPDATE
									' . DB_PREFIX . 'system_holiday AS sh
								SET
									sh.deleted = ?
								WHERE
									sh.source = "gov.uk" AND
									sh.deleted = "0000-00-00 00:00:00" AND
									(
										' . $where_sql . '
									)';

						$db->query($sql, $parameters);

					}

				//--------------------------------------------------
				// Add new

					foreach ($holidays as $country => $dates) {
						foreach ($dates as $date => $name) {
							if (!in_array($date, $matched_dates[$country])) {

								if (isset($unmatched_dates[$country][$date])) {
									$values = $unmatched_dates[$country][$date]; // If holiday name has changed, preserve any extra columns.
								} else {
									$values = [];
								}

								$values['country'] = $country;
								$values['date'] = $date;
								$values['name'] = $name;
								$values['source'] = 'gov.uk';
								$values['deleted'] = '0000-00-00 00:00:00';

								$db->insert(DB_PREFIX . 'system_holiday', $values);

							}
						}
					}

			}

			public static function holidays_get($country = NULL) {

				$holidays = config::get('timestamp.holiday_cache');

				if ($holidays === NULL) {

					$holidays = [];

					if (config::get('db.host') !== NULL) {

						timestamp::holidays_check_table();

						$db = db_get();

						$sql = 'SELECT
									sh.country,
									sh.date
								FROM
									' . DB_PREFIX . 'system_holiday AS sh
								WHERE
									sh.deleted = "0000-00-00 00:00:00"';

						foreach ($db->fetch_all($sql) as $row) {
							$holidays[$row['country']][] = $row['date'];
						}

					}

					config::set('timestamp.holiday_cache', $holidays);

				}

				if (!$country) {
					$country = config::get('timestamp.holiday_country', 'england-and-wales');
				}

				return ($holidays[$country] ?? []);

			}

			protected static function holidays_check_table() {

				if (config::get('debug.level') > 0) {

					debug_require_db_table(DB_PREFIX . 'system_holiday', '
							CREATE TABLE [TABLE] (
								`source` enum("", "gov.uk") NOT NULL,
								`country` enum("england-and-wales", "scotland", "northern-ireland") NOT NULL,
								`date` date NOT NULL,
								`name` tinytext NOT NULL,
								`deleted` datetime NOT NULL,
								PRIMARY KEY (`country`, `date`, `deleted`)
							);');

				}

			}

	}

?>