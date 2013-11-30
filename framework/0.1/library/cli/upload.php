<?php

	function upload_run($server) {

		debug('Run upload: ' . $server);

		//--------------------------------------------------
		// Config:
		//
		//   upload.[server].method = [local/scp/rsync] ... local could be detected from same hostname in src/dest.
		//   upload.[server].src = [git/path]
		//   upload.[server].dest = [path]
		//
		// Examples:
		//
		//   Reader:
		//
		//     upload.demo.method = git
		//     upload.demo.src =
		//     upload.demo.dest = fey:/www/demo/craig.reader/
		//
		//     upload.live.method = local
		//     upload.live.src = fey:/www/demo/craig.reader/ AND /www/demo/craig.framework/framework/
		//     upload.live.dest = fey:/www/live/craig.reader/
		//
		//   CA:
		//
		//     upload.demo.method = svn
		//     upload.demo.src =
		//     upload.demo.dest = ca:/www/demo/ca.portal/
		//
		//     upload.live.method = local
		//     upload.live.src = ca:/www/demo/ca.portal/
		//     upload.live.dest = ca:/www/live/ca.portal/
		//
		//   Thrive:
		//
		//     upload.demo.method = git
		//     upload.demo.src =
		//     upload.demo.dest = fey:/www/demo/thrive.corporate/
		//
		//     upload.live.method = rsync
		//     upload.live.src = fey:/www/demo/thrive.corporate/
		//     upload.live.dest = thrive:/www/live/thrive.corporate/
		//
		//   Chrysalis:
		//
		//     upload.stage.method = svn
		//     upload.stage.src =
		//     upload.stage.dest = ss:/www/stage/chrysalis.f2f/
		//
		//     upload.demo.method = local
		//     upload.demo.src = ss:/www/stage/chrysalis.f2f/
		//     upload.demo.dest = ss:/www/demo/chrysalis.f2f/
		//
		//     upload.live.method = rsync
		//     upload.live.src = ss:/www/demo/chrysalis.f2f/
		//     upload.live.dest = chrysalis:/www/live/chrysalis.f2f/
		//
		//--------------------------------------------------

		// Possibly establish connection first (single connection?), as a background process, ref:
		//   Host *
		//      ControlMaster auto
		//      ControlPath ~/.ssh/master-%r@%h:%p

		// Ensure the folder /dest_dir/upload/ exists.

		// Check for a block file in /dest_dir/upload/block.txt

		// Create lock file at dest /dest_dir/upload/lock.txt... maybe with a timestamp and uuid in it?

		// Create empty folder, or cp of live site (rsync), at /dest_dir/upload/files/

		// SCP or rsync /app/, /framework/, and /httpd/ folders.

		// Run the 'mvtolive' script equivalent (from Fey)... provides Diff, Database, Continue, Cancel.

		// Run the ./cli --install script, to create folders in /files/ and /private/files/.

		// Scripts
		//   .../framework/0.1/library/cli/upload/init.sh - run locally
		//   .../framework/0.1/library/cli/upload/process.sh - run remotely, from /dest_dir/upload/files/framework/

	}

?>