<?php

$routes[] = array(
		'path' => '/blog/',
		'action' => 'rewrite', // Default? - could there be other actions? - not going to allow matching direct to controller_template.
		'config' => array(
				'replace' => '/news/',
			),
	);

$routes[] = array(
		'path' => '^/(home|work)/', // Could also be '/*/' for a wildcard match, where * just hits a dir [^/], but this needs an str_replace('/', '\\/')
		'match' => 'preg', // prefix, suffix, exact, wildcard, preg
		'action' => 'rewrite',
		'config' => array(
				'replace' => '/item/',
				'matches' => array(
						1 => 'area-name', // Stored in site config, can preg use labels?, 0 for full match, * in wildcard.
					),
			),
	);

?>