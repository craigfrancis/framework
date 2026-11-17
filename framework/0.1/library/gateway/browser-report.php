<?php

//--------------------------------------------------
// Table check - done early so developer can load API
// directly in browser to see the error message.

	if (config::get('debug.level') > 0 && config::get('db.host') !== NULL && config::get('output.site_available') !== false) {

		debug_require_db_table(DB_PREFIX . 'system_report_browser', '
				CREATE TABLE [TABLE] (
					type varchar(30) NOT NULL,
					url tinytext NOT NULL,
					body text NOT NULL,
					ip tinytext NOT NULL,
					browser tinytext NOT NULL,
					created datetime NOT NULL,
					KEY created (created)
				);');

	}

//--------------------------------------------------
// Get report array

	$data_raw = file_get_contents('php://input');
	$data_array = json_decode($data_raw, true);

	if (!is_array($data_array)) {

		$info  = '--------------------------------------------------' . "\n";
		$info .= $data_raw . "\n";
		$info .= '--------------------------------------------------' . "\n";
		$info .= 'GET: ' . print_r($_GET, true) . "\n";
		$info .= '--------------------------------------------------' . "\n";
		$info .= 'POST: ' . print_r($_POST, true) . "\n";
		$info .= '--------------------------------------------------';

		exit_with_error('Bad Browser Report', $info);

	}

//--------------------------------------------------
// Ignored URIs

	// $ignore_uris = config::get('output.browser_report_ignore');
	//
	// if (is_array($ignore_uris) && in_array($report['blocked-uri'], $ignore_uris)) {
	// 	exit();
	// }

//--------------------------------------------------
// Handler

	$return = [];

	// $handler = config::get('output.browser_report_handle');
	//
	// if ($handler && function_exists($handler)) {
	// 	$return = call_user_func($handler, $report, $data_array); // Either handles everything, or returns details in an array (e.g. 'user_id').
	// } else {
	// }

//--------------------------------------------------
// Record

	if (is_array($return) && config::get('db.host') !== NULL) {

		$db = db_get();

		$now = new timestamp();

		foreach ($data_array as $report) {

			// array_merge(xxx, config::get('output.browser_report_extra', []), $return)

			$db->insert(DB_PREFIX . 'system_report_browser', [
					'type'     => ($report['type'] ?? ''),
					'url'      => ($report['url'] ?? ''),
					'body'     => (array_key_exists('body', $report) ? json_encode($report['body']) : '-'),
					'ip'       => strval(config::get('request.ip')),
					'browser'  => strval(config::get('request.browser')),
					'created'  => $now,
				]);

		}

	}

?>