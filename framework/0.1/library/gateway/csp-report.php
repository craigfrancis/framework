<?php

	$data_str = file_get_contents('php://input');

	if (substr($data_str, 0, 1) == '{') {
		$data_array = json_decode($data_str, true);
		if ($data_array) {
			exit_with_error('Content-Security-Policy failure', var_export($data_array, true));
		}
	}

?>