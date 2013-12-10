<?php

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
						$config[$key . '_server'] = $matches[1];
						$config[$key . '_path'] = $matches[2];
					} else {
						exit("\n" . 'Invalid config "upload.' . $server . '.' . $key . '" - ' . $info . "\n\n");
					}
				}
			}

			if ($config['src'] != 'scm' && !isset($config['method'])) {
				$config['method'] = ($config['src_server'] == $config['dst_server'] ? 'local' : 'scp');
			}

			debug($config);

		//--------------------------------------------------
		// SCM mode

			if ($config['src'] == 'scm') {

				// TODO: Connect to $config['dst_server'] to run install script? how about checking the db?

				return;

			}

		//--------------------------------------------------
		// Run

			execute_command(escapeshellarg(FRAMEWORK_ROOT . '/library/cli/upload/init.sh') . ' ' . escapeshellarg($config['src_server']) . ' ' . escapeshellarg($config['src_path']) . ' ' . escapeshellarg($config['dst_server']) . ' ' . escapeshellarg($config['dst_path']), true);

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

		// Possibly establish connection first (single connection?), as a background process, ref:
		//
		// SSH_CONTROL="~/.ssh/master-%r@%h:%p";
		// ssh -fN -M  -S "${SSH_CONTROL}" 217.171.110.91;
		// ssh         -S "${SSH_CONTROL}" 217.171.110.91 touch "hi";
		// ssh -O exit -S "${SSH_CONTROL}" 217.171.110.91 2> /dev/null;

		// Ensure the folder /dst_dir/upload/ exists.

		// Check for a block file in /dst_dir/upload/block.txt

		// Create lock file at dst /dst_dir/upload/lock.txt... maybe with a timestamp and uuid in it?

		// Create empty folder, or cp of live site (rsync), at /dst_dir/upload/files/

		// SCP or rsync /app/, /framework/, and /httpd/ folders.

		// Run the 'mvtolive' script equivalent (from Fey)... provides Diff, Database, Continue, Cancel.

		// Run the ./cli --install script, to create folders in /files/ and /private/files/.

		// Scripts
		//   .../framework/0.1/library/cli/upload/init.sh - run locally
		//   .../framework/0.1/library/cli/upload/process.sh - run remotely, from /dst_dir/upload/files/framework/
		//   .../framework/0.1/library/cli/upload/publish.sh - separate script, to run on remote server once files are in /dst_dir/upload/files/

	}

?>