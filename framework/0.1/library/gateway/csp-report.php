<?php

		// http://www.html5rocks.com/en/tutorials/security/content-security-policy/

	$data_str = file_get_contents('php://input');

	if ($data_str != '') {

		$data_array = json_decode($data_str, true);

		if (isset($data_array['csp-report'])) {

			$report = array_merge(array(
					'blocked-uri'        => '',
					'violated-directive' => '',
					'original-policy'    => '',
					'referrer'           => '',
					'document-uri'       => '',
				), $data_array['csp-report']);

			$ignore_uris = config::get('output.csp_report_ignore');

			if ($ignore_uris == 'defaults') {

				$ignore_uris = array(
					'chrome-extension://lifbcibllhkdhoafpjfnlhfpfgnpldfl', // Skype
					'http://nikkomsgchannel', // Rapport
					'http://edge.crtinv.com/', // Sterkly Revenue Suite (adds banners to websites)
				);

			}

			if (!is_array($ignore_uris) || !in_array($report['blocked-uri'], $ignore_uris)) {

				$db = $this->db_get();

				$values_update = array(
					'blocked_uri'        => $report['blocked-uri'],
					'violated_directive' => $report['violated-directive'],
					'referrer'           => $report['referrer'],
					'document_uri'       => $report['document-uri'],
					'original_policy'    => $report['original-policy'],
					'json'               => $data_str,
					'ip'                 => config::get('request.ip'),
					'browser'            => config::get('request.browser'),
					'updated'            => date('Y-m-d H:i:s'),
				);

				$values_insert = $values_update;
				$values_insert['created'] = date('Y-m-d H:i:s');

				$db->insert(DB_PREFIX . 'report_csp', $values_insert, $values_update);

			}

		} else {

			// TODO: Support Safari with url-encoded version
			//exit_with_error('Content-Security-Policy failure', $data_str);

		}

	}

?>