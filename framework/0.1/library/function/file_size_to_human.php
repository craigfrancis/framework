<?php

//--------------------------------------------------
// Function to convert a file-size into something
// human readable

	function file_size_to_human($size) {

		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		foreach ($units as $unit) {
			if ($size >= 1024 && $unit != 'YB') {
				$size = ($size / 1024);
			} else {
				return round($size, 0) . $unit;
			}
		}

	}

?>