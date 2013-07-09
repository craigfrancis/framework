<?php

//--------------------------------------------------
// Directories

	function dump_dir() {

		//--------------------------------------------------
		// File folders

			$folders = array(
				'files' => FILE_ROOT,
				'private' => PRIVATE_ROOT,
			);

			foreach ($folders as $folder_name => $folder_path) {

				if (substr($folder_path, -1) != '/') {
					$folder_path .= '/';
				}
				$folder_path_length = strlen($folder_path);

				$folder_listing = shell_exec('find ' . escapeshellarg($folder_path) . ' -type d -mindepth 1 ! -path "*/.*" 2>&1');
				$folder_children = array();

				foreach (explode("\n", $folder_listing) as $path) {
					if (substr($path, 0, $folder_path_length) == $folder_path) {
						$path = substr($path, ($folder_path_length + 1));
						if ($path != 'tmp' && substr($path, 0, 4) != 'tmp/') { // Will be created anyway
							$folder_children[] = $path;
						}
					}
				}

				$setup_file = APP_ROOT . '/library/setup/dir.' . safe_file_name($folder_name) . '.txt';

				file_put_contents($setup_file, implode("\n", $folder_children));

			}

	}

//--------------------------------------------------
// Database

	function dump_db() {

		file_put_contents(APP_ROOT . '/library/setup/database.txt', '');

		// TODO
		// see http://davidwalsh.name/backup-database-xml-php

	}

?>