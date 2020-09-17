<?php

//--------------------------------------------------
// Check image class

	$image = new image('/path/to/file.jpg');

	for ($k = 0; $k < 30; $k++) {

		$config = [];

		if (mt_rand(1, 2) == 1) {
			if (mt_rand(1, 2) == 1) {
				$config['width'] = mt_rand(10, 1000);
			} else {
				$config['width_min'] = mt_rand(10, 900);
				$config['width_max'] = mt_rand($config['width_min'], 1000);
			}
		}

		if (mt_rand(1, 2) == 1) {
			if (mt_rand(1, 2) == 1) {
				$config['height'] = mt_rand(10, 1000);
			} else {
				$config['height_min'] = mt_rand(10, 900);
				$config['height_max'] = mt_rand($config['height_min'], 1000);
			}
		}

		$config['background'] = '000000';

		$image->resize($config);

		$output_width = $image->width_get();
		$output_height = $image->height_get();

		if (isset($config['width'])      && $config['width']      != $output_width)  exit_with_error('Width check error',      print_r($config, true) . "\n\n" . $output_height . 'x' . $output_height);
		if (isset($config['width_min'])  && $config['width_min']  >  $output_width)  exit_with_error('Min width check error',  print_r($config, true) . "\n\n" . $output_height . 'x' . $output_height);
		if (isset($config['width_max'])  && $config['width_max']  <  $output_width)  exit_with_error('Max width check error',  print_r($config, true) . "\n\n" . $output_height . 'x' . $output_height);
		if (isset($config['height'])     && $config['height']     != $output_height) exit_with_error('Height check error',     print_r($config, true) . "\n\n" . $output_height . 'x' . $output_height);
		if (isset($config['height_min']) && $config['height_min'] >  $output_height) exit_with_error('Min height check error', print_r($config, true) . "\n\n" . $output_height . 'x' . $output_height);
		if (isset($config['height_max']) && $config['height_max'] <  $output_height) exit_with_error('Max height check error', print_r($config, true) . "\n\n" . $output_height . 'x' . $output_height);

	}

//--------------------------------------------------
// Folder names

	mime_set('text/plain');

	$sizes = array(

			'XxX',

			'Xx100',
			'XxX-100',
			'Xx1-100',
			'Xx90-100',

			'X-100x100',
			'X-100xX-100',
			'X-100x1-100',
			'X-100x90-100',

			'100xX',
			'X-100xX',
			'1-100xX',
			'90-100xX',

			'100xX-100',
			'X-100xX-100',
			'1-100xX-100',
			'90-100xX-100',

			'100x100',
			'X-100xX-100',
			'1-100x1-100',
			'90-100x90-100',

			'100x0-200_000000', // Hex value for a background colour, so it does not crop the image.

		);

	foreach ($sizes as $size) {

		echo "\n\n" . $size . "\n";

		// Process

		echo debug_dump($config);

	}

//--------------------------------------------------
// Done

	exit();

?>