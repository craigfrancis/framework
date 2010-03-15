<?php

//--------------------------------------------------
// Convert a UNIX time-stamp into human text

	function timestamp_to_human($unix) {

		//--------------------------------------------------
		// Maths

			$sec = $unix % 60;
			$unix -= $sec;

			$minSeconds = $unix % 3600;
			$unix -= $minSeconds;
			$min = ($minSeconds / 60);

			$hourSeconds = $unix % 86400;
			$unix -= $hourSeconds;
			$hour = ($hourSeconds / 3600);

			$daySeconds = $unix % 604800;
			$unix -= $daySeconds;
			$day = ($daySeconds / 86400);

			$week = ($unix / 604800);

		//--------------------------------------------------
		// Text

			$output = '';

			if ($week > 0) $output .= ', ' . $week . ' week'   . ($week != 1 ? 's' : '');
			if ($day  > 0) $output .= ', ' . $day  . ' day'    . ($day  != 1 ? 's' : '');
			if ($hour > 0) $output .= ', ' . $hour . ' hour'   . ($hour != 1 ? 's' : '');
			if ($min  > 0) $output .= ', ' . $min  . ' minute' . ($min  != 1 ? 's' : '');

			if ($sec > 0 || $output == '') {
				$output .= ', ' . $sec  . ' second' . ($sec != 1 ? 's' : '');
			}

		//--------------------------------------------------
		// Grammar

			$output = substr($output, 2);
			$output = preg_replace('/, ([^,]+)$/', ' and $1', $output);

		//--------------------------------------------------
		// Return the output

			return $output;

	}

?>