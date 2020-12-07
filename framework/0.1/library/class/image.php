<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/helpers/image/
//--------------------------------------------------

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
			private $image_source = NULL;

			public $alpha_blend = false; // Don't blend by default, and expose value (public) for when this image is being added to another image
			public $alpha_save = true;

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
						$config = [];
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
					$this->image_source = NULL;

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
				$this->image_source = ($return['source'] == 'file' ? $file_path : NULL);

				if ($return['source'] == 'object') {
					$this->alpha_blend = $file_path->alpha_blend;
					$this->alpha_save = $file_path->alpha_save;
				}

				$this->_alpha_update($this->image_ref);

				if (!imageistruecolor($this->image_ref)) { // Happens when using imagecreatefromgif

					// Disabled as this breaks when doing a simple load and save on a gif with transparency.

					// $old_alpha_blend = $this->alpha_blend;
					// $this->alpha_blend = false; // Don't want blending support for this new canvas, while copy over the transparent gif
					// $new_image = $this->_create_canvas($this->image_width, $this->image_height);
					// imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_width, $this->image_height);
					// imagedestroy($this->image_ref);
					// $this->image_ref = $new_image;
					// $this->alpha_blend_set($old_alpha_blend);

				}

				return IMAGE_LOAD_SUCCESS;

			}

			public function load_from_string($data) {

				$image = imagecreatefromstring($data);

				if ($image) {

					$this->image_ref = $image;
					$this->image_width = imagesx($image);
					$this->image_height = imagesy($image);
					$this->image_type = NULL; // Unknown
					$this->image_source = NULL;

					return IMAGE_LOAD_SUCCESS;

				} else {

					return IMAGE_LOAD_ERR_READ;

				}

			}

			private function _load_image($image) {

				//--------------------------------------------------
				// Return

					$return = [];

				//--------------------------------------------------
				// If an image object was passed into this function

					if ($image instanceof image) {

						if (!$image->image_ref) {
							return IMAGE_LOAD_ERR_READ;
						}

						$return['ref'] = $image->image_ref;
						$return['width'] = $image->image_width;
						$return['height'] = $image->image_height;
						$return['type'] = $image->image_type;
						$return['source'] = 'object';

						return $return;

					}

				//--------------------------------------------------
				// If we were passed an actual GD object

					$is_gd = (is_object($image) && $image instanceof GdImage);
					if (!$is_gd) {
						$is_gd = (is_resource($image) && get_resource_type($image) == 'gd'); // for PHP 7
					}

					if ($is_gd) {

						$return['ref'] = $image;
						$return['width'] = imagesx($image);
						$return['height'] = imagesy($image);
						$return['type'] = NULL; // Unknown
						$return['source'] = 'gd';

						return $return;

					}

				//--------------------------------------------------
				// If not a file

					if (!is_file($image) || !is_readable($image)) {
						return IMAGE_LOAD_ERR_GONE;
					}

				//--------------------------------------------------
				// Image dimensions

					$dimensions = getimagesize($image);
					if ($dimensions !== false) {
						$return['width'] = $dimensions[0];
						$return['height'] = $dimensions[1];
						$return['source'] = 'file';
					} else {
						return IMAGE_LOAD_ERR_SIZE;
					}

				//--------------------------------------------------
				// Reference

					if ($dimensions[2] == IMAGETYPE_JPEG) {

						$return['ref'] = imagecreatefromjpeg($image);
						$return['type'] = 'jpg';

					} else if ($dimensions[2] == IMAGETYPE_GIF) {

						$return['ref'] = imagecreatefromgif($image);
						$return['type'] = 'gif';

					} else if ($dimensions[2] == IMAGETYPE_PNG) {

						$return['ref'] = imagecreatefrompng($image);
						$return['type'] = 'png';

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
				exit_with_error('Unknown colour defined', print_r($colour, true));
			}

		//--------------------------------------------------
		// Create canvas (alpha config)

			private function _create_canvas($width, $height) {

				$image = imagecreatetruecolor($width, $height);

				$this->_alpha_update($image);

				if (!$this->alpha_blend) {
					// imagecolortransparent($image, imagecolorallocatealpha($image, 0, 0, 0, 127));
					// - or -
					// $background = imagecolorallocatealpha($image, 0, 0, 0, 127); // Does not work when opening and directly saving a gif with transparency
					// imagefill($image, 0, 0, $background);
				}

				return $image;

			}

			public function alpha_blend_set($enabled) {
				$this->alpha_blend = ($enabled === true);
				$this->_alpha_update($this->image_ref);
			}

			public function alpha_save_set($enabled) {
				$this->alpha_save = ($enabled === true);
				$this->_alpha_update($this->image_ref);
			}

			private function _alpha_update($image) {
				imagealphablending($image, $this->alpha_blend); // Must be on for True_type fonts
				imagesavealpha($image, $this->alpha_save);
			}

		//--------------------------------------------------
		// Adding an image

			public function image_add($image, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Load image

						$src = $this->_load_image($image);
						if (!is_array($src)) {
							return $src;
						}

					//--------------------------------------------------
					// Config

						$defaults = array(
								'width' => NULL,
								'height' => NULL,
								'left' => 0,
								'top' => 0,
								'repeat' => NULL,
							);

						if (!is_array($config)) {
							$config = [];
						}

						$config = array_merge($defaults, $config);

					//--------------------------------------------------
					// Add

						if ($config['repeat']) {

							$dst_width  = ($config['width']  !== NULL ? $config['width']  : $this->image_width);
							$dst_height = ($config['height'] !== NULL ? $config['height'] : $this->image_height);

							exit_with_error('TODO: Repeat support'); // aka 'watermark'... true? position/align = [left/centre/right] x [top/middle/bottom] ... width/height is the area on dest to cover, don't stretch (if not set, assume full dest image size)

								// http://fuelphp.com/docs/classes/image.html#/method_watermark
								// http://docs.magentocommerce.com/Varien/Varien_Image/Varien_Image.html

						} else {

							$dst_width  = ($config['width']  !== NULL ? $config['width']  : $src['width']);
							$dst_height = ($config['height'] !== NULL ? $config['height'] : $src['height']);

							imagecopyresampled($this->image_ref, $src['ref'], $config['left'], $config['top'], 0, 0, $dst_width, $dst_height, $src['width'], $src['height']);

						}

					//--------------------------------------------------
					// Success

						return IMAGE_LOAD_SUCCESS;

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
								'background' => NULL,
								'scale' => true,
								'left' => NULL,
								'top' => NULL,
							);

						if (!is_array($config)) {
							$config = [];
						}

						$config = array_merge($defaults, $config);

					//--------------------------------------------------
					// Canvas width/height

						if ($config['width'] > 0 && $config['height'] > 0) {

							//--------------------------------------------------
							// Straight forward forced size

								$canvas_width = $config['width'];
								$canvas_height = $config['height'];

						} else if ($config['width'] > 0) {

							//--------------------------------------------------
							// Forced width, calculated height within min/max

								$canvas_width = $config['width'];
								$canvas_height = (round($canvas_width * ($this->image_height / $this->image_width)));

								if ($config['background'] !== NULL && !$config['scale'] && $canvas_height > $this->image_height) {
									$canvas_height = $this->image_height; // If making the image larger (with a background and not scaling the image), don't cause pointless top/bottom borders (only add left/right).
								}

								if ($config['height_min'] > 0 && $canvas_height < $config['height_min']) $canvas_height = $config['height_min']; // If below min height, force it larger (cropped width, or black bars high)
								if ($config['height_max'] > 0 && $canvas_height > $config['height_max']) $canvas_height = $config['height_max']; // If above max height, force is smaller (cropped height, or black bars wide)

						} else if ($config['height'] > 0) {

							//--------------------------------------------------
							// Forced height, calculated width within min/max

								$canvas_height = $config['height'];
								$canvas_width = (round($canvas_height * ($this->image_width / $this->image_height)));

								if ($config['background'] !== NULL && !$config['scale'] && $canvas_width > $this->image_width) {
									$canvas_width = $this->image_width; // If making the image larger (with a background and not scaling the image), don't cause pointless left/right borders (only add top/bottom).
								}

								if ($config['width_min'] > 0 && $canvas_width < $config['width_min']) $canvas_width = $config['width_min'];
								if ($config['width_max'] > 0 && $canvas_width > $config['width_max']) $canvas_width = $config['width_max'];

						} else {

							//--------------------------------------------------
							// Assume the same image size

								$canvas_width = $this->image_width;
								$canvas_height = $this->image_height;

							//--------------------------------------------------
							// Scale up to satisfy min width/height

								if ($config['width_min'] > 0 && $canvas_width < $config['width_min']) {
									$canvas_width = $config['width_min'];
									$canvas_height = (round($canvas_width * ($this->image_height / $this->image_width))); // Scale up to satisfy min width
								}

								if ($config['height_min'] > 0 && $canvas_height < $config['height_min']) {
									$canvas_height = $config['height_min'];
									$canvas_width = (round($canvas_height * ($this->image_width / $this->image_height))); // Scale up to satisfy min height
								}

							//--------------------------------------------------
							// Scale down to satisfy max width/height

								if ($config['width_max'] > 0 && $canvas_width > $config['width_max']) {

									$canvas_width = $config['width_max'];
									$canvas_height = (round($canvas_width * ($this->image_height / $this->image_width))); // Scale down to satisfy min width

									if ($config['height_min'] > 0 && $canvas_height < $config['height_min']) { // If this now drops the height below the min, just force it (to be cropped, or black bars)
										$canvas_height = $config['height_min'];
									}

								}

								if ($config['height_max'] > 0 && $canvas_height > $config['height_max']) {

									$canvas_height = $config['height_max'];
									$canvas_width = (round($canvas_height * ($this->image_width / $this->image_height))); // Scale down to satisfy min height

									if ($config['width_min'] > 0 && $canvas_width < $config['width_min']) { // If this now drops the width below the min, just force it (to be cropped, or black bars)
										$canvas_width = $config['width_min'];
									}

								}

						}

					//--------------------------------------------------
					// No change

						if ($canvas_width == $this->image_width && $canvas_height == $this->image_height) {
							return;
						}

					//--------------------------------------------------
					// Create new image canvas

						$new_image = $this->_create_canvas($canvas_width, $canvas_height);

					//--------------------------------------------------
					// Copy image onto new canvas (with background)

						if ($config['stretch']) {

							//--------------------------------------------------
							// Positions

								$dst_width = $canvas_width;
								$dst_height = $canvas_height;

								$dst_left = 0;
								$dst_top = 0;

						} else if ($config['background'] === NULL) { // aka 'crop'

							//--------------------------------------------------
							// Scale, possibly to max size

								$dst_width = $this->image_width;
								$dst_height = $this->image_height;

								if ($config['scale']) {

									$scaled_width = round($canvas_height * ($this->image_width / $this->image_height));
									$scaled_height = round($canvas_width * ($this->image_height / $this->image_width));

									if ($scaled_height >= $canvas_height) { // If scaled up height (matching canvas width) exceeds canvas, use it... test "360x35" to "180" wide (">=" instead of just ">")
										$dst_width = $canvas_width;
										$dst_height = $scaled_height;
									} else {
										$dst_width = $scaled_width;
										$dst_height = $canvas_height;
									}

								}

							//--------------------------------------------------
							// Position

								$dst_left = round($config['left'] !== NULL ? (0 - $config['left']) : (($canvas_width  / 2) - ($dst_width  / 2)));
								$dst_top  = round($config['top']  !== NULL ? (0 - $config['top'])  : (($canvas_height / 2) - ($dst_height / 2)));

						} else {

							//--------------------------------------------------
							// Set background

								imagefill($new_image, 0, 0, $this->_colour_allocate($new_image, $config['background']));

							//--------------------------------------------------
							// Size

								$dst_width = $this->image_width;
								$dst_height = $this->image_height;

								if ($config['scale']) {

									$scaled_width = round($canvas_height * ($this->image_width / $this->image_height));
									$scaled_height = round($canvas_width * ($this->image_height / $this->image_width));

									if ($scaled_height <= $canvas_height) { // If scaled up height (matching canvas width) is still within canvas, use it.
										$dst_width = $canvas_width;
										$dst_height = $scaled_height;
									} else {
										$dst_width = $scaled_width;
										$dst_height = $canvas_height;
									}

									if ($dst_width > $canvas_width) { // Push width down to fit within canvas
										$dst_width = $canvas_width;
										$dst_height = ceil($canvas_width * ($this->image_height / $this->image_width));
									}

									if ($dst_height > $canvas_height) { // Push height down to fit within canvas
										$dst_height = $canvas_height;
										$dst_width = ceil($canvas_height * ($this->image_width / $this->image_height));
									}

								}

							//--------------------------------------------------
							// Position

								$dst_left = round($config['left'] !== NULL ? (0 - $config['left']) : (($canvas_width  / 2) - ($dst_width  / 2)));
								$dst_top  = round($config['top']  !== NULL ? (0 - $config['top'])  : (($canvas_height / 2) - ($dst_height / 2)));

						}

						imagecopyresampled($new_image, $this->image_ref, $dst_left, $dst_top, 0, 0, $dst_width, $dst_height, $this->image_width, $this->image_height);

					//--------------------------------------------------
					// Store

						imagedestroy($this->image_ref);

						$this->image_ref = $new_image;
						$this->image_width = $canvas_width;
						$this->image_height = $canvas_height;
						$this->image_source = NULL;

				}
			}

		//--------------------------------------------------
		// Rotate

			public function sharpen($matrix = NULL) {

				if (!is_array($matrix)) {
					$matrix = [
							[-1, -1, -1],
							[-1, 16, -1],
							[-1, -1, -1],
						];
				}

				$divisor = array_sum(array_map('array_sum', $matrix));
				$offset = 0;
				imageconvolution($this->image_ref, $matrix, $divisor, $offset);

			}

			public function rotate($degrees, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Config

						$defaults = array(
								'background' => NULL,
							);

						if (!is_array($config)) {
							$config = [];
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
						$this->image_source = NULL;

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
					header('Content-Type: image/png');
					imagepng($this->image_ref, NULL, $compression);
				}
			}

			public function output_gif() {
				if ($this->image_ref) {
					header('Content-Type: image/gif');
					imagegif($this->image_ref);
				}
			}

			public function output_jpg($quality = 75) {
				if ($this->image_ref) {
					header('Content-Type: image/jpeg');
					imagejpeg($this->image_ref, NULL, $quality);
				}
			}

		//--------------------------------------------------
		// Save image

			public function save($file_path, $file_type = 'jpg', $quality = NULL, $preserve = false) {
				if ($preserve === true && $this->image_source !== NULL && $file_type == $this->image_type) {
					copy($this->image_source, $file_path);
					chmod($file_path, octdec(config::get('file.default_permission', 666)));
				} else if ($file_type == 'jpg') {
					$this->save_jpg($file_path, $quality);
				} else if ($file_type == 'png') {
					$this->save_png($file_path, $quality);
				} else if ($file_type == 'gif') {
					$this->save_gif($file_path);
				} else {
					exit_with_error('Unknown image type "' . $file_type . '"');
				}
			}

			public function save_png($file_path, $compression = NULL) {
				if ($this->image_ref) {
					if ($compression === NULL) {
						$compression = 6;
					}
					imagepng($this->image_ref, $file_path, $compression);
					chmod($file_path, octdec(config::get('file.default_permission', 666)));
				}
			}

			public function save_gif($file_path) {
				if ($this->image_ref) {
					imagegif($this->image_ref, $file_path);
					chmod($file_path, octdec(config::get('file.default_permission', 666)));
				}
			}

			public function save_jpg($file_path, $quality = NULL) {
				if ($this->image_ref) {
					if ($quality === NULL) {
						$quality = 75;
					}
					imagejpeg($this->image_ref, $file_path, $quality);
					chmod($file_path, octdec(config::get('file.default_permission', 666)));
				}
			}

			public function save_tiles($num_tiles_wide, $num_tiles_high, $file_name_prefix, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Config

						$defaults = array(
								'file_type' => 'jpg',
								'tile_width' => 256,
								'tile_height' => 256,
								'background' => NULL,
								'quality' => NULL,
							);

						if (!is_array($config)) {
							$config = [];
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

								$file_path = $file_name_prefix . $x . '-' . $y . '.' . $config['file_type'];

								if ($config['quality']) {
									if ($config['file_type'] == 'png') imagepng($tile, $file_path, $config['quality']);
									if ($config['file_type'] == 'jpg') imagejpeg($tile, $file_path, $config['quality']);
								} else {
									if ($config['file_type'] == 'png') imagepng($tile, $file_path);
									if ($config['file_type'] == 'gif') imagegif($tile, $file_path);
									if ($config['file_type'] == 'jpg') imagejpeg($tile, $file_path);
								}

								chmod($file_path, octdec(config::get('file.default_permission', 666)));

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
					$this->image_source = NULL;
				}
			}

		//--------------------------------------------------
		// Clone support

			public function __clone() {
				$new_image = $this->_create_canvas($this->image_width, $this->image_height);
				imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $this->image_width, $this->image_height, $this->image_width, $this->image_height);
				$this->image_ref = $new_image;
				$this->image_source = NULL;
			}

	}

?>