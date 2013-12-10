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

		$exec_script = FRAMEWORK_ROOT . '/library/cli/upload/' . safe_file_name($script) . '.sh';
		$exec_params = escapeshellarg($server) . ' ' . escapeshellarg($config['method']) . ' ' . escapeshellarg($config['src_host']) . ' ' . escapeshellarg($config['src_path']) . ' ' . escapeshellarg($config['dst_host']) . ' ' . escapeshellarg($config['dst_path']);

		execute_command(escapeshellcmd($exec_script) . ' ' . $exec_params, true);
		return;

		$descriptor = array(
			   0 => array('file', 'php://stdin', 'r'),
			   1 => array('pipe', 'w'),
			   2 => array('pipe', 'w'),
			);

		$descriptor = array(
			   0 => array('file', '/dev/tty', 'r'),
			   1 => array('pipe', 'w'),
			   2 => array('pipe', 'w'),
			);

		$process = proc_open($exec_command, $descriptor, $pipes);

// stream_set_blocking
//

		if (is_resource($process)) {
		    echo stream_get_contents($pipes[1]);
		    echo stream_get_contents($pipes[2]);
		}

// popen
// exec

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
					$config['method'] = ($config['src_host'] == $config['dst_host'] ? 'local' : 'scp');
				}
			}

		//--------------------------------------------------
		// Run

// TODO: Improve detection for when we are running locally... $server is the destination (live), so won't match SERVER (demo)

			if ($config['src_path'] != ROOT) {
				upload_exec('connect', $server, $config);
			} else {
				upload_exec('process', $server, $config);
			}

	}

?>