<?php

/***************************************************

	//--------------------------------------------------
	// License

		This source code is released under the BSD licence,
		see the end of this script for the full details.
		It was originally created by Craig Francis in 2006.

		http://www.craigfrancis.co.uk/features/code/phpImage/

	//--------------------------------------------------
	// Example setup

		//--------------------------------------------------
		// Load image and send to browser

			$image = new image('1.jpg');
			$image->output_jpg();

		//--------------------------------------------------
		// Load two images

			$image_sub = new image('2.gif');

			$image = new image('1.jpg');
			$image->image_add($image_sub, 10, 123);
			$image->output_gif();

	//--------------------------------------------------
	// Other ideas (TODO)

		http://docs.magentocommerce.com/Varien/Varien_Image/Varien_Image.html

***************************************************/

	define('IMAGE_LOAD_SUCCESS', 0);
	define('IMAGE_LOAD_ERR_GONE', 1);
	define('IMAGE_LOAD_ERR_SIZE', 2);
	define('IMAGE_LOAD_ERR_READ', 3);

	class image_base extends check {

		//--------------------------------------------------
		// Variables

			private $image_ref = NULL;
			private $image_width = NULL;
			private $image_height = NULL;
			private $image_type = NULL;

			private $alpha_blending = true;

		//--------------------------------------------------
		// Setup

			public function __construct($file_path = NULL) {

				if ($file_path !== NULL) {
					$this->load_from_file($file_path);
				}

			}

		//--------------------------------------------------
		// Create

			public function create_image($width, $height, $config = NULL) {

				//--------------------------------------------------
				// Config

					$defaults = array(
							'background' => NULL,
						);

					if (!is_array($config)) {
						$config = array();
					}

					$config = array_merge($defaults, $config);

				//--------------------------------------------------
				// Kill old image

					if ($this->image_ref) {
						imagedestroy($this->image_ref);
					}

				//--------------------------------------------------
				// Create

					$this->image_ref = $this->_create_canvas($width, $height);
					$this->image_width = $width;
					$this->image_height = $height;
					$this->image_type = NULL; // Unknown

				//--------------------------------------------------
				// Background

					if ($config['background'] !== NULL) {
						imagefill($this->image_ref, 0, 0, $this->_colour_allocate($this->image_ref, $config['background']));
					}

			}

			public function load_from_file($file_path) {

				$return = $this->_load_image($file_path);
				if (!is_array($return)) {
					return $return;
				}

				$this->image_ref = $return['ref'];
				$this->image_width = $return['width'];
				$this->image_height = $return['height'];
				$this->image_type = $return['type'];

				if (is_object($file_path) && (get_class($file_path) == 'image' || is_subclass_of($file_path, 'image'))) {
					$this->alpha_blending = $file_path->alpha_blending;
				}

				if (!imageistruecolor($this->image_ref)) { // Happens when using imagecreatefromgif

					// Disabled as this breaks Creative Metier on the SS server when doing a simple load and save on a gif with transparency.

					// $old_alpha_blending = $this->alpha_blending;
					// $this->alpha_blending = false; // Don't want blending support for this new canvas, while copy over the transparent gif
					// $new_image = $this->_create_canvas($this->image_width, $this->image_height);
					// imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_width, $this->image_height);
					// imagedestroy($this->image_ref);
					// $this->image_ref = $new_image;
					// $this->alpha_blending_set($old_alpha_blending);

				}

				return IMAGE_LOAD_SUCCESS;

			}

			private function _load_image($image) {

				//--------------------------------------------------
				// Return

					$return = array();

				//--------------------------------------------------
				// If an image object was passed into this function

					if (is_object($image) && (get_class($image) == 'image' || is_subclass_of($image, 'image'))) {

						if (!$image->image_ref) {
							return IMAGE_LOAD_ERR_READ;
						}

						$return['ref'] = $image->image_ref;
						$return['width'] = $image->image_width;
						$return['height'] = $image->image_height;
						$return['type'] = $image->image_type;

						return $return;

					}

				//--------------------------------------------------
				// If we were passed an actual GD object

					if (is_resource($image) && get_resource_type($image) == 'gd') {

						$return['ref'] = $image;
						$return['width'] = imagesx($image);
						$return['height'] = imagesy($image);
						$return['type'] = NULL; // Unknown

						return $return;

					}

				//--------------------------------------------------
				// If not a file

					if (substr($image, 0, 7) != 'http://' && (!is_file($image) || !is_readable($image))) {
						return IMAGE_LOAD_ERR_GONE;
					}

				//--------------------------------------------------
				// Image dimensions

					$dimensions = getimagesize($image);
					if ($dimensions !== false) {
						$return['width'] = $dimensions[0];
						$return['height'] = $dimensions[1];
						$return['type'] = $dimensions[2];
					} else {
						return IMAGE_LOAD_ERR_SIZE;
					}

				//--------------------------------------------------
				// Reference

					if ($return['type'] == IMAGETYPE_JPEG) {

						$return['ref'] = imagecreatefromjpeg($image);

					} else if ($return['type'] == IMAGETYPE_GIF) {

						$return['ref'] = imagecreatefromgif($image);

					} else if ($return['type'] == IMAGETYPE_PNG) {

						$return['ref'] = imagecreatefrompng($image);

					} else {

						return IMAGE_LOAD_ERR_READ;

					}

				//--------------------------------------------------
				// Return

					return $return;

			}

			private function _colour_allocate($image, $colour) {
				if (is_array($colour)) {
					if (isset($colour['red']) && isset($colour['green']) && isset($colour['blue'])) {
						return imagecolorallocate($image, $colour['red'], $colour['green'], $colour['blue']);
					}
				} else if (strlen($colour) == 6) {
					return imagecolorallocate($image, hexdec(substr($colour, 0, 2)), hexdec(substr($colour, 2, 2)), hexdec(substr($colour, 4, 2)));
				}
				exit_with_error('TODO: Different types of colour specifications (e.g. hex value)');
			}

		//--------------------------------------------------
		// Create canvas (alpha blending support)

			private function _create_canvas($width, $height) {

				$image = imagecreatetruecolor($width, $height);
				$image = $this->alpha_blending_update($image);

				if (!$this->alpha_blending) {
					imagecolortransparent($image, imagecolorallocatealpha($image, 0, 0, 0, 127));
					// $background = imagecolorallocatealpha($image, 0, 0, 0, 127); // Does not work when opening and directly saving a gif with transparency
					// imagefill($image, 0, 0, $background);
				}

				return $image;

			}

			public function alpha_blending_set($enabled) {
				$this->alpha_blending = ($enabled === true);
				$this->alpha_blending_update($this->image_ref);
			}

			public function alpha_blending_update($image) {
				if ($this->alpha_blending) {
					imagealphablending($image, true); // Must be on (default) for True_type fonts
					imagesavealpha($image, false);
				} else {
					imagealphablending($image, false); // Must be off for 'save alpha'
					imagesavealpha($image, true);
				}
				return $image;
			}

		//--------------------------------------------------
		// Adding an image

			public function image_add($image, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Load image

						$return = $this->_load_image($image);
						if (!is_array($return)) {
							return $return;
						}

					//--------------------------------------------------
					// Config

						$defaults = array(
								'left' => 0,
								'top' => 0,
								'width' => $return['width'],
								'height' => $return['height'],
								'watermark' => false,
							);

						if (!is_array($config)) {
							$config = array();
						}

						$config = array_merge($defaults, $config);

					//--------------------------------------------------
					// Add

						if ($config['watermark']) {

							exit_with_error('TODO: Watermark support');

						} else {

							imagecopyresampled($this->image_ref, $return['ref'], $config['left'], $config['top'], 0, 0, $config['width'], $config['height'], $return['width'], $return['height']);

						}

					//--------------------------------------------------
					// Success

						return IMAGE_LOAD_SUCCESS;

				}
			}

		//--------------------------------------------------
		// Rotate

			public function rotate($degrees, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Config

						$defaults = array(
								'background' => NULL,
							);

						if (!is_array($config)) {
							$config = array();
						}

						$config = array_merge($defaults, $config);

					//--------------------------------------------------
					// Rotate

						$new_image = imagerotate($this->image_ref, $degrees, 0);

					//--------------------------------------------------
					// Store

						imagedestroy($this->image_ref);

						$this->image_ref = $new_image;
						$this->image_width = imagesx($this->image_ref);
						$this->image_height = imagesy($this->image_ref);

				}
			}

		//--------------------------------------------------
		// Resize

			public function resize($config) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Config

						$defaults = array(
								'width' => NULL,
								'width_min' => NULL,
								'width_max' => NULL,
								'height' => NULL,
								'height_min' => NULL,
								'height_max' => NULL,
								'stretch' => false,
								'crop' => false,
								'grow' => false,
								'background' => '000000',
							);

						if (!is_array($config)) {
							$config = array();
						}

						$config = array_merge($defaults, $config);

					//--------------------------------------------------
					// Base width/height

						if ($config['width'] > 0 && $config['height'] > 0) {

							$new_width = $config['width'];
							$new_height = $config['height'];

						} else if ($config['width'] > 0) {

							$new_width = $config['width'];

							if ($config['crop']) {
								$new_height = $this->image_height;
							} else {
								$new_height = (round($new_width * ($this->image_height / $this->image_width)));
							}

						} else if ($config['height'] > 0) {

							$new_height = $config['height'];
							$new_width = (round($new_height * ($this->image_width / $this->image_height)));

							if ($config['crop'] && $new_width > $this->image_width) {
								$new_width = $this->image_width;
							}

// 							if ($config['crop'] && $new_height > $this->image_height) {
// 								$new_width = $this->image_width;
// 							} else {
//
// 							}

						} else {

							$new_width = $this->image_width;
							$new_height = $this->image_height;

						}

					//--------------------------------------------------
					// Min and max sizes

// $image->resize(array('height' => 300, 'width_min' => 500));

						if ($config['width_min'] > 0 && $new_width < $config['width_min']) {
							$new_width = $config['width_min'];

// 							$test_height = (round($config['width_min'] * ($new_height / $new_width)));
// 							if ($test_height > $this->image_height && $test_height > $new_height) {
// 								$new_height = $test_height;
// 							}

						}

						if ($config['height_min'] > 0 && $new_height < $config['height_min']) {
// 							$new_width = (round($config['height_min'] * ($new_width / $new_height)));
							$new_height = $config['height_min'];
// 							if ($config['width_min'] > 0 && $config['width_min'] > $this->image_width) {
// 								if ($new_width > $config['width_min']) {
// 									$new_width = $config['width_min'];
// 								}
// 							} else {
// 								if ($new_width > $this->image_width) {
// 									$new_width = $this->image_width;
// 								}
// 							}
						}

						if ($config['width_max'] > 0 && $new_width > $config['width_max']) {
							$new_height = (round($config['width_max'] * ($new_height / $new_width)));
							$new_width = $config['width_max'];
						}

						if ($config['height_max'] > 0 && $new_height > $config['height_max']) {
							$new_width = (round($config['height_max'] * ($new_width / $new_height)));
							$new_height = $config['height_max'];
						}

					//--------------------------------------------------
					// No change

						if ($new_width == $this->image_width && $new_height == $this->image_height) {
							return;
						}

					//--------------------------------------------------
					// Re-size

						$new_image = $this->_create_canvas($new_width, $new_height);

						if ($config['stretch']) {

							imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $new_width, $new_height, $this->image_width, $this->image_height);

						} else {

							if ($config['background'] !== NULL) {
								imagefill($new_image, 0, 0, $this->_colour_allocate($new_image, $config['background']));
							}

							$dst_width = $this->image_width;
							$dst_height = $this->image_height;

							if ($config['grow'] && $new_width > $this->image_width && $new_height > $this->image_height) {

								$scaled_width = ceil($new_height * ($this->image_width / $this->image_height));
								$scaled_height = ceil($new_width * ($this->image_height / $this->image_width));

								if ($scaled_height <= $new_height) {
									$dst_width = $new_width;
									$dst_height = $scaled_height;
								} else if ($scaled_width <= $new_width) {
									$dst_width = $scaled_width;
									$dst_height = $new_height;
								} else {
									exit_with_error('Error calculating scaled size (' . $new_width . ' x ' . $new_height . ' / ' . $this->image_width . ' x ' . $this->image_height . ')'); // Mathematically impossible?
								}

							}

							if (!$config['crop']) {

								if ($this->image_width > $new_width) {
									$dst_width = $new_width;
									$dst_height = ceil($new_width * ($this->image_height / $this->image_width));
								}

								if ($this->image_height > $new_height) {
									$dst_width = ceil($new_height * ($this->image_width / $this->image_height));
									$dst_height = $new_height;
								}

							}

							$left = round(($new_width / 2) - ($dst_width / 2));
							$top = round(($new_height / 2) - ($dst_height / 2));

							imagecopyresampled($new_image, $this->image_ref, $left, $top, 0, 0, $dst_width, $dst_height, $this->image_width, $this->image_height);

						}

					//--------------------------------------------------
					// Store

						if (isset($new_image)) {
							imagedestroy($this->image_ref);
							$this->image_ref = $new_image;
							$this->image_width = $new_width;
							$this->image_height = $new_height;
						} else {
							exit_with_error('Unknown image resize options', print_r($config, true));
						}

				}
			}

		//--------------------------------------------------
		// Return image details

			public function width_get() {
				return $this->image_width;
			}

			public function height_get() {
				return $this->image_height;
			}

			public function type_get() {
				return $this->image_type;
			}

			public function ref_get() {
				return $this->image_ref;
			}

		//--------------------------------------------------
		// Print image

			public function output_png($compression = 6) {
				if ($this->image_ref) {
					mime_set('image/png');
					imagepng($this->image_ref, NULL, $compression);
				}
			}

			public function output_gif() {
				if ($this->image_ref) {
					mime_set('image/gif');
					imagegif($this->image_ref);
				}
			}

			public function output_jpg($quality = 75) {
				if ($this->image_ref) {
					mime_set('image/jpeg');
					imagejpeg($this->image_ref, NULL, $quality);
				}
			}

		//--------------------------------------------------
		// Save image

			public function save($file_path, $type = 'png') {
				if ($type == 'png') {
					$this->save_png($file_path);
				} else if ($type == 'gif') {
					$this->save_gif($file_path);
				} else if ($type == 'jpg') {
					$this->save_jpg($file_path);
				} else {
					exit_with_error('Unknown image type "' . $type . '"');
				}
			}

			public function save_png($file_path, $compression = 6) {
				if ($this->image_ref) {
					imagepng($this->image_ref, $file_path, $compression);
					@chmod($file_path, 0666);
				}
			}

			public function save_gif($file_path) {
				if ($this->image_ref) {
					imagegif($this->image_ref, $file_path);
					@chmod($file_path, 0666);
				}
			}

			public function save_jpg($file_path, $quality = 75) {
				if ($this->image_ref) {
					imagejpeg($this->image_ref, $file_path, $quality);
					@chmod($file_path, 0666);
				}
			}

			public function save_tiles($num_tiles_wide, $num_tiles_high, $file_name_prefix, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Config

						$defaults = array(
								'file_ext' => 'jpg',
								'tile_width' => 256,
								'tile_height' => 256,
								'background' => NULL,
								'quality' => NULL,
							);

						if (!is_array($config)) {
							$config = array();
						}

						$config = array_merge($defaults, $config);

					//--------------------------------------------------
					// Create the canvas

						//--------------------------------------------------
						// Start

							$canvas_width = ($num_tiles_wide * $config['tile_width']);
							$canvas_height = ($num_tiles_high * $config['tile_height']);

							$canvas = $this->_create_canvas($canvas_width, $canvas_height);

						//--------------------------------------------------
						// Background

							if ($config['background'] !== NULL) {
								imagefill($canvas, 0, 0, $this->_colour_allocate($canvas, $config['background']));
							}

						//--------------------------------------------------
						// Add image

							if ($this->image_width > $this->image_height) {
								$canvas_zoom_max_level = ceil(log(ceil($this->image_width / $config['tile_width']), 2));
							} else {
								$canvas_zoom_max_level = ceil(log(ceil($this->image_height / $config['tile_height']), 2));
							}

							$canvas_zoom_max_size_width = (intval(pow(2, $canvas_zoom_max_level)) * $config['tile_width']);
							$canvas_zoom_max_size_height = (intval(pow(2, $canvas_zoom_max_level)) * $config['tile_height']);

							$width = (($this->image_width / $canvas_zoom_max_size_width) * $canvas_width);
							$height = (($this->image_height / $canvas_zoom_max_size_height) * $canvas_height);

							$offset_left = (($canvas_width / 2) - ($width / 2));
							$offset_top = (($canvas_height / 2) - ($height / 2));

							imagecopyresampled($canvas, $this->image_ref, $offset_left, $offset_top, 0, 0, $width, $height, $this->image_width, $this->image_height);

					//--------------------------------------------------
					// Save the image parts

						for ($x = 0; $x < $num_tiles_wide; $x++) {
							for ($y = 0; $y < $num_tiles_high; $y++) {

								$tile = $this->_create_canvas($config['tile_width'], $config['tile_height']);

								imagecopyresampled($tile, $canvas, 0, 0, ($x * $config['tile_width']), ($y * $config['tile_width']), $config['tile_width'], $config['tile_height'], $config['tile_width'], $config['tile_height']);

								$file_path = $file_name_prefix . $x . '-' . $y . '.' . $config['file_ext'];

								if ($config['quality']) {
									if ($config['file_ext'] == 'png') imagepng($tile, $file_path, $config['quality']);
									if ($config['file_ext'] == 'jpg') imagejpeg($tile, $file_path, $config['quality']);
								} else {
									if ($config['file_ext'] == 'png') imagepng($tile, $file_path);
									if ($config['file_ext'] == 'gif') imagegif($tile, $file_path);
									if ($config['file_ext'] == 'jpg') imagejpeg($tile, $file_path);
								}

								@chmod($file_path, 0666);

								imagedestroy($tile);

							}
						}

					//--------------------------------------------------
					// Cleanup

						imagedestroy($canvas);

				}
			}

		//--------------------------------------------------
		// Destroy image

			public function destroy() {
				if ($this->image_ref) {
					imagedestroy($this->image_ref);
					$this->image_ref = NULL;
					$this->image_width = NULL;
					$this->image_height = NULL;
					$this->image_type = NULL;
				}
			}

		//--------------------------------------------------
		// Clone support

			public function __clone() {
				$new_image = $this->_create_canvas($this->image_width, $this->image_height);
				imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_width, $this->image_height);
				$this->image_ref = $new_image;
			}

	}

?>