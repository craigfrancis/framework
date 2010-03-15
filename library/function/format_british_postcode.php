<?php

//--------------------------------------------------
// Take a postcode and format it correctly, an
// invalid postcode will return NULL

	function format_british_postcode($postcode) {

		//--------------------------------------------------
		// Clean up the user input

			$postcode = strtoupper($postcode);
			$postcode = preg_replace('/[^A-Z0-9]/', '', $postcode);
			$postcode = preg_replace('/([A-Z0-9]{3})$/', ' \1', $postcode);
			$postcode = trim($postcode);

		//--------------------------------------------------
		// Check that the submitted value is a valid
		// British postcode: AN NAA | ANN NAA | AAN NAA |
		// AANN NAA | ANA NAA | AANA NAA

			if (preg_match('/^[a-z](\d[a-z\d]?|[a-z]\d[a-z\d]?) \d[a-z]{2}$/i', $postcode)) {
				return $postcode;
			} else {
				return NULL;
			}

	}

?>