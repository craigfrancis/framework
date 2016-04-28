<?php

	$response->set('title_html', '<h1>Deleted</h1>');

	if (!isset($message_html)) {

		if (!isset($message)) {

			$message = 'This ' . (isset($type) ? $type : 'item');

			if (isset($timestamp)) {
				$message .= ' was deleted on the ' . $timestamp->format('jS F Y, \a\t g:ia') . '.';
			} else {
				$message .= ' has been deleted.';
			}

		}

		$message_html = html($message);

	}

	echo '<p>' . $message_html . '</p>';

?>