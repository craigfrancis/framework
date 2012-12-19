<?php

//--------------------------------------------------
// Not on Live

	if (SERVER == 'live') {
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

				if ($handle_item = opendir($type_path . '/original/')) {
					while (false !== ($id = readdir($handle_item))) {
						if (preg_match('/^([0-9]+)\.[a-z]+$/', $id, $matches)) {

							$file->image_save($matches[1]);

							set_time_limit(5); // Don't time out

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