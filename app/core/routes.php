<?php

$routes[] = array(
		'path' => '^/(home|work)/',
		'replace' => '/',
		'method' => 'regexp',
		'variables' => array(
				'area',
			),
	);

$routes[] = array(
		'path' => '/blog/',
		'replace' => '/news/',
	);

$routes[] = array(
		'path' => '/news/*/',
		'replace' => '/news/item/',
		'method' => 'wildcard', // wildcard, prefix, suffix, exact, regexp, preg
		'variables' => array(
				'ref',
				'error',
			),
	);

$routes[] = array(
		'path' => '^/(desert|sea)/',
		'replace' => '/location-\1/',
		'method' => 'regexp',
		'variables' => array(
				'location',
			),
	);

?>