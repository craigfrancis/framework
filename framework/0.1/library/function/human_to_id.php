<?php

//--------------------------------------------------
// Quick function to convert human to an id

	function human_to_id($text) {

		$text = strtolower($text);
		$text = preg_replace('/[^a-z0-9_]/i', '-', $text);
		$text = preg_replace('/--+/', '-', $text);
		$text = preg_replace('/-+$/', '', $text);
		$text = preg_replace('/^-+/', '', $text);

		return $text;

	}

?>