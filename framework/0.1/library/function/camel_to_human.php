<?php

//--------------------------------------------------
// Quick function to convert camel case to human

	function camel_to_human($text) {
		return ucfirst(preg_replace('/([a-z])([A-Z])/', '\1 \2', $text));
	}

?>