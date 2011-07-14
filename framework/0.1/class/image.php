<?php

	class image extends check {

		// ...

		public function save_to_size_folders($path, $file_name) {

			//--------------------------------------------------
			// Image type

				$file_ext = 'jpg';

			//--------------------------------------------------
			// Original

				$path_original = $path . '/original/' . $file_name;

				if (is_dir($path . '/original/')) {
					// Save jpg?
				}

			//--------------------------------------------------
			// Sub sizes

				if ($handle = opendir($path)) {
					while (false !== ($size = readdir($handle))) {

						$image = new image($path_original); // TODO: need a copy of the image, so it does not get scaled down, then up

						// See below... but what happens if the aspect ratio of the image
						// does not allow it to confirm to the boundaries... does it set a
						// background colour, or crop the image?

						$image->save_jpg($base_path . '/' . $size . '/' . $file_name . '.jpg');
						$image->destroy();

					}
				}

		}

		public function delete_from_size_folders($path, $file_name) {
		}

	}

//--------------------------------------------------
// Folder looping

	$sizes = array(

			'original',
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

			'100x100', // Forces the size, either to distort or crop? perhaps 100x100_crop?
			'X-100xX-100',
			'1-100x1-100',
			'90-100x90-100',

			'100x0-200_000000', // Hex value for a background colour, so it does not crop the image.

		);

	foreach ($sizes as $size) {
		echo $size . "\n";
		if (preg_match('/^([0-9]+|X)((?:-[0-9]+)?)x([0-9]+|X)((?:-[0-9]+)?)$/', $size, $matches)) {

			list($size, $min_width, $max_width, $min_height, $max_height) = $matches;

			$max_width = str_replace('-', '', $max_width);
			$max_height = str_replace('-', '', $max_height);

			if ($min_width === 'X' && $max_width != '') $min_width = 1;
			if ($min_height === 'X' && $max_height != '') $min_height = 1;

			if ($min_width === 'X' && $min_height === 'X') {

				echo '  No change' . "\n";

			} else if ($min_width === 'X') {

				if ($max_height === '') {
					echo '  Fixed height = ' . $min_height . "\n"; // scale_height
				} else {
					echo '  Min height = ' . $min_height . "\n";
					echo '  Max height = ' . $max_height . "\n";
				}

			} else if ($min_height === 'X') {

				if ($max_width === '') {
					echo '  Fixed width = ' . $min_width . "\n"; // scale_width
				} else {
					echo '  Min width = ' . $min_width . "\n";
					echo '  Max width = ' . $max_width . "\n";
				}

			} else if ($max_height === '' && $max_width === '') {

				echo '  Fixed width = ' . $min_width . "\n"; // force_size($width, $height)
				echo '  Fixed height = ' . $min_height . "\n";

			} else if ($max_height === '') {

				echo '  Min width = ' . $min_width . "\n";
				echo '  Max width = ' . $max_width . "\n";
				echo '  Fixed height = ' . $min_height . "\n";

			} else if ($max_width === '') {

				echo '  Fixed width = ' . $min_width . "\n";
				echo '  Min height = ' . $min_height . "\n";
				echo '  Max height = ' . $max_height . "\n";

			} else {

				echo '  Min width = ' . $min_width . "\n";
				echo '  Max width = ' . $max_width . "\n";
				echo '  Min height = ' . $min_height . "\n";
				echo '  Max height = ' . $max_height . "\n";

			}

			echo "\n";

		}
	}

?>