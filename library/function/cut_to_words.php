<?php

	function cut_to_words($text, $words) {
		$text = strip_tags($text);
		$text = explode(' ', $text, $words + 1);
		if (count($text) > $words) {
			$dumpData = array_pop($text);
		}
		return implode(' ', $text);
	}

?>