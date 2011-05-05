<?php

//--------------------------------------------------
// Quick function to convert human to camel case

	function human_to_camel($text) {

		$text = ucwords(strtolower($text));
		$text = preg_replace('/[^a-zA-Z0-9]/', '', $text);

		if (strlen($text) > 0) { // Min of 1 char
			$text[0] = strtolower($text[0]);
		}

		return $text;

	}

?>