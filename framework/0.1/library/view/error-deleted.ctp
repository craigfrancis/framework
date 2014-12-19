<?php

	$response->set('title_html', '<h1>Deleted</h1>');

	if (!isset($message)) {

		$message = 'This ' . (isset($type) ? $type : 'item');

		if (isset($timestamp)) {
			$message .= ' was deleted on the ' . date('jS F Y, \a\t g:ia', $timestamp) . '.';
		} else {
			$message .= ' has been deleted.';
		}

	}

	echo '<p>' . html($message) . '</p>';

?>