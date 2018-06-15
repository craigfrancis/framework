<?php

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
				} catch (Exception $e) {
					$this->null = true;
				}
			}

		//--------------------------------------------------
		// Output

			public function null() {
				return $this->null; // Can be false, true, or the value that caused it to be NULL (e.g. 0000-00-00)
			}

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

			public function html($format_text, $null_html = NULL, $format_title = NULL) {
				if ($this->null) {
					return $null_html;
				} else {
					$attribute_value = $this->format('c');
					if (($pos = strpos($attribute_value, 'T00:00:00')) !== false) {
						$attribute_value = substr($attribute_value, 0, $pos);
					}
					return '<time datetime="' . html($attribute_value) . '"' . ($format_title ? ' title="' . html($this->format($format_title)) . '"' : '') . '>' . html($this->format($format_text)) . '</time>';
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

			public function modify($modify, $show_error = true) {
				// if (SERVER == 'stage' && $show_error) {
				// 	$called_from = debug_backtrace();
				// 	exit_with_error('Please use $timestamp = $now->clone(xxx); instead of $timestamp->modify(xxx)'); // TODO: Enable when most sites have been converted (2018-06-14)
				// }
				if (!$this->null) {
					return parent::modify($modify);
				}
			}

			public function _debug_dump() {
				if ($this->null) {
					return 'NULL';
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

			public function business_days_add($business_days) {
				$business_days = intval($business_days); // Decrement does not work on strings
				$holidays = timestamp::holidays_get();
				$timestamp = $this->clone();
				while ($business_days != 0) {
					$timestamp = $timestamp->clone($business_days > 0 ? '+1 day' : '-1 day');
					if ($timestamp->format('N') < 6 && !in_array($timestamp->format('Y-m-d'), $holidays)) {
						$business_days += ($business_days > 0 ? -1 : 1);
					}
				}
				return $timestamp;
			}

			public function business_day_next() {
				$holidays = timestamp::holidays_get();
				$timestamp = $this->clone();
				while ($timestamp->format('N') >= 6 || in_array($timestamp->format('Y-m-d'), $holidays)) {
					$timestamp = $timestamp->clone('+1 day');
				}
				return $timestamp;
			}

			public function business_days_diff($end) {

				if (!is_object($end) || !is_a($end, 'timestamp')) {
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
				$holidays = timestamp::holidays_get();
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

			public static function holidays_update() {

				//--------------------------------------------------
				// New

					$xml = file_get_contents('https://www.devcf.com/a/api/holidays/');
					$xml = simplexml_load_string($xml); // simplexml_load_file is blocked by libxml_disable_entity_loader()

					$holidays = array();
					$earliest = NULL;

					foreach ($xml->holiday as $holiday) {

						$date = strval($holiday['date']);

						$timestamp = strtotime($date);
						if ($earliest === NULL || $timestamp < $earliest) {
							$earliest = $timestamp;
						}

						$holidays[strval($holiday['country'])][$date] = strval($holiday['name']);

					}

				//--------------------------------------------------
				// Current

					timestamp::holidays_check_table();

					$db = db_get();

					$sql = 'SELECT
								sh.country,
								sh.date
							FROM
								' . DB_PREFIX . 'system_holiday AS sh
							WHERE
								sh.date >= ?';

					$parameters = array();
					$parameters[] = array('s', date('Y-m-d', $earliest));

					foreach ($db->fetch_all($sql, $parameters) as $row) {
						unset($holidays[$row['country']][$row['date']]);
					}

				//--------------------------------------------------
				// Add

					foreach ($holidays as $country_code => $country_holidays) {
						foreach ($country_holidays as $holiday_date => $holiday_name) {

							$db->insert(DB_PREFIX . 'system_holiday', array(
									'country' => $country_code,
									'date' => $holiday_date,
									'name' => $holiday_name,
								));

						}
					}

			}

			public static function holidays_get() {

				$holidays = config::get('timestamp.holiday_cache');

				if ($holidays === NULL) {

					timestamp::holidays_check_table();

					$holidays = array();

					$db = db_get();

					$sql = 'SELECT
								sh.date
							FROM
								' . DB_PREFIX . 'system_holiday AS sh';

					foreach ($db->fetch_all($sql) as $row) {
						$holidays[] = $row['date'];
					}

					config::set('timestamp.holiday_cache', $holidays);

				}

				return $holidays;

			}

			protected static function holidays_check_table() {

				if (config::get('debug.level') > 0) {

					debug_require_db_table(DB_PREFIX . 'system_holiday', '
							CREATE TABLE [TABLE] (
								`country` enum("uk") NOT NULL,
								`date` date NOT NULL,
								`name` tinytext NOT NULL,
								PRIMARY KEY (`country`, `date`)
							);');

				}

			}

	}

	if (false) {

		config::set('timestamp.formats', array(
					'iso' => 'Y-m-d H:i:s',
					'human' => 'l jS F Y, g:i:sa',
				));

		class timestamp extends timestamp_base {
		}

		$timestamp = new timestamp();
		debug($timestamp->format('human'));

		$timestamp = new timestamp(time());
		debug($timestamp->format('human'));

		echo '<hr />';

		$timestamp = new timestamp('2014W04-2');
		debug($timestamp->format('human'));
		$timestamp = $timestamp->clone('+3 days');
		debug($timestamp->format('human'));
		debug($timestamp->html('l jS F Y'));

		echo '<hr />';

		$timestamp = timestamp::createFromFormat('d/m/y H:i:s', '01/07/03 12:01:02');
		debug($timestamp->format('l jS F Y g:i:sa'));
		debug($timestamp->format('db'));

		echo '<hr />';

		$timestamp = new timestamp('2014-09-22 16:37:15', 'db');
		debug($timestamp->format('human'));
		debug($timestamp->format('iso'));
		debug($timestamp->format('db'));
		debug($timestamp->html('human'));

		exit();

	}

?>