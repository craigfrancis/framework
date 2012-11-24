<?php

/***************************************************

	//--------------------------------------------------
	// Example setup

			// Save images to folders:
			//  files/item_name/original/123.png
			//  files/item_name/100x100/123.jpg
			//  files/item_name/500x500/123.jpg
			//  files/item_name/120xX/123.jpg

		$file = new file('item_name');
		$file->image_save(123, '/path/to/file.png');

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
							'file_root' => FILE_ROOT,
							'file_url' => FILE_URL,
							'file_folder_division' => NULL, // Set to something like "1000" so the folder structure can by divided into folders /files/008000/8192
							'file_missing_url' => NULL,
							'image_type' => 'png',
							'image_placeholder_url' => NULL, // If you want to show placeholder images, e.g. /a/img/place-holder/100x100.jpg
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

			public function folder_path_get() {
				return $this->config['file_root'] . '/' . safe_file_name($this->config['profile']);
			}

		//--------------------------------------------------
		// Standard file support

			public function file_path_get($id, $ext = NULL) {

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

			public function file_save($file, $id, $ext = NULL) {
				file_put_contents($this->file_path_get($id, $ext), $file);
			}

			public function file_exists($id, $ext = NULL) {
				return file_exists($this->file_path_get($id, $ext));
			}

		//--------------------------------------------------
		// Image support

			public function image_path_get($id, $size = 'original') {
				return $this->folder_path_get() . '/' . safe_file_name($size) . '/' . safe_file_name($id) . '.' . safe_file_name($this->config['image_type']);
			}

			public function image_url_get($id, $size = 'original') {

				$path = $this->image_path_get($id, $size);

				if (is_file($path)) {

					return str_replace('_', '-', $this->config['file_url'] . substr($path, strlen($this->config['file_root'])));

				} else if ($this->config['image_placeholder_url'] !== NULL) {

					return $this->config['image_placeholder_url'] . '/' . safe_file_name($size) . '.' . safe_file_name($this->config['image_type']);

				} else {

					return $this->config['image_missing_url'];

				}

			}

			public function image_exists($id) {
				return file_exists($this->image_path_get($id));
			}

			public function image_get_html($id, $alt = '', $size = 'original') {

				$image_info = getimagesize($this->image_path_get($id, $size));
				if ($image_info) {
					return '<img src="' . html($this->image_url_get($id, $size)) . '" alt="' . html($alt) . '" width="' . html($image_info[0]) . '" height="' . html($image_info[1]) . '" />';
				} else {
					return 'N/A';
				}

			}

		//--------------------------------------------------
		// Save

			public function image_save($id, $path = NULL) { // No path set, then re-save images using the original file.

				//--------------------------------------------------
				// Original image

					$original_path = $this->image_path_get($id, 'original');

					if ($path === NULL) {

						$path = $original_path;

					} else {

						$original_dir = dirname($original_path);
						if (!is_dir($original_dir)) {
							mkdir($original_dir, 0777, true); // Most installs will write as the "apache" user, which is a problem if the normal user account can't edit/delete these files.
						}

						$source_image = new image($path); // The image needs to be re-saved, ensures no hacked files are uploaded and exposed though the FILE_URL folder.
						$source_image->save($original_path, $this->config['image_type']);

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
									}

								//--------------------------------------------------
								// Load image, resize, and save

									$image = new image($original_path); // Need a new copy of the image, so it does not get scaled down, then back up again
									$image->resize($config);
									$image->save($this->image_path_get($id, $size), $this->config['image_type']);
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

	}

//--------------------------------------------------
// Folder looping

	if (false) {

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

			echo debug_dump($config);

		}

		exit();

	}

?>