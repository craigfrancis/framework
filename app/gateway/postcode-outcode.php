<?php

//--------------------------------------------------
// Config

	exit('Disabled' . "\n");

//--------------------------------------------------
// Paths

	$source_path = APP_ROOT . '/gateway/postcode-outcode/example.csv';
	$output_path = APP_ROOT . '/gateway/postcode-outcode/outcode.csv';

		// Download the "ONS Postcode Directory" from
		// http://www.ons.gov.uk/ons/external-links/other/ons-geoportal-catalogue.html

		// $source_path = APP_ROOT . '/gateway/postcode-outcode/ONSPD_MAY_2015_UK.csv';

	if (!is_file($source_path)) {
		exit('Missing source file' . "\n");
	}

	if (!is_file($output_path)) {
		exit('Missing output file' . "\n");
	}

	if (!is_writable($output_path)) {
		exit('Cannot write to output file' . "\n");
	}

	if (($source_fp = fopen($source_path, 'r')) === false) {
		exit('Cannot open source file' . "\n");
	}

	if (($output_fp = fopen($output_path, 'w')) === false) {
		exit('Cannot open output file' . "\n");
	}

//--------------------------------------------------
// Parse

	$headings = NULL;
	$outcodes = array();

	while (($data = fgetcsv($source_fp, 1000, ',')) !== false) {
		if ($headings === NULL) {

			$headings = array_flip($data);

			if (isset($headings['pcd2'])) {
				$heading_pcd2 = $headings['pcd2'];
			} else {
				exit('Missing field "pcd2"' . "\n");
			}

			if (isset($headings['lat'])) {
				$heading_lat = $headings['lat'];
			} else {
				exit('Missing field "lat"' . "\n");
			}

			if (isset($headings['long'])) {
				$heading_long = $headings['long'];
			} else {
				exit('Missing field "long"' . "\n");
			}

		} else {

			$outcode = $data[$heading_pcd2];
			$outcode = substr($outcode, 0, strpos($outcode, ' '));

			if (!in_array($outcode, $outcodes)) {

				$latitude = round($data[$heading_lat], 2);
				$longitude = round($data[$heading_long], 2);

				if ($latitude != 99.99 && $longitude != 0) {

					fputcsv($output_fp, array($outcode, $latitude, $longitude));

					$outcodes[] = $outcode;

				}

			}

		}
	}

	fclose($source_fp);
	fclose($output_fp);

//--------------------------------------------------
// Done

	exit('Done (' . count($outcodes) . ')' . "\n");

?>