<?php

	function array_key_sort(&$array, $key, $sort_flags = SORT_STRING, $sort_order = SORT_ASC) { // Sort an array by a key (https://bugs.php.net/bug.php?id=68622)
		$array_key_sort = new array_key_sort($key);
		switch ($sort_flags & ~SORT_FLAG_CASE) { // ref https://github.com/php/php-src/blob/master/ext/standard/array.c#L144
			case SORT_NUMERIC:
				$type = 'numeric';
				break;
			case SORT_NATURAL:
				$type = ($sort_flags & SORT_FLAG_CASE ? 'strnatcasecmp' : 'strnatcmp');
				break;
			case SORT_STRING:
			case SORT_REGULAR:
			default:
				$type = ($sort_flags & SORT_FLAG_CASE ? 'strcasecmp' : 'strcmp');
				break;
		}
		uasort($array, array($array_key_sort, $type));
		if ($sort_order == SORT_DESC) { // Sort type and order cannot be merged
			$array = array_reverse($array);
		}
	}

		class array_key_sort {
			private $key = NULL;
			public function __construct($key) {
				$this->key = $key;
			}
			public function strcmp($a, $b) { // String comparison
				return strcmp($a[$this->key], $b[$this->key]);
			}
			public function strcasecmp($a, $b) { // Case-insensitive string comparison
				return strcasecmp($a[$this->key], $b[$this->key]);
			}
			public function strnatcmp($a, $b) { // String comparisons using a "natural order" algorithm
				return strnatcmp($a[$this->key], $b[$this->key]);
			}
			public function strnatcasecmp($a, $b) { // Case insensitive string comparisons using a "natural order" algorithm
				return strnatcasecmp($a[$this->key], $b[$this->key]);
			}
			public function numeric($a, $b) {
				if ($a[$this->key] == $b[$this->key]) {
					return 0;
				}
				return ($a[$this->key] < $b[$this->key] ? -1 : 1);
			}
		}

?>