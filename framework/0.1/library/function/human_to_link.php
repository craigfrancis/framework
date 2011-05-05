<?php

//--------------------------------------------------
// Quick function to convert human to link

	function human_to_link($text) {
		return str_replace('_', '-', human_to_id($text));
	}

?>