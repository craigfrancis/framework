<?php

//--------------------------------------------------
// Get report array

	$report = array(
			'date-time'                   => '',
			'hostname'                    => '',
			'port'                        => '',
			'effective-expiration-date'   => '',
			'include-subdomains'          => '',
			'noted-hostname'              => '',
			'served-certificate-chain'    => '',
			'validated-certificate-chain' => '',
			'known-pins'                  => '',
		);

	$data_raw = file_get_contents('php://input');
	$data_array = json_decode($data_raw, true);
	$data_match = false;

	foreach ($report as $key => $value) {
		if (isset($data_array[$key])) {
			$value = $data_array[$key];
			if (is_array($value)) {
				$value = implode(",\n", $value);
			}
			$report[$key] = $value;
			$data_match = true;
		}
	}

	if (!$data_match) {

		if ($data_raw == '' && count($_GET) == 0 && count($_POST) == 0) {

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

	}

	$report['referrer'] = config::get('request.referrer');

//--------------------------------------------------
// Record

	$handler = config::get('output.pkp_report_handle');

	if ($handler && function_exists($handler)) {
		$return = call_user_func($handler, $report, $data_raw); // Either handles everything, or returns details in an array (e.g. 'user_id').
	} else {
		$return = [];
	}

	if (is_array($return) && config::get('db.host') !== NULL) {

		$db = db_get();

		$now = new timestamp();

		$values = array_merge(array(
				'date_time'       => $report['date-time'],
				'hostname'        => $report['hostname'],
				'port'            => $report['port'],
				'expires'         => $report['effective-expiration-date'],
				'subdomains'      => $report['include-subdomains'],
				'noted_hostname'  => $report['noted-hostname'],
				'served_chain'    => $report['served-certificate-chain'],
				'validated_chain' => $report['validated-certificate-chain'],
				'known_pins'      => $report['known-pins'],
				'referrer'        => $report['referrer'],
				'data_raw'        => $data_raw,
				'ip'              => config::get('request.ip'),
				'browser'         => config::get('request.browser'),
				'created'         => $now,
			), config::get('output.pkp_report_extra', []), $return);

		$db->insert(DB_PREFIX . 'system_report_pkp', $values);

	}

?>