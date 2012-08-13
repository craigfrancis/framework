<?php

//--------------------------------------------------
// Start

	echo '<?xml version="1.0" encoding="' . xml(config::get('output.charset')) . '"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

//--------------------------------------------------
// Links

	$root_path = VIEW_ROOT . '/';
	$root_folders = array();
	if ($handle = opendir($root_path)) {
		while (false !== ($file = readdir($handle))) {
			if (substr($file, 0, 1) != '.') {

				if (is_file($root_path . $file) && substr($file, -4) == '.ctp') {

					$root_folders[] = substr($file, 0, -4);

				}

			}
		}
		closedir($handle);
	}
	sort($root_folders);

	foreach ($root_folders as $folder) {

		echo '<url><loc>' . xml(url('/' . $folder . '/')) . '</loc></url>' . "\n";

	}

//--------------------------------------------------
// End

	echo '</urlset>';

?>