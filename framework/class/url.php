<?php

	class url {

		// init function to get the path/query
		// function to print the object

		function add_variable($variable, $value = NULL) {

			//--------------------------------------------------
			// Split

				if ($url === NULL) {
					$path = (isset($_SERVER['SCRIPT_URL']) ? $_SERVER['SCRIPT_URL'] : '');
					$query = (isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
				} else {
					if (($pos = strpos($url, '?')) !== false) {
						$path = substr($url, 0, $pos);
						$query = substr($url, ($pos + 1));
					} else {
						$path = $url;
						$query = '';
					}
				}

			//--------------------------------------------------
			// Remove or replace variable

				if (preg_match('/(.*)(^|&)' . preg_quote($variable, '/') . '=[^&]*(&|$)(.*)/', $query, $m)) {
					if ($value !== NULL) {
						$query = $m[1] . $m[2] . $variable . '=' . urlencode($value) . $m[3] . $m[4];
					} else {
						$query = $m[1] . ($m[1] != '' && $m[4] != '' ? $m[2] : '') . ($m[1] != '' && $m[2] == '' && $m[4] != '' ? $m[3] : '') . $m[4];
					}
				} else if ($value !== NULL) {
					$query .= ($query == '' ? '' : '&') . $variable . '=' . urlencode($value);
				}

			//--------------------------------------------------
			// Return

				return $path . ($query == '' ? '' : '?') . $query;

		}

	}

?>