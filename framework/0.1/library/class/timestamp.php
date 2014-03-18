<?php

	class timestamp extends datetime {

		public function __construct($time = 'now', $timezone = NULL) {
			if (is_numeric($time)) {
				$time = '@' . $time; // Numbers are always timestamps (not '20080701')
			}
			if ($timezone !== NULL) {
				parent::__construct($time, $timezone);
			} else {
				parent::__construct($time); // PHP 5.3 support, does not like NULL
			}
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

			$end = new timestamp($end);

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

	}

?>