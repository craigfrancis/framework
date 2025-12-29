<?php

//--------------------------------------------------
// Get report array

	$report = array(
			'blocked-uri'        => '',
			'violated-directive' => '',
			'original-policy'    => '',
			'referrer'           => '',
			'document-uri'       => '',
		);

	$data_raw = file_get_contents('php://input');
	$data_array = json_decode($data_raw, true);

	if (isset($data_array['csp-report'])) {

		$report = array_merge($report, $data_array['csp-report']);

	} else if (request('document-url') !== NULL) { // Safari

		$report['document-uri'] = request('document-url');
		$report['violated-directive'] = request('violated-directive');

	} else if ($data_raw == '' && count($_GET) == 0 && count($_POST) == 0) {

		exit('Missing report data.'); // User loaded page?

	} else {

		$info  = '--------------------------------------------------' . "\n";
		$info .= $data_raw . "\n";
		$info .= '--------------------------------------------------' . "\n";
		$info .= 'GET: ' . print_r($_GET, true) . "\n";
		$info .= '--------------------------------------------------' . "\n";
		$info .= 'POST: ' . print_r($_POST, true) . "\n";
		$info .= '--------------------------------------------------';

		exit_with_error('Content-Security-Policy failure', $info);

	}

	if ($report['referrer'] == '') {
		$report['referrer'] = config::get('request.referrer'); // Safari
	}

//--------------------------------------------------
// Table check - done early so developer can load API
// directly in browser to see the error message.

	if (config::get('debug.level') > 0 && config::get('db.host') !== NULL && config::get('output.site_available') !== false) {

		debug_require_db_table(DB_PREFIX . 'system_report_csp', '
				CREATE TABLE [TABLE] (
					document_uri varchar(80) NOT NULL,
					blocked_uri varchar(80) NOT NULL,
					violated_directive varchar(80) NOT NULL,
					referrer tinytext NOT NULL,
					original_policy text NOT NULL,
					data_raw text NOT NULL,
					ip tinytext NOT NULL,
					browser tinytext NOT NULL,
					created datetime NOT NULL,
					updated datetime NOT NULL,
					PRIMARY KEY (document_uri,blocked_uri,violated_directive)
				);');

	}

//--------------------------------------------------
// Ignored URIs

	$ignore_uris = config::get('output.csp_report_ignore');

	if (is_array($ignore_uris) && in_array($report['blocked-uri'], $ignore_uris)) {
		exit();
	}

//--------------------------------------------------
// Record

	$handler = config::get('output.csp_report_handle');

	if ($handler && function_exists($handler)) {
		$return = call_user_func($handler, $report, $data_raw); // Either handles everything, or returns details in an array (e.g. 'user_id').
	} else {
		$return = [];
	}

	if (is_array($return) && config::get('db.host') !== NULL) {

		$db = db_get();

		$now = new timestamp();

		$values_update = array_merge(array(
				'document_uri'       => $report['document-uri'],
				'blocked_uri'        => $report['blocked-uri'],
				'violated_directive' => $report['violated-directive'],
				'referrer'           => $report['referrer'],
				'original_policy'    => $report['original-policy'],
				'data_raw'           => $data_raw,
				'ip'                 => config::get('request.ip'),
				'browser'            => config::get('request.browser'),
				'updated'            => $now,
			), config::get('output.csp_report_extra', []), $return);

		$values_insert = $values_update;
		$values_insert['created'] = $now;

		$db->insert(DB_PREFIX . 'system_report_csp', $values_insert, $values_update);

	}

?>