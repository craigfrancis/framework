
	<?php

		if (!isset($message)) {

			$message = 'This ' . (isset($type) ? $type : 'item');

			if (isset($timestamp)) {
				$message .= ' was deleted on the ' . date('jS F Y, \a\t g:ia', $timestamp) . '.';
			} else {
				$message .= ' has been deleted.';
			}

		}

	?>

	<h1>Deleted</h1>
	<p><?= html($message) ?></p>
