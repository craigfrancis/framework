<?php

	function array_column($array, $column_key, $index_key = null) {
		$results = [];
		foreach ($array as $k => $v) {
			$results[($index_key ? $v[$index_key] : $k)] = $v[$column_key];
		}
		return $results;
	}

?>