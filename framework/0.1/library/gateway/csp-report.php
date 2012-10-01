<?php

	$data_str = file_get_contents('php://input');

	if (substr($data_str, 0, 1) == '{') {

		$data_array = json_decode($data_str, true);

		if (isset($data_array['csp-report'])) {

			$report = array_merge(array(
					'violated-directive' => '',
					'original-policy'    => '',
					'blocked-uri'        => '',
					'referrer'           => '',
					'document-uri'       => '',
				), $data_array['csp-report']);

			$report = true;

// 			if ($report['blocked-uri'] == 'http://nikkomsgchannel') $report = false;
// 			if (substr($report['blocked-uri'], 0, 19) == 'chrome-extension://') $report = false;

			if ($report) {

				$values_update = array(
					'violated_directive' => $report['violated-directive'],
					'original_policy'    => $report['original-policy'],
					'blocked_uri'        => $report['blocked-uri'],
					'referrer'           => $report['referrer'],
					'document_uri'       => $report['document-uri'],
				);

				$values_insert = $values_update;
				$values_insert['created'] = date('Y-m-d H:i:s');

				$db = $this->db_get();

				$db->insert(DB_PREFIX . 'system_lock_worklist', $values_insert, $values_update);

			}

		} else {

			exit_with_error('Content-Security-Policy failure', var_export($data_array, true));

		}

	}

?>