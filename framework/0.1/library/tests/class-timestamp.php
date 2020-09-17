<?php

	config::set('timestamp.formats', array(
				'iso' => 'Y-m-d H:i:s',
				'human' => 'l jS F Y, g:i:sa',
			));

	class timestamp extends timestamp_base {
	}

	$timestamp = new timestamp();
	debug($timestamp->format('human'));

	$timestamp = new timestamp(time());
	debug($timestamp->format('human'));

	echo '<hr />';

	$timestamp = new timestamp('2014W04-2');
	debug($timestamp->format('human'));
	$timestamp = $timestamp->clone('+3 days');
	debug($timestamp->format('human'));
	debug($timestamp->html('l jS F Y'));

	echo '<hr />';

	$timestamp = timestamp::createFromFormat('d/m/y H:i:s', '01/07/03 12:01:02');
	debug($timestamp->format('l jS F Y g:i:sa'));
	debug($timestamp->format('db'));

	echo '<hr />';

	$timestamp = new timestamp('2014-09-22 16:37:15', 'db');
	debug($timestamp->format('human'));
	debug($timestamp->format('iso'));
	debug($timestamp->format('db'));
	debug($timestamp->html('human'));

	exit();

?>