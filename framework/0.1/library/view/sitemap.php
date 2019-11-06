<?php

//--------------------------------------------------
// Start

	echo '<?xml version="1.0" encoding="' . xml(config::get('output.charset')) . '"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

//--------------------------------------------------
// URLs

	$paths = [];
	$paths[] = '/';

	foreach ($paths as $path) {
		echo '<url><loc>' . xml($path) . '</loc></url>' . "\n";
	}

//--------------------------------------------------
// End

	echo '</urlset>';

?>