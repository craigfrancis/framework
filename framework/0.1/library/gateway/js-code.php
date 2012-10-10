<?php

//--------------------------------------------------
// Notes

	$js_ref = request('ref');

	$session_js = session::get('output.js_code');

	if (isset($session_js[$js_ref])) {

		$js_code = $session_js[$js_ref];

		unset($session_js[$js_ref]);

	} else {

		$js_code = '';

	}

	foreach ($session_js as $ref => $info) {
		$max_life = (time() - 30);
		if (!preg_match('/^([0-9]+)\-[0-9]+$/', $ref, $matches) || $matches[1] < $max_life) {
			unset($session_js[$ref]);
		}
	}

	session::set('output.js_code', $session_js);

//--------------------------------------------------
// Headers

	mime_set('application/javascript');

	header('Pragma: no-cache');
	header('Cache-control: private, no-cache, must-revalidate');
	header('Expires: Sat, 01 Jan 2000 01:00:00 GMT');

//--------------------------------------------------
// Return JavaScript

	echo $js_code;

?>