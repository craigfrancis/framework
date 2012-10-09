<?php

//--------------------------------------------------
// Support

	if (config::get('debug.level') <= 0) {
		exit('Disabled');
	}

//--------------------------------------------------
// Notes

	$debug_ref = request('ref');

	$session_notes = session::get('debug.notes');

	if (isset($session_notes[$debug_ref])) {

		$notes = $session_notes[$debug_ref];

		unset($session_notes[$debug_ref]);

	} else {

		$notes = array();

	}

	foreach ($session_notes as $ref => $info) {
		$max_life = (time() - 10);
		if (!preg_match('/^([0-9]+)\-[0-9]+$/', $ref, $matches) || $matches[1] < $max_life) {
			unset($session_notes[$ref]);
		}
	}

	session::set('debug.notes', $session_notes);

//--------------------------------------------------
// Headers

	mime_set('application/javascript');

	header('Pragma: no-cache');
	header('Cache-control: private, no-cache, must-revalidate');
	header('Expires: Sat, 01 Jan 2000 01:00:00 GMT');

//--------------------------------------------------
// Return JavaScript

	echo 'var debug_notes = ' . json_encode($notes) . ';' . "\n";

	readfile(FRAMEWORK_ROOT . '/library/view/debug.js');

?>