<?php

	class image {

		// ...

		function save_to_size_folders($path, $file_name) {

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

						// TODO: See below... but what happens if the aspect ratio of the image
						// does not allow it to confirm to the boundaries... does it set a
						// background colour, or crop the image?

						$image->saveJpg($basePath . '/' . $size . '/' . $file_name . '.jpg');
						$image->destroy();

					}
				}

		}

		function delete_from_size_folders($path, $file_name) {
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

	foreach ($sizes as $cSize) {
		echo $cSize . "\n";
		if (preg_match('/^([0-9]+|X)((?:-[0-9]+)?)x([0-9]+|X)((?:-[0-9]+)?)$/', $cSize, $matches)) {

			list($cSize, $minWidth, $maxWidth, $minHeight, $maxHeight) = $matches;

			$maxWidth = str_replace('-', '', $maxWidth);
			$maxHeight = str_replace('-', '', $maxHeight);

			if ($minWidth === 'X' && $maxWidth != '') $minWidth = 1;
			if ($minHeight === 'X' && $maxHeight != '') $minHeight = 1;

			if ($minWidth === 'X' && $minHeight === 'X') {

				echo '  No change' . "\n";

			} else if ($minWidth === 'X') {

				if ($maxHeight === '') {
					echo '  Fixed height = ' . $minHeight . "\n"; // scaleHeight
				} else {
					echo '  Min height = ' . $minHeight . "\n";
					echo '  Max height = ' . $maxHeight . "\n";
				}

			} else if ($minHeight === 'X') {

				if ($maxWidth === '') {
					echo '  Fixed width = ' . $minWidth . "\n"; // scaleWidth
				} else {
					echo '  Min width = ' . $minWidth . "\n";
					echo '  Max width = ' . $maxWidth . "\n";
				}

			} else if ($maxHeight === '' && $maxWidth === '') {

				echo '  Fixed width = ' . $minWidth . "\n"; // forceSize($width, $height)
				echo '  Fixed height = ' . $minHeight . "\n";

			} else if ($maxHeight === '') {

				echo '  Min width = ' . $minWidth . "\n";
				echo '  Max width = ' . $maxWidth . "\n";
				echo '  Fixed height = ' . $minHeight . "\n";

			} else if ($maxWidth === '') {

				echo '  Fixed width = ' . $minWidth . "\n";
				echo '  Min height = ' . $minHeight . "\n";
				echo '  Max height = ' . $maxHeight . "\n";

			} else {

				echo '  Min width = ' . $minWidth . "\n";
				echo '  Max width = ' . $maxWidth . "\n";
				echo '  Min height = ' . $minHeight . "\n";
				echo '  Max height = ' . $maxHeight . "\n";

			}

			echo "\n";

		}
	}

?>