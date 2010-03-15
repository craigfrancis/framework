<?php

$routes[] = array(
		'path' => '/admin/',
		'action' => 'user_controller_helper', // controller default, alternatives being controller_helper, controller_template, rewrite
		'config' => array(
				'type' => 'admin',
			),
	);

$routes[] = array(
		'path' => '/admin/news/',
		'action' => 'crud_controller_template', // This probably isn't necessary, developers want to only look in once place (controller folder) to find where the code/config is.
		'config' => 'news',
	);

$routes[] = array(
		'path' => '/news/',
		'action' => 'index_controller_template',
		'config' => 'news',
	);

$routes[] = array(
		'path' => '/blog/',
		'action' => 'rewrite',
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
			),
	);

?>