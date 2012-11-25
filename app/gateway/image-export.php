<?php

//--------------------------------------------------
// Main script

	$output_php = file_get_contents(ROOT . '/framework/0.1/class/image.php');

//--------------------------------------------------
// Save

	config::set('debug.show', false);

	mime_set('text/plain');

	echo $output_php;

?>