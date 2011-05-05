<?php

$routes[] = array(
		'path' => '/blog/',
		'replace' => '/news/',
	);

$routes[] = array(
		'path' => '/news/*/',
		'replace' => '/news/item/',
		'match' => 'wildcard', // wildcard, prefix, suffix, exact, regexp, preg
	);

$routes[] = array(
		'path' => '^/(home|work)/',
		'replace' => '/area-\1/',
		'match' => 'regexp',
	);

// Switch to limit to GET/POST requests?

?>