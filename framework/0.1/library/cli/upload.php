<?php

	//--------------------------------------------------
	// Config:
	//
	//   upload.[server].method = [local/scp/rsync] ... local could be detected from same hostname in src/dst.
	//   upload.[server].src = [git/path]
	//   upload.[server].dst = [path]
	//
	// Examples:
	//
	//   Reader:
	//
	//     upload.demo.src = scm
	//     upload.demo.dst = fey:/www/demo/craig.reader/
	//
	//     upload.live.method = local
	//     upload.live.src = fey:/www/demo/craig.reader/
	//     upload.live.dst = fey:/www/live/craig.reader/
	//
	//   CA:
	//
	//     upload.demo.src = scm
	//     upload.demo.dst = ca:/www/demo/ca.portal/
	//
	//     upload.live.method = local
	//     upload.live.src = ca:/www/demo/ca.portal/
	//     upload.live.dst = ca:/www/live/ca.portal/
	//
	//   Thrive:
	//
	//     upload.demo.src = scm
	//     upload.demo.dst = fey:/www/demo/thrive.corporate/
	//
	//     upload.live.method = rsync
	//     upload.live.src = fey:/www/demo/thrive.corporate/
	//     upload.live.dst = thrive:/www/live/thrive.corporate/
	//
	//   Chrysalis:
	//
	//     upload.stage.src = scm
	//     upload.stage.dst = ss:/www/stage/chrysalis.f2f/
	//
	//     upload.demo.method = local
	//     upload.demo.src = ss:/www/stage/chrysalis.f2f/
	//     upload.demo.dst = ss:/www/demo/chrysalis.f2f/
	//
	//     upload.live.method = rsync
	//     upload.live.src = ss:/www/demo/chrysalis.f2f/
	//     upload.live.dst = chrysalis:/www/live/chrysalis.f2f/
	//
	//--------------------------------------------------

	function upload_exec($script, $server, $config) {

		$exec_dir = FRAMEWORK_ROOT . '/library/cli/upload';
		$exec_script = $exec_dir . '/' . safe_file_name($script) . '.sh';
		$exec_params = escapeshellarg(FRAMEWORK_ROOT) . ' ' . escapeshellarg($server) . ' ' . escapeshellarg($config['method']) . ' ' . escapeshellarg($config['src_host']) . ' ' . escapeshellarg($config['src_path']) . ' ' . escapeshellarg($config['dst_host']) . ' ' . escapeshellarg($config['dst_path']);
		$exec_command = escapeshellcmd($exec_script) . ' ' . $exec_params;

		// execute_command($exec_command, true);
		// return;

		$descriptor = array(
				0 => array('file', 'php://stdin', 'r'),
				1 => array('file', 'php://stdout', 'r'),
				2 => array('file', 'php://stderr', 'r'),
			);

		$process = proc_open($exec_command, $descriptor, $pipes, $exec_dir . '/');

	}

	function upload_run($server) {

		//--------------------------------------------------
		// Config

			$server = preg_replace('/[^a-z0-9]/i', '', $server);

			if ($server == '') {
				exit('Please specify a server.' . "\n\n");
			}

			$config = config::get_all('upload.' . $server);

			$required = array(
					'src' => 'Either "scm", or the server to connect to, with path - e.g. "test.example.com:/www/demo/company.project/"',
					'dst' => 'The server to connect to (from src), with path - e.g. "www.example.com:/www/live/company.project/"',
				);

			foreach ($required as $key => $info) {
				if (!isset($config[$key])) {
					exit("\n" . 'Missing config "upload.' . $server . '.' . $key . '" - ' . $info . "\n\n");
				}
				if ($key != 'src' || $config[$key] != 'scm') {
					if (preg_match('/^([^:]+):(.*)$/', $config[$key], $matches)) {
						$config[$key . '_host'] = $matches[1];
						$config[$key . '_path'] = $matches[2];
					} else {
						exit("\n" . 'Invalid config "upload.' . $server . '.' . $key . '" - ' . $info . "\n\n");
					}
				}
			}

			if (!isset($config['method'])) {
				if ($config['src'] == 'scm') {
					$config['method'] = 'scm';
				} else {
					$config['method'] = ($config['src_host'] == $config['dst_host'] ? 'local' : 'rsync');
				}
			}

		//--------------------------------------------------
		// Run

// TODO: Improve detection for when we are running locally... $server is the destination (live), so won't match SERVER (demo)
// TODO: Handle "scm" mode... connect to $config['dst_host'] to run install script? how about checking the db?

			if ($config['src_path'] != ROOT) {
				upload_exec('connect', $server, $config);
			} else {
				upload_exec('process', $server, $config);
			}

	}

?>