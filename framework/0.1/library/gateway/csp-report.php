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
// Ignored URIs

	$ignore_uris = config::get('output.csp_report_ignore');

	if (is_array($ignore_uris) && in_array($report['blocked-uri'], $ignore_uris)) {
		exit();
	}

//--------------------------------------------------
// Record

	$handler = config::get('output.csp_report_handle');

	if ($handler) {

		call_user_func($handler, $report, $data_raw);

	} else if (config::get('db.host') !== NULL) {

		$db = db_get();

		$values_update = array(
			'blocked_uri'        => $report['blocked-uri'],
			'violated_directive' => $report['violated-directive'],
			'referrer'           => $report['referrer'],
			'document_uri'       => $report['document-uri'],
			'original_policy'    => $report['original-policy'],
			'data_raw'           => $data_raw,
			'ip'                 => config::get('request.ip'),
			'browser'            => config::get('request.browser'),
			'updated'            => date('Y-m-d H:i:s'),
		);

		$values_insert = $values_update;
		$values_insert['created'] = date('Y-m-d H:i:s');

		$db->insert(DB_PREFIX . 'system_report_csp', $values_insert, $values_update);

	}

?>