<?php

	// http://www.phpprime.com/doc/helpers/file/

	// require_once(FRAMEWORK_ROOT . '/library/tests/class-file.php');

	class file_base extends check {

		//--------------------------------------------------
		// Variables

			private $config = [];

		//--------------------------------------------------
		// Setup

			public function __construct($config) { // Either a profile name (string), or config options (array).
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
				// Config

					$this->config = array_merge($this->config, [
							'profile'                 => 'default',
							'file_private'            => false,
							'file_root'               => NULL,
							'file_url'                => NULL,
							'file_timestamp_url'      => config::get('output.timestamp_url', false),
							'file_ext'                => NULL,
							'file_folder_division'    => NULL, // Set to something like "1000" so the folder structure can by divided into folders /files/008000/8192
							'file_missing_url'        => NULL,
							'image_type'              => 'jpg',
							'image_quality'           => NULL,
							'image_preserve_original' => false, // Ideally the image needs to be re-saved, ensuring no hacked files are uploaded and exposed on the website, e.g. http://blog.portswigger.net/2016/12/bypassing-csp-using-polyglot-jpegs.html
							'image_preserve_unsafe'   => false,
							'image_url_prefix'        => '', // Could include the domain name (e.g. for email images).
							'image_placeholder_url'   => NULL, // If you want to show placeholder images, e.g. /a/img/place-holder/100x100.jpg
							'image_missing_url'       => NULL,
							'image_background'        => NULL, // If images should not be cropped, but have borders instead (e.g. '000000' for black)
						], config::get_all('file.default'));

					if ($profile !== NULL) {
						$this->config = array_merge($this->config, config::get_all('file.' . $profile));
						$this->config['profile'] = $profile;
					}

					if (is_array($config)) {
						$this->config = array_merge($this->config, $config);
					}

			}

			public function config_set($key, $value) {
				$this->config[$key] = $value;
			}

			public function config_set_default($key, $value) {
				if (!isset($this->config[$key])) {
					$this->config[$key] = $value;
				}
			}

			public function config_exists($key) {
				return isset($this->config[$key]);
			}

			public function config_get($key) {
				if ($key == 'file_root' && $this->config[$key] === NULL) return ($this->config['file_private'] ? PRIVATE_ROOT . '/files' : FILE_ROOT);
				if ($key == 'file_url'  && $this->config[$key] === NULL) return ($this->config['file_private'] ? NULL : FILE_URL);
				if (array_key_exists($key, $this->config)) {
					return $this->config[$key];
				} else {
					exit_with_error('Unknown file config type "' . $key . '"');
				}
			}

			public function folder_path_get() {
				return $this->config_get('file_root') . '/' . safe_file_name($this->config['profile']);
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

					$folder = $this->folder_path_get() . '/' . $divide;

					if (!is_dir($folder)) {
						@mkdir($folder, 0777, true);
						if (!is_dir($folder)) {
							exit_with_error('Cannot create folder ' . $folder);
						} else {
							@chmod($folder, 0777);
						}
					}

					$path = $folder . '/' . str_pad($id, $division_len, '0', STR_PAD_LEFT);

					if ($ext !== NULL) {
						$path .= '.' . safe_file_name($ext);
					}

					return $path;

				}

			}

			public function file_url_get($id, $ext = NULL) {

				$file_url = $this->config_get('file_url');

				if ($file_url === NULL || $file_url === false) {
					exit_with_error('There is no public url for this file.', $this->config['profile'] . ' - ' . $id);
				}

				$path = $this->file_path_get($id, $ext);

				if (is_file($path)) {

					$url = $file_url . substr($path, strlen($this->config_get('file_root')));

					if ($this->config['file_timestamp_url']) {
						$url = timestamp_url($url, filemtime($path));
					}

				} else {

					$url = $this->config['file_missing_url'];

				}

				return $url;

			}

			public function file_exists($id, $ext = NULL) {
				return is_file($this->file_path_get($id, $ext)); // file_exists returns true for directories.
			}

			public function file_save($id, $path, $ext = NULL) {
				$dest = $this->file_path_get($id, $ext);
				$this->_writable_check(dirname($dest));
				copy($path, $dest);
				@chmod($dest, octdec(config::get('file.default_permission', 666)));
			}

			public function file_save_contents($id, $contents, $ext = NULL) {
				$dest = $this->file_path_get($id, $ext);
				$this->_writable_check(dirname($dest));
				file_put_contents($dest, $contents);
				@chmod($dest, octdec(config::get('file.default_permission', 666)));
			}

			public function file_save_image($id, $path, $ext = NULL) { // Use image_save() to have different image versions.

				if ($ext === NULL) {
					$ext = $this->config['file_ext'];
				}

				$dest_path = $this->file_path_get($id, $ext);

				$this->_writable_check(dirname($dest_path));

				$image = new image($path); // The image needs to be re-saved, ensures no hacked files are uploaded and exposed on the website
				$image->save($dest_path, $ext, $this->config['image_quality'], $this->config['image_preserve_unsafe']);

			}

			public function file_delete($id, $ext = NULL) {
				$path = $this->file_path_get($id, $ext);
				if (is_file($path)) {
					unlink($path);
				}
			}

		//--------------------------------------------------
		// Image support

			public function image_exists($id, $size = 'original') {
				return is_file($this->image_path_get($id, $size));
			}

			public function image_path_get($id, $size = 'original') {
				return $this->folder_path_get() . '/' . safe_file_name($size) . '/' . safe_file_name($id) . '.' . safe_file_name($this->config['image_type']);
			}

			public function image_url_get($id, $size = 'original') {

				$file_url = $this->config_get('file_url');

				if ($file_url === NULL || $file_url === false) {
					exit_with_error('There is no public url for this file.', $this->config['profile'] . ' - ' . $id);
				}

				$path = $this->image_path_get($id, $size);

				if (is_file($path)) {

					$url = $file_url . substr($path, strlen($this->config_get('file_root')));

					if ($this->config['file_timestamp_url']) {
						$url = timestamp_url($url, filemtime($path));
					}

				} else if ($this->config['image_placeholder_url'] !== NULL) {

					$url = $this->config['image_placeholder_url'] . '/' . safe_file_name($size) . '.' . safe_file_name($this->config['image_type']);

				} else {

					$url = $this->config['image_missing_url'];

				}

				return $this->config['image_url_prefix'] . $url;

			}

			public function image_html_get($id, $size = 'original', $alt = '', $img_id = NULL) {

				$image_url = $this->image_url_get($id, $size);

				if (!$image_url) {
					return NULL;
				}

				$image_path = $this->image_path_get($id, $size);
				if (is_file($image_path)) {
					$image_info = getimagesize($image_path);
				} else {
					$image_path = PUBLIC_ROOT . $image_url; // Try to find placeholder, where image_path_get must always return saved location.
					if (is_file($image_path)) {
						$image_info = getimagesize($image_path);
					} else {
						$image_info = NULL;
					}
				}

				if ($image_info) {
					return '<img src="' . html($image_url) . '" alt="' . html($alt) . '" width="' . html($image_info[0]) . '" height="' . html($image_info[1]) . '"' . ($img_id === NULL ? '' : ' id="' . html($img_id) . '"') . ' />';
				} else {
					return '<img src="' . html($image_url) . '" alt="' . html($alt) . '"' . ($img_id === NULL ? '' : ' id="' . html($img_id) . '"') . ' />';
				}

			}

		//--------------------------------------------------
		// Save

				//--------------------------------------------------
				// You might need to increase these limits:
				//
				//   ini_set('memory_limit', '1024M');
				//   set_time_limit(5);
				//
				//--------------------------------------------------

			public function image_save($id, $path = NULL) { // No path set, then re-save images using the original file... also see $file->file_save_image() to save a single image

				//--------------------------------------------------
				// Original image

					$original_path = $this->image_path_get($id, 'original');

					if ($path === NULL) {

						$path = $original_path;

					} else {

						$this->_writable_check(dirname($original_path));

						$preserve_original = $this->config['image_preserve_original'];
						if ($this->config['image_preserve_unsafe']) {
							$preserve_original = true;
						}

						$source_image = new image($path);
						$source_image->save($original_path, $this->config['image_type'], $this->config['image_quality'], $preserve_original);
						$source_image->destroy();

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

									$config = [];

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

									$image_path = $this->image_path_get($id, $size);

									$image = new image($original_path); // Need a new copy of the image, so it does not get scaled down, then back up again
									$image->resize($config);
									$image->save($image_path, $this->config['image_type'], $this->config['image_quality'], $this->config['image_preserve_unsafe']);
									$image->destroy();

								//--------------------------------------------------
								// Optimise

									$this->_image_optimise($image_path, $this->config['image_type']);

							}
						}
						closedir($handle);
					}

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

			private function _image_optimise($image_path, $ext) {
				if ($ext == 'jpg') {

					foreach (array('/usr/bin/jpegtran', '/usr/local/bin/jpegtran', '/opt/homebrew/bin/jpegtran') as $command_path) {
						if (@is_executable($command_path)) {

							$command = new command();
							$command->exec($command_path . ' -copy none -optimize -progressive -outfile ? ?', [
									$image_path,
									$image_path,
								]);

							return;

						}
					}
					trigger_error('Could not find path to jpegtran', E_USER_NOTICE);

				} else if ($ext == 'png') {

					foreach (array('/usr/bin/optipng', '/usr/local/bin/optipng', '/opt/homebrew/bin/optipng') as $command_path) {
						if (@is_executable($command_path)) {

							$command = new command();
							$command->exec($command_path, [
									$image_path,
								]);

							return;

						}
					}
					trigger_error('Could not find path to optipng', E_USER_NOTICE);

				}
				return NULL;
			}

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

?>