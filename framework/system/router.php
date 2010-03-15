<?php

//--------------------------------------------------
// App routes

	$routes = array();

	$routePath = ROOT_APP . DS . 'core' . DS . 'route.php';
	if (is_file($routePath)) {
		require_once($routePath);
	}

//--------------------------------------------------
// Process routes

dump($routes);

	foreach ($routes as $cRoute) {

	}

?>