<?php

//--------------------------------------------------
// Disable log

	config::set('debug.log_file', NULL);

//--------------------------------------------------
// Reference

	$debug_ref = trim($this->sub_path_get(), '/');

	$pos = strpos($debug_ref, '.json');
	if ($pos > 0) {
		$debug_ref = substr($debug_ref, 0, $pos);
	}

//--------------------------------------------------
// Code

	$data = '';

	$debug_data = session::get('debug.output_data');

	if (is_array($debug_data)) {

		if (isset($debug_data[$debug_ref])) {

			$data = $debug_data[$debug_ref];

			unset($debug_data[$debug_ref]);

		}

		foreach ($debug_data as $ref => $info) {
			$max_life = (time() - 30);
			if (!preg_match('/^([0-9]+)\-[0-9]+$/', $ref, $matches) || $matches[1] < $max_life) {
				unset($debug_data[$ref]);
			}
		}

		session::set('debug.output_data', $debug_data);

	}

//--------------------------------------------------
// Headers

	mime_set('application/json');

	http_cache_headers(0);

	// if (extension_loaded('zlib')) {
	// 	ob_start('ob_gzhandler');
	// }

//--------------------------------------------------
// Return

	echo $data;

?>