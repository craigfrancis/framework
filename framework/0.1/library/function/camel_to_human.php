<?php

//--------------------------------------------------
// Quick function to convert human to camel case

	function camel_to_human($text) {
		return ucfirst(preg_replace('/([a-z])([A-Z])/', '\1 \2', $text));
	}

?>