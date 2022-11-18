<?php

	$response->set('title_html', '<h1>Permission Denied</h1>');

	if (!isset($message_html)) {

		if (!isset($message)) {
			$message = 'You cannot access this ' . (isset($type) ? $type : 'item') . '.';
		}

		$message_html = html($message);

	}

	echo '<p>' . $message_html . '</p>';

?>