<?php

/***************************************************
// Example setup
//--------------------------------------------------

	//--------------------------------------------------
	// Save images to folders:
	//  files/item_name/original/123.png
	//  files/item_name/100x100/123.jpg
	//  files/item_name/500x500/123.jpg
	//  files/item_name/120xX/123.jpg

		$file = new file('item_name');
		$file->image_save(123, '/path/to/file.png');


//--------------------------------------------------
// End of example setup
***************************************************/

	class file_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->_setup($config);
			}

			protected function _setup($config) {

				//--------------------------------------------------
				// Default config

					$this->config = array(
							'image_type' => 'jpg',
							'image_missing_url_prefix' => NULL, // If you want to show placeholder images, e.g. /a/img/place-holder/100x100.jpg
							'image_missing_url' => NULL,
						);

				//--------------------------------------------------
				// Set config

					if (is_string($config)) {
						$profile = $config;
					} else if (isset($config['profile'])) {
						$profile = $config['profile'];
					} else {
						$profile = NULL;
					}

					if (!is_array($config)) {
						$config = array();
					}

					$prefix = 'file';
					if ($profile !== NULL) {
						$prefix .= '.' . $profile;
						$config['profile'] = $profile;
					}

					$this->config_set(array_merge(config::get_all($prefix), $config));

			}

		//--------------------------------------------------
		// Configuration

			public function config_set($config, $value = NULL) {

				if (is_array($config)) {
					foreach ($config as $key => $value) {
						$this->config[$key] = $value;
					}
				} else {
					$this->config[$config] = $value;
				}

			}

		//--------------------------------------------------
		// Image path and url

			public function image_path_get($id, $size = 'original') {
				return FILE_ROOT . '/' . safe_file_name($this->config['profile']) . '/' . safe_file_name($size) . '/' . $id . '.' . safe_file_name($this->config['image_type']);
			}

			public function image_url_get($id, $size = 'original') {

				$path = $this->image_path_get($id, $size);

				if (is_file($path)) {

					return str_replace('_', '-', FILE_URL . substr($path, strlen(FILE_ROOT)));

				} else if ($this->config['image_missing_url_prefix'] !== NULL) { // Allow for placeholder images

					return $this->config['image_missing_url_prefix'] . '/' . safe_file_name($size) . '.' . safe_file_name($this->config['image_type']);

				} else {

					return $this->config['image_missing_url'];

				}

			}

			public function image_exists($id) {
				return file_exists($this->image_path_get($id));
			}

		//--------------------------------------------------
		// Save

			public function image_save($id, $path = NULL) { // No path set, then re-save images using the original file.

				//--------------------------------------------------
				// Variables

					$folder_path = FILE_ROOT . '/' . safe_file_name($this->config['profile']);
					$original_path = $this->image_path_get($id, 'original');

				//--------------------------------------------------
				// Image

					if ($path === NULL) {

						$path = $original_path;

					} else {

						$original_dir = dirname($original_path);
						if (!is_dir($original_dir)) {
							mkdir($original_dir, 0777, true); // Most installs will write as the "apache" user, which is a problem if the normal user account can't edit/delete these files.
						}

						$source_image = new image($path); // The image will be re-saved to ensure no hacked files are uploaded and exposed though the FILE_URL folder.

						if ($this->config['image_type'] == 'png') {
							$source_image->save_png($original_path);
						} else if ($this->config['image_type'] == 'jpg') {
							$source_image->save_jpg($original_path);
						} else if ($this->config['image_type'] == 'gif') {
							$source_image->save_gif($original_path);
						} else {
							exit_with_error('Unknown image type "' . $this->config['image_type'] . '"');
						}

					}

					if (!is_file($path)) {
						exit_with_error('Cannot open image file "' . $path . '"');
					}

				//--------------------------------------------------
				// Sub sizes

					if ($handle = opendir($folder_path)) {
						while (false !== ($size = readdir($handle))) {
							if (substr($size, 0, 1) != '.' && $size != 'original') {

								$image = new image($original_path); // TODO: need a copy of the image, so it does not get scaled down, then back up again

								// See below... but what happens if the aspect ratio of the image
								// does not allow it to confirm to the boundaries... does it set a
								// background colour, or crop the image?

								$image->save_jpg($this->image_path_get($id, $size));
								$image->destroy();

							}
						}
						closedir($handle);
					}

				//--------------------------------------------------
				// Cleanup source image

					$source_image->destroy();

			}

			public function image_delete($id) {
			}

	}

//--------------------------------------------------
// Folder looping

	if (false) {

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

	}

?>