<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/file/
//--------------------------------------------------

	class file_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = array();

		//--------------------------------------------------
		// Setup

			public function __construct($config = NULL) {
				$this->setup($config);
			}

			protected function setup($config) {

				//--------------------------------------------------
				// Profile

					if (is_string($config)) {
						$profile = $config;
					} else if (isset($config['profile'])) {
						$profile = $config['profile'];
					} else {
						$profile = NULL;
					}

				//--------------------------------------------------
				// Default config

					$default_config = array(
							'file_root' => FILE_ROOT,
							'file_url' => FILE_URL,
							'file_ext' => NULL,
							'file_folder_division' => NULL, // Set to something like "1000" so the folder structure can by divided into folders /files/008000/8192
							'file_missing_url' => NULL,
							'image_type' => 'jpg',
							'image_quality' => NULL,
							'image_url_prefix' => '',
							'image_placeholder_url' => NULL, // If you want to show placeholder images, e.g. /a/img/place-holder/100x100.jpg
							'image_missing_url' => NULL,
							'image_background' => NULL, // If images should not be cropped, but have borders instead (e.g. '000000' for black)
						);

					$default_config = array_merge($default_config, config::get_all('file.default'));

				//--------------------------------------------------
				// Set config

					if (!is_array($config)) {
						$config = array();
					}

					if ($profile !== NULL) {
						$config = array_merge(config::get_all('file.' . $profile), $config);
						$config['profile'] = $profile;
					}

					$this->config = array_merge($default_config, $config);

			}

			public function folder_path_get() {
				return $this->config['file_root'] . '/' . safe_file_name($this->config['profile']);
			}

		//--------------------------------------------------
		// Standard file support

			public function file_path_get($id, $ext = NULL) {

				if ($ext === NULL) {
					$ext = $this->config['file_ext'];
				}

				if ($this->config['file_folder_division'] === NULL) {

					$path = $this->folder_path_get() . '/' . safe_file_name($id);
					if ($ext !== NULL) {
						$path .= '.' . safe_file_name($ext);
					}
					return $path;

				} else {

					$id = intval($id);

					$division_int = intval($this->config['file_folder_division']); // e.g. 1000
					$division_len = strlen($division_int);

					$divide = (floor($id / $division_int) * $division_int);
					$divide = str_pad(intval($divide), ($division_len + 2), '0', STR_PAD_LEFT);

					$folder = $this->config['file_root'] . '/files/' . $folder . '/' . $divide;

					if (!is_dir($folder)) {
						@mkdir($folder, 0777, true);
						if (!is_dir($folder)) {
							exit_with_error('Cannot create folder ' . $folder);
						} else {
							@chmod($folder, 0777);
						}
					}

					$path = $folder . '/' . str_pad($id, $division_len, '0', STR_PAD_LEFT);

					if (!is_dir($path)) {
						@mkdir($path, 0777, true);
						if (!is_dir($path)) {
							exit_with_error('Cannot create sub folder ' . $path);
						} else {
							@chmod($path, 0777);
						}
					}

					if ($ext !== NULL) {
						$path .= '.' . safe_file_name($ext);
					}

					return $path;

				}

			}

			public function file_url_get($id, $ext = NULL) {

				$path = $this->file_path_get($id, $ext);

				if (is_file($path)) {

					return str_replace('_', '-', $this->config['file_url'] . substr($path, strlen($this->config['file_root'])));

				} else {

					return $this->config['file_missing_url'];

				}

			}

			public function file_exists($id, $ext = NULL) {
				return file_exists($this->file_path_get($id, $ext));
			}

			public function file_save($id, $path, $ext = NULL) {
				$dest = $this->file_path_get($id, $ext);
				$this->_writable_check(dirname($dest));
				copy($path, $dest);
			}

			public function file_save_contents($id, $contents, $ext = NULL) {
				$dest = $this->file_path_get($id, $ext);
				$this->_writable_check(dirname($dest));
				file_put_contents($dest, $contents);
			}

			public function file_save_image($id, $path) { // Use image_save() to have different image versions.

				//--------------------------------------------------
				// Path

					$dest_path = $this->file_path_get($id, $this->config['file_ext']);

				//--------------------------------------------------
				// Folder

					$dest_dir = dirname($dest_path);
					if (!is_dir($dest_dir)) {
						if (SERVER == 'live') {
							mkdir($dest_dir, 0777, true); // Most installs will write as the "apache" user, which is a problem if the normal user account can't edit/delete these files.
						}
						if (!is_dir($dest_dir)) {
							exit_with_error('Missing image directory', $dest_dir);
						}
					}
					if (!is_writable($dest_dir)) {
						exit_with_error('Cannot write to image directory', $dest_dir);
					}

				//--------------------------------------------------
				// Save

					$image = new image($path); // The image needs to be re-saved, ensures no hacked files are uploaded and exposed though the FILE_URL folder.
					$image->save($dest_path, $this->config['file_ext'], $this->config['image_quality']);

			}

		//--------------------------------------------------
		// Image support

			public function image_exists($id, $size = 'original') {
				return file_exists($this->image_path_get($id, $size));
			}

			public function image_path_get($id, $size = 'original') {
				return $this->folder_path_get() . '/' . safe_file_name($size) . '/' . safe_file_name($id) . '.' . safe_file_name($this->config['image_type']);
			}

			public function image_url_get($id, $size = 'original') {

				$path = $this->image_path_get($id, $size);

				if (is_file($path)) {

					$url = str_replace('_', '-', $this->config['file_url'] . substr($path, strlen($this->config['file_root'])));

				} else if ($this->config['image_placeholder_url'] !== NULL) {

					$url = $this->config['image_placeholder_url'] . '/' . safe_file_name($size) . '.' . safe_file_name($this->config['image_type']);

				} else {

					$url = $this->config['image_missing_url'];

				}

				return $this->config['image_url_prefix'] . $url;

			}

			public function image_html_get($id, $size = 'original', $alt = '', $img_id = NULL) {

				$image_path = $this->image_path_get($id, $size);

				if (file_exists($image_path)) {
					$image_info = getimagesize($image_path);
					if ($image_info) {
						return '<img src="' . html($this->image_url_get($id, $size)) . '" alt="' . html($alt) . '" width="' . html($image_info[0]) . '" height="' . html($image_info[1]) . '"' . ($img_id === NULL ? '' : ' id="' . html($img_id) . '"') . ' />';
					}
				}

				return NULL;

			}

		//--------------------------------------------------
		// Save

			public function image_save($id, $path = NULL) { // No path set, then re-save images using the original file... also see $file->file_save_image() to save a single image

				//--------------------------------------------------
				// Make sure we have plenty of memory

					ini_set('memory_limit', '1024M');

				//--------------------------------------------------
				// Original image

					$original_path = $this->image_path_get($id, 'original');

					if ($path === NULL) {

						$path = $original_path;

						$source_image = new image($path);

					} else {

						$this->_writable_check(dirname($original_path));

						$source_image = new image($path); // The image needs to be re-saved, ensures no hacked files are uploaded and exposed though the FILE_URL folder.
						$source_image->save($original_path, $this->config['image_type'], $this->config['image_quality']);

					}

					if (!is_file($path)) {
						exit_with_error('Cannot open image file "' . $path . '"');
					}

				//--------------------------------------------------
				// Sub sizes

					if ($handle = opendir($this->folder_path_get())) {
						while (false !== ($size = readdir($handle))) {
							if (substr($size, 0, 1) != '.' && $size != 'original') {

								//--------------------------------------------------
								// Resize config

									$config = array();

									$pos = strpos($size, '_');
									if ($pos !== false) {
										$details = substr($size, 0, $pos);
										$background = substr($size, ($pos + 1));
										if (!preg_match('/^[0-9a-f]{6}$/i', $background)) {
											exit_with_error('Invalid background colour "' . $background . '" on size folder "' . $size . '"');
										}
									} else {
										$details = $size;
										$background = NULL;
									}

									$pos = strpos($details, 'x');
									if ($pos !== false) {
										$width = substr($details, 0, $pos);
										$height = substr($details, ($pos + 1));
									} else {
										exit_with_error('Missing "x" in size folder "' . $size . '"');
									}

									$pos = strpos($width, '-');
									if ($pos !== false) {
										$config['width_min'] = substr($width, 0, $pos);
										$config['width_max'] = substr($width, ($pos + 1));
									} else {
										$config['width'] = $width;
									}

									$pos = strpos($height, '-');
									if ($pos !== false) {
										$config['height_min'] = substr($height, 0, $pos);
										$config['height_max'] = substr($height, ($pos + 1));
									} else {
										$config['height'] = $height;
									}

									foreach ($config as $key => $value) {
										if ($value == 'X') {
											unset($config[$key]);
										} else if (preg_match('/^[0-9]+$/', $value)) {
											$config[$key] = intval($value);
										} else {
											exit_with_error('The "' . $key . '" has the invalid value "' . $value . '" in size folder "' . $size . '"');
										}
									}

									if ($background !== NULL) {

										$config['background'] = $background;

									} else if ($this->config['image_background'] !== NULL) {

										$config['background'] = $this->config['image_background'];

									}

								//--------------------------------------------------
								// Load image, resize, and save

									$image = new image($original_path); // Need a new copy of the image, so it does not get scaled down, then back up again
									$image->resize($config);
									$image->save($this->image_path_get($id, $size), $this->config['image_type'], $this->config['image_quality']);
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

				//--------------------------------------------------
				// Original

					$original_path = $this->image_path_get($id, 'original');
					if (is_file($original_path)) {
						unlink($original_path);
					}

				//--------------------------------------------------
				// Sub sizes

					if ($handle = opendir($this->folder_path_get())) {
						while (false !== ($size = readdir($handle))) {
							if (substr($size, 0, 1) != '.' && $size != 'original') {

								$path = $this->image_path_get($id, $size);
								if (is_file($path)) {
									unlink($path);
								}

							}
						}
						closedir($handle);
					}

			}

		//--------------------------------------------------
		// Support

			private function _writable_check($dir) {

				if (!is_dir($dir)) {
					if (SERVER == 'live') {
						mkdir($dir, 0777, true); // Most installs will write as the "apache" user, which is a problem if the normal user account can't edit/delete these files.
					}
					if (!is_dir($dir)) {
						exit_with_error('Missing directory', $dir);
					}
				}
				if (!is_writable($dir)) {
					exit_with_error('Cannot write to directory', $dir);
				}

			}

	}

//--------------------------------------------------
// Testing

	if (false) {

		//--------------------------------------------------
		// Check image class

			$image = new image('/path/to/file.jpg');

			for ($k = 0; $k < 30; $k++) {

				$config = array();

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

	}

?>