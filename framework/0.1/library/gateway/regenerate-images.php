<?php

//--------------------------------------------------
// Not on Live

	if (SERVER == 'live' && (!defined('ADMIN_LOGGED_IN') || ADMIN_LOGGED_IN !== true)) {
		exit('Disabled');
	}

//--------------------------------------------------
// Mode

	mime_set('text/plain');

	echo date('Y-m-d H:i:s') . ' - Start' . "\n\n";

//--------------------------------------------------
// Files

	if ($type_handle = opendir(FILE_ROOT)) {
		while (false !== ($type_name = readdir($type_handle))) {

			$type_path = FILE_ROOT . '/' . safe_file_name($type_name);

			if (!preg_match('/^\./', $type_name) && is_dir($type_path . '/original/')) {

				echo '  ' . $type_name . "\n";

				$file = new file($type_name);
				$type = $file->config_get('image_type');

				if ($handle_item = opendir($type_path . '/original/')) {
					while (false !== ($id = readdir($handle_item))) {
						if (preg_match('/^([0-9]+)\.([a-z]+)$/', $id, $matches)) {

							if ($matches[2] == $type) {
								$file->image_save($matches[1]);
							} else {
								echo '    Invalid file "' . $id . '" (not ' . $type . ')' . "\n";
							}

						}
					}
					closedir($handle_item);
				}

			}

		}
		closedir($type_handle);
	}

//--------------------------------------------------
// Done

	echo "\n";
	echo date('Y-m-d H:i:s') . ' - Done' . "\n";

	exit();

?>