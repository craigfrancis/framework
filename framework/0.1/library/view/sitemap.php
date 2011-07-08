<?php

//--------------------------------------------------
// Start

	echo '<?xml version="1.0" encoding="UTF-8"?>
		<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

//--------------------------------------------------
// Links

	$root_path = APP_ROOT . '/view/';
	$root_folders = array();
	if ($handle = opendir($root_path)) {
		while (false !== ($file = readdir($handle))) {
			if (substr($file, 0, 1) != '.') {

				if (is_file($root_path . $file) && substr($file, -4) == '.ctp') {

					$root_folders[] = substr($file, 0, -4);

				} else if (is_dir($root_path . $file)) {

					$root_folders[] = $file;

				}

			}
		}
		closedir($handle);
	}
	sort($root_folders);

	foreach ($root_folders as $folder) {

		echo '
			<url>
				<loc>' . xml(url('/' . $folder . '/')) . '</loc>
			</url>';

	}

//--------------------------------------------------
// End

	echo '
		</urlset>';

?>