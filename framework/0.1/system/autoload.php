<?php

	function classAutoload($class_name) {
		exit('Auto load: ' . $class_name);
	}

	spl_autoload_register('classAutoload');

?>