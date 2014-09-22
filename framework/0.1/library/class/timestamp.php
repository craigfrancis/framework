<?php

	class timestamp_base extends datetime {

		protected $formats = array(
			);

		public function __construct($time = 'now', $timezone = NULL) {
			if ($timezone == 'db') {
				parent::__construct($time, new DateTimeZone('UTC'));
				parent::setTimezone(new DateTimeZone(config::get('output.timezone')));
			} else {
				if ($timezone === NULL) {
					$timezone = config::get('output.timezone');
				}
				if (is_numeric($time)) {
					parent::__construct('@' . $time); // Numbers are now always timestamps (not '20080701')
					parent::setTimezone(new DateTimeZone($timezone)); // Timezone is ignored on construct.
				} else {
					parent::__construct($time, new DateTimeZone($timezone));
				}
			}
		}

		public function format($format) {
			if ($format == 'db') {
				$timezone = parent::getTimezone();
				parent::setTimezone(new DateTimeZone('UTC'));
				$output = parent::format('Y-m-d H:i:s');
				parent::setTimezone($timezone);
				return $output;
			} else {
				if (isset($this->formats[$format])) {
					$format = $this->formats[$format];
				}
				return parent::format($format);
			}
		}

		public function html($format) {
			return '<time datetime="' . html($this->format('c')) . '">' . html($this->format($format)) . '</time>';
		}

		public function business_days_add($business_days) {
			$business_days = intval($business_days); // Decrement does not work on strings
			$holidays = timestamp::holidays_get();
			while ($business_days > 0) {
				$this->modify('+1 day');
				if ($this->format('N') < 6 && !in_array($this->format('Y-m-d'), $holidays)) {
					$business_days--;
				}
			}
			return $this;
		}

		public function business_day_select() {
			$holidays = timestamp::holidays_get();
			while ($this->format('N') >= 6 || in_array($this->format('Y-m-d'), $holidays)) {
				$this->modify('+1 day');
			}
			return $this;
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

			while ($this < $end) {
				if ($this->format('N') < 6 && !in_array($this->format('Y-m-d'), $holidays)) {
					$business_days++;
				}
				$this->modify('+1 day');
			}

			return $business_days;

		}

		static function holidays_update() {

			//--------------------------------------------------
			// New

				$xml = simplexml_load_file('https://www.devcf.com/a/api/holidays/');

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
							sh.date >= "' . $db->escape(date('Y-m-d', $earliest)) . '"';

				foreach ($db->fetch_all($sql) as $row) {
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

		static function holidays_get() {

			$holidays = config::get('cache.business_days');

			if ($holidays === NULL) {

				timestamp::holidays_check_table();

				$holidays = array();

				$db = db_get();

				$db->query('SELECT
								sh.date
							FROM
								' . DB_PREFIX . 'system_holiday AS sh');

				while ($row = $db->fetch_row()) {
					$holidays[] = $row['date'];
				}

				config::set('cache.business_days', $holidays);

			}

			return $holidays;

		}

		static function holidays_check_table() {

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

		class timestamp extends timestamp_base {

			protected $formats = array(
					'iso' => 'Y-m-d H:i:s',
					'human' => 'l jS F Y, g:i:sa',
				);

		}

		$timestamp = new timestamp();
		debug($timestamp->format('human'));

		$timestamp = new timestamp(time());
		debug($timestamp->format('human'));

		echo '<hr />';

		$timestamp = new timestamp('2014W04-2');
		debug($timestamp->format('human'));
		$timestamp->modify('+3 days');
		debug($timestamp->format('human'));
		debug($timestamp->html('l jS F Y'));

		echo '<hr />';

		$timestamp = new timestamp('2014-09-22 16:37:15', 'db');
		debug($timestamp->format('human'));
		debug($timestamp->format('iso'));
		debug($timestamp->format('db'));
		debug($timestamp->html('human'));

		exit();

	}

?>