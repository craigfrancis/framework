<?php

	//--------------------------------------------------
	// Config:
	//
	//   upload.[server].source = [git/svn/server]
	//   upload.[server].method = [scm/local/rsync/scp] ... usually auto detected
	//   upload.[server].location = [server:path]
	//
	// Examples:
	//
	//   Reader:
	//
	//     upload.demo.source = git
	//     upload.demo.location = fey:/www/demo/craig.reader/
	//
	//     upload.live.source = demo
	//     upload.live.method = local
	//     upload.live.location = fey:/www/live/craig.reader/
	//
	//   CA:
	//
	//     upload.demo.source = svn
	//     upload.demo.location = ca:/www/demo/ca.portal/
	//
	//     upload.live.source = demo
	//     upload.live.method = local
	//     upload.live.location = ca:/www/live/ca.portal/
	//
	//   Thrive:
	//
	//     upload.demo.source = git
	//     upload.demo.location = fey:/www/demo/thrive.corporate/
	//
	//     upload.live.source = demo
	//     upload.live.method = rsync
	//     upload.live.location = thrive:/www/live/thrive.corporate/
	//
	//   Chrysalis:
	//
	//     upload.stage.source = svn
	//     upload.stage.location = ss:/www/stage/chrysalis.f2f/
	//
	//     upload.demo.source = stage
	//     upload.demo.method = local
	//     upload.demo.location = ss:/www/demo/chrysalis.f2f/
	//
	//     upload.live.source = demo
	//     upload.live.method = rsync
	//     upload.live.location = chrysalis:/www/live/chrysalis.f2f/
	//
	//--------------------------------------------------

	function upload_run($server) {

		//--------------------------------------------------
		// Config

			$server = preg_replace('/[^a-z0-9]/i', '', $server);

			if ($server == '') {
				exit("\n" . 'Please specify a server.' . "\n\n");
			}

			$config_dst = upload_config($server);

		//--------------------------------------------------
		// Processing

			if ($config_dst['source'] == 'svn' || $config_dst['source'] == 'git') {

				//--------------------------------------------------
				// SCM mode

					if (SERVER == 'stage') {

						if (isset($config_dst['update'])) {
							if (!is_array($config_dst['update'])) {
								$config_dst['update'] = ($config_dst['update'] === true ? array('project', 'framework') : array());
							}
							$update = implode(' ', $config_dst['update']);
						} else {
							$update = 'false';
						}

						upload_exec('process-scm', array(
								SERVER,
								ROOT,
								$config_dst['source'],
								$config_dst['location_host'],
								$config_dst['location_path'],
								$update,
							));

					}

			} else {

				//--------------------------------------------------
				// Upload config

					$config_src = upload_config($config_dst['source']);

				//--------------------------------------------------
				// Upload processing

					if ($config_dst['source'] != SERVER) {

						upload_exec('connect', array(
								SERVER,
								$server,
								$config_src['location_host'],
								$config_src['location_path'],
							));

					} else if ($config_dst['location_host'] == $config_src['location_host']) {

						upload_exec('process-local', array(
								FRAMEWORK_ROOT,
								$config_src['location_path'],
								$config_dst['location_path'],
							));

					} else {

						if (!isset($config_dst['method'])) {
							$config_dst['method'] = 'rsync';
						}

						upload_exec('process-remote', array(
								SERVER,
								FRAMEWORK_ROOT,
								$config_dst['method'],
								$config_src['location_path'],
								$config_dst['location_host'],
								$config_dst['location_path'],
							));

					}

			}

	}

	function upload_exec($script, $exec_params) {

		$exec_params = implode(' ', array_map('escapeshellarg', $exec_params));

		$exec_dir = FRAMEWORK_ROOT . '/library/cli/upload';
		$exec_script = $exec_dir . '/' . safe_file_name($script) . '.sh';
		$exec_command = escapeshellcmd($exec_script) . ' ' . $exec_params;

		passthru($exec_command);

		// $descriptor = array( // - Was used
		// 		0 => array('file', 'php://stdin', 'r'),
		// 		1 => array('file', 'php://stdout', 'r'),
		// 		2 => array('file', 'php://stderr', 'r'),
		// 	);

		// $descriptor = array( - might also work
		// 		0 => array('file', '/dev/tty', 'r'),
		// 		1 => array('file', '/dev/tty', 'w'),
		// 		2 => array('file', '/dev/tty', 'w'),
		// 	);

		// $descriptor = array( - does not work on OSX
		// 		0 => array('pty'),
		// 		1 => array('pty'),
		// 		2 => array('pty')
		// 	);

		// $process = proc_open($exec_command, $descriptor, $pipes, $exec_dir);

	}

	function upload_config($server) {

		$config = config::get_all('upload.' . $server);

		$required = array(
				'source' => 'Either the server to connect to (e.g. "demo"), "git", or "svn".',
				'location' => 'Server hostname and path - e.g. "www.example.com:/www/live/company.project/"',
			);

		foreach ($required as $key => $info) {
			if (!isset($config[$key]) || $config[$key] == '') {
				exit("\n" . 'Missing config "upload.' . $server . '.' . $key . '" - ' . $info . "\n\n");
			}
		}

		if (preg_match('/^([^:]+):(.*)$/', $config['location'], $matches)) {
			$config['location_host'] = $matches[1];
			$config['location_path'] = $matches[2];
		} else {
			exit("\n" . 'Invalid location for config "upload.' . $server . '.location" - ' . $config['location'] . "\n\n");
		}

		return $config;

	}

?>