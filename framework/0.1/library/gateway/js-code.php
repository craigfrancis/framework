<?php

//--------------------------------------------------
// Notes

	$js_ref = request('ref');
	$js_code = '';

	$session_js = session::get('output.js_code');

	if (is_array($session_js)) {

		if (isset($session_js[$js_ref])) {

			$js_code = $session_js[$js_ref];

			unset($session_js[$js_ref]);

		}

		foreach ($session_js as $ref => $info) {
			$max_life = (time() - 30);
			if (!preg_match('/^([0-9]+)\-[0-9]+$/', $ref, $matches) || $matches[1] < $max_life) {
				unset($session_js[$ref]);
			}
		}

		session::set('output.js_code', $session_js);

	}

//--------------------------------------------------
// Headers

	mime_set('application/javascript');

	http_cache_headers(0);

//--------------------------------------------------
// Return JavaScript

	echo $js_code;

?>