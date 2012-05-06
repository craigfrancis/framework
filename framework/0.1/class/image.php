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

***************************************************/

	define('IMAGE_LOAD_SUCCESS', 0);
	define('IMAGE_LOAD_ERR_GONE', 1);
	define('IMAGE_LOAD_ERR_SIZE', 2);
	define('IMAGE_LOAD_ERR_READ', 3);

	define('IMAGE_ADD_IMAGE_TILE_TOP_LEFT', 0);
	define('IMAGE_ADD_IMAGE_TILE_CENTER', 1);

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

			public function create_image($width, $height, $bg_red = 0, $bg_green = 0, $bg_blue = 0) {

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

					if ($bg_red !== NULL && $bg_green !== NULL && $bg_blue !== NULL) {
						$background = imagecolorallocate($this->image_ref, $bg_red, $bg_green, $bg_blue);
						imagefill($this->image_ref, 0, 0, $background);
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

			public function image_add($image, $left = 0, $top = 0, $width = NULL, $height = NULL) {
				if ($this->image_ref) {

					$return = $this->_load_image($image);
					if (!is_array($return)) {
						return $return;
					}

					if ($width === NULL) $width = $return['width'];
					if ($height === NULL) $height = $return['height'];

					imagecopyresampled($this->image_ref, $return['ref'], $left, $top, 0, 0, $width, $height, $return['width'], $return['height']);

					return IMAGE_LOAD_SUCCESS;

				}
			}

			public function image_add_size_and_cut_to_box($image, $box_left, $box_top, $box_width, $box_height, $bg_red = 0, $bg_green = 0, $bg_blue = 0, $config = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Image info

						$return = $this->_load_image($image);
						if (!is_array($return)) {
							return $return;
						}

					//--------------------------------------------------
					// Calculate how to crop or scale the image

						$source_width = $return['width'];
						$source_height = $return['height'];

						$source_left = 0;
						$source_top = 0;

						if ($return['width'] > $box_width && $return['height'] > $box_height) { // Can only crop when image is big enough

							//--------------------------------------------------
							// The new size

								if (($return['width'] / $box_width) > ($return['height'] / $box_height)) {

									$new_height = $box_height; // Height is the best dimension for cropping to
									$new_width = ceil($new_height * ($return['width'] / $return['height']));

								} else {

									$new_width = $box_width; // Width is the best dimension for cropping to
									$new_height = ceil($new_width * ($return['height'] / $return['width']));

								}

							//--------------------------------------------------
							// Return back to the source scale

								if ($new_width > $box_width) {

									$source_width = round(($box_width / $box_height) * $source_height);

								} else {

									$source_height = round(($box_height / $box_width) * $source_width);

								}

							//--------------------------------------------------
							// Start point

								$source_left = round(($return['width'] - $source_width) / 2);

								if (isset($config['position_top']) && $config['position_top'] === true) {
									$source_top = 0;
								} else {
									$source_top = round(($return['height'] - $source_height) / 2);
								}

						} else {

							//--------------------------------------------------
							// Add a place holder background

								if ($bg_red !== NULL && $bg_green !== NULL && $bg_blue !== NULL) {
									$background = imagecolorallocate($this->image_ref, $bg_red, $bg_green, $bg_blue);
									imagefilledrectangle($this->image_ref, $box_left, $box_top, ($box_left + $box_width - 1), ($box_top + $box_height - 1), $background);
								}

							//--------------------------------------------------
							// If a dimension is too big

								if ($box_width > $source_width) {
									$box_left += round(($box_width - $source_width) / 2);
									$box_width = $source_width;
								}

								if ($box_height > $source_height) {
									$box_top += round(($box_height - $source_height) / 2);
									$box_height = $source_height;
								}

							//--------------------------------------------------
							// If a dimension is too small

								if ($box_width < $source_width) {
									$source_left = round(($source_width - $box_width) / 2);
									$source_width = $box_width;
								}

								if ($box_height < $source_height) {

									if (isset($config['position_top']) && $config['position_top'] === true) {
										$source_top = 0;
									} else {
										$source_top = round(($source_height - $box_height) / 2);
									}

									$source_height = $box_height;

								}

						}

						imagecopyresampled($this->image_ref, $return['ref'], $box_left, $box_top, $source_left, $source_top, $box_width, $box_height, $source_width, $source_height);

					//--------------------------------------------------
					// Return

						return IMAGE_LOAD_SUCCESS;

				}
			}

			public function image_tile_add($image, $style = IMAGE_ADD_IMAGE_TILE_CENTER) {
				$this->image_tile_add_to_area($image, $this->image_width, $this->image_height, 0, 0, $style);
			}

			public function image_tile_add_to_area($image, $area_width, $area_height, $area_left, $area_top, $style = IMAGE_ADD_IMAGE_TILE_CENTER) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Load

						$tile = $this->_load_image($image);
						if (!is_array($tile)) {
							return $tile;
						}

					//--------------------------------------------------
					// Tile repeat count

						$repeat_l = ceil($area_width / $tile['width']);
						$repeat_t = ceil($area_height / $tile['height']);

					//--------------------------------------------------
					// Offset

						if ($style == IMAGE_ADD_IMAGE_TILE_TOP_LEFT) {
							$offset_left = 0;
							$offset_top = 0;
						} else {
							$offset_left = (0 - round((($repeat_l * $tile['width']) - $area_width) / 2));
							$offset_top = (0 - round((($repeat_t * $tile['height']) - $area_height) / 2));
						}

					//--------------------------------------------------
					// Apply tiles

						for ($l = 0; $l < $repeat_l; $l++) {
							for ($t = 0; $t < $repeat_t; $t++) {

								//--------------------------------------------------
								// Grid position

									$dest_left = ($tile['width'] * $l);
									$dest_top = ($tile['height'] * $t);

								//--------------------------------------------------
								// Which part of the tile to copy - cuts off the
								// left and top sides

									$src_left = ($l == 0 ? (0 - $offset_left) : 0);
									$src_top = ($t == 0 ? (0 - $offset_top) : 0);

								//--------------------------------------------------
								// Size of the tile area to copy - cuts off the
								// right and bottom sides

									//--------------------------------------------------
									// Width

										$src_width = ($area_width - $dest_left) - $offset_left; // Double negative, add offset_left
										if ($src_width > $tile['width']) {
											$src_width = $tile['width']; // Not too much
										}

										$src_width -= $src_left; // If left-side is cut, don't address too much

									//--------------------------------------------------
									// Height

										$src_height = ($area_height - $dest_top) - $offset_top; // Double negative, add offset_top
										if ($src_height > $tile['height']) {
											$src_height = $tile['height']; // Not too much
										}

										$src_height -= $src_top; // If top-side is cut, don't address too much

								//--------------------------------------------------
								// Apply the skew to the grid position - where
								// the 'offset' and 'src' are the same on the
								// left column or top row... otherwise its only
								// the 'offset' which is applied from these two.

									$dest_left += $area_left + ($offset_left + $src_left);
									$dest_top += $area_top + ($offset_top + $src_top);

								//--------------------------------------------------
								// Copy the tile onto the image, keeping 1:1 ratio

									imagecopyresampled($this->image_ref, $tile['ref'], $dest_left, $dest_top, $src_left, $src_top, $src_width, $src_height, $src_width, $src_height);

							}
						}

					//--------------------------------------------------
					// Success

						return IMAGE_LOAD_SUCCESS;

				}
			}

		//--------------------------------------------------
		// Change image size

			public function center_to_box($box_width, $box_height, $bg_red = 0, $bg_green = 0, $bg_blue = 0, $scale = true) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Create the canvas

						$new_image = $this->_create_canvas($box_width, $box_height);

						if ($bg_red !== NULL && $bg_green !== NULL && $bg_blue !== NULL) {
							$background = imagecolorallocate($new_image, $bg_red, $bg_green, $bg_blue);
							imagefill($new_image, 0, 0, $background);
						}

					//--------------------------------------------------
					// Configure the centre area

						$width = $this->image_width;
						$height = $this->image_height;

						if ($scale) {

							if ($width > $box_width) {
								$height = ceil($box_width * ($height / $width));
								$width = $box_width;
							}

							if ($height > $box_height) {
								$width = ceil($box_height * ($width / $height));
								$height = $box_height;
							}

						}

						$left = round(($box_width / 2) - ($width / 2));
						$top = round(($box_height / 2) - ($height / 2));

						imagecopyresampled($new_image, $this->image_ref, $left, $top, 0, 0, $width, $height, $this->image_width, $this->image_height);

					//--------------------------------------------------
					// Kill the old image

						imagedestroy($this->image_ref);

					//--------------------------------------------------
					// Replace the image

						$this->image_ref = $new_image;
						$this->image_width = $box_width;
						$this->image_height = $box_height;

				}
			}

			public function cut_to_box($box_width, $box_height, $bg_red = 0, $bg_green = 0, $bg_blue = 0) {
				$this->center_to_box($box_width, $box_height, $bg_red, $bg_green, $bg_blue, false);
			}

			public function size_and_cut_to_box($box_width, $box_height, $bg_red = 0, $bg_green = 0, $bg_blue = 0) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Create the canvas

						$new_image = $this->_create_canvas($box_width, $box_height);

						if ($bg_red !== NULL && $bg_green !== NULL && $bg_blue !== NULL) {
							$background = imagecolorallocate($new_image, $bg_red, $bg_green, $bg_blue);
							imagefill($new_image, 0, 0, $background);
						}

					//--------------------------------------------------
					// Configure the centre area

						$new_width = $this->image_width;
						$new_height = $this->image_height;

						if ($this->image_width > $box_width && $this->image_height > $box_height) { // Can only crop when image is big enough

							if (($this->image_width / $box_width) > ($this->image_height / $box_height)) {

								$new_height = $box_height; // Height is the best dimension for cropping to
								$new_width = ceil($new_height * ($this->image_width / $this->image_height));

							} else {

								$new_width = $box_width; // Width is the best dimension for cropping to
								$new_height = ceil($new_width * ($this->image_height / $this->image_width));

							}

						}

						$left = round(($box_width / 2) - ($new_width / 2));
						$top = round(($box_height / 2) - ($new_height / 2));

						imagecopyresampled($new_image, $this->image_ref, $left, $top, 0, 0, $new_width, $new_height, $this->image_width, $this->image_height);

					//--------------------------------------------------
					// Kill the old image

						imagedestroy($this->image_ref);

					//--------------------------------------------------
					// Replace the image

						$this->image_ref = $new_image;
						$this->image_width = $box_width;
						$this->image_height = $box_height;

				}
			}

			public function max_size($max_width, $max_height) {
				if ($this->image_ref && ($this->image_width > $max_width || $this->image_height > $max_height)) {

					//--------------------------------------------------
					// Dimensions

						$new_width = $this->image_width;
						$new_height = $this->image_height;

						if ($new_width > $max_width) {
							$new_height = (round($max_width * ($new_height / $new_width)));
							$new_width = $max_width;
						}

						if ($new_height > $max_height) {
							$new_width = (round($max_height * ($new_width / $new_height)));
							$new_height = $max_height;
						}

					//--------------------------------------------------
					// Size

						$new_image = $this->_create_canvas($new_width, $new_height);

						imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $new_width, $new_height, $this->image_width, $this->image_height);

					//--------------------------------------------------
					// Kill the old image

						imagedestroy($this->image_ref);

					//--------------------------------------------------
					// Store

						$this->image_ref = $new_image;
						$this->image_width = $new_width;
						$this->image_height = $new_height;

				}
			}

			public function scale_width($width) {
				if ($this->image_ref) {
					$height = (round($width * ($this->image_height / $this->image_width)));
					$this->force_size($width, $height);
				}
			}

			public function scale_height($height) {
				if ($this->image_ref) {
					$width = (round($height * ($this->image_width / $this->image_height)));
					$this->force_size($width, $height);
				}
			}

			public function force_size($width, $height) {
				if ($this->image_ref) {
					$new_image = $this->_create_canvas($width, $height);
					imagecopyresampled($new_image, $this->image_ref, 0, 0, 0, 0, $width, $height, $this->image_width, $this->image_height);
					imagedestroy($this->image_ref);
					$this->image_ref = $new_image;
					$this->image_width = $width;
					$this->image_height = $height;
				}
			}

			public function crop_size($width, $height, $left = 0, $top = 0) {
				if ($this->image_ref) {
					$new_image = $this->_create_canvas($width, $height);
					imagecopyresampled($new_image, $this->image_ref, 0, 0, $left, $top, $width, $height, $width, $height);
					imagedestroy($this->image_ref);
					$this->image_ref = $new_image;
					$this->image_width = $width;
					$this->image_height = $height;
				}
			}

		//--------------------------------------------------
		// Rotate image

			public function rotate($degrees) {
				if ($this->image_ref) {

					// imagerotate($this->image_ref, $degrees, 0);

					if ($degrees == 90 || $degrees == 270) {

						$new_width = $this->image_height;
						$new_height = $this->image_width;

					} else if ($degrees == 180) {

						$new_width = $this->image_width;
						$new_height = $this->image_height;

					} else {

						return;

					}

					$new_image = $this->_create_canvas($new_width, $new_height);

					for ($i = 0; $i < $this->image_width; $i++) {
						for ($j = 0; $j < $this->image_height; $j++) {
							$src_colour = imagecolorat($this->image_ref, $i, $j);
							switch ($degrees) {
								case 90:  imagesetpixel($new_image, (($this->image_height - 1) - $j), $i, $src_colour ); break;
								case 180: imagesetpixel($new_image, ($this->image_width - $i), (($this->image_height - 1) - $j), $src_colour ); break;
								case 270: imagesetpixel($new_image, $j, ($this->image_width - $i), $src_colour ); break;
							}
						}
					}

					imagedestroy($this->image_ref);

					$this->image_ref = $new_image;
					$this->image_width = $new_width;
					$this->image_height = $new_height;

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

			public function output_png($compression = 0) {
				if ($this->image_ref) {
					header('Content-type: image/png');
					imagepng($this->image_ref, NULL, $compression);
				}
			}

			public function output_gif() {
				if ($this->image_ref) {
					header('Content-type: image/gif');
					imagegif($this->image_ref);
				}
			}

			public function output_jpg($quality = 80) {
				if ($this->image_ref) {
					header('Content-type: image/jpeg');
					imagejpeg($this->image_ref, NULL, $quality);
				}
			}

		//--------------------------------------------------
		// Save image

			public function save_png($file_path, $compression = 0) {
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

			public function save_jpg($file_path, $quality = 80) {
				if ($this->image_ref) {
					imagejpeg($this->image_ref, $file_path, $quality);
					@chmod($file_path, 0666);
				}
			}

			public function save_tiles($num_tiles_wide, $num_tiles_high, $file_name_prefix, $file_name_ext = 'jpg', $tile_width = 256, $tile_height = 256, $bg_red = 0, $bg_green = 0, $bg_blue = 0, $quality = NULL) {
				if ($this->image_ref) {

					//--------------------------------------------------
					// Create the canvas

						//--------------------------------------------------
						// Start

							$canvas_width = ($num_tiles_wide * $tile_width);
							$canvas_height = ($num_tiles_high * $tile_height);

							$canvas = $this->_create_canvas($canvas_width, $canvas_height);

						//--------------------------------------------------
						// Background

							if ($bg_red !== NULL && $bg_green !== NULL && $bg_blue !== NULL) {
								$background = imagecolorallocate($canvas, $bg_red, $bg_green, $bg_blue);
								imagefill($canvas, 0, 0, $background);
							}

						//--------------------------------------------------
						// Add image

							if ($this->image_width > $this->image_height) {
								$canvas_zoom_max_level = ceil(log(ceil($this->image_width / $tile_width), 2));
							} else {
								$canvas_zoom_max_level = ceil(log(ceil($this->image_height / $tile_height), 2));
							}

							$canvas_zoom_max_size_width = (intval(pow(2, $canvas_zoom_max_level)) * $tile_width);
							$canvas_zoom_max_size_height = (intval(pow(2, $canvas_zoom_max_level)) * $tile_height);

							$width = (($this->image_width / $canvas_zoom_max_size_width) * $canvas_width);
							$height = (($this->image_height / $canvas_zoom_max_size_height) * $canvas_height);

							$offset_left = (($canvas_width / 2) - ($width / 2));
							$offset_top = (($canvas_height / 2) - ($height / 2));

							imagecopyresampled($canvas, $this->image_ref, $offset_left, $offset_top, 0, 0, $width, $height, $this->image_width, $this->image_height);

					//--------------------------------------------------
					// Save the image parts

						for ($x = 0; $x < $num_tiles_wide; $x++) {
							for ($y = 0; $y < $num_tiles_high; $y++) {

								$tile = $this->_create_canvas($tile_width, $tile_height);

								imagecopyresampled($tile, $canvas, 0, 0, ($x * $tile_width), ($y * $tile_width), $tile_width, $tile_height, $tile_width, $tile_height);

								$file_path = $file_name_prefix . $x . '-' . $y . '.' . $file_name_ext;

								if ($quality) {
									if ($file_name_ext == 'png') imagepng($tile, $file_path, $quality);
									if ($file_name_ext == 'jpg') imagejpeg($tile, $file_path, $quality);
								} else {
									if ($file_name_ext == 'png') imagepng($tile, $file_path);
									if ($file_name_ext == 'gif') imagegif($tile, $file_path);
									if ($file_name_ext == 'jpg') imagejpeg($tile, $file_path);
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

//--------------------------------------------------
// Copyright (c) 2006, Craig Francis All rights
// reserved.
//
// Redistribution and use in source and binary forms,
// with or without modification, are permitted provided
// that the following conditions are met:
//
//  * Redistributions of source code must retain the
//    above copyright notice, this list of
//    conditions and the following disclaimer.
//  * Redistributions in binary form must reproduce
//    the above copyright notice, this list of
//    conditions and the following disclaimer in the
//    documentation and/or other materials provided
//    with the distribution.
//  * Neither the name of the author nor the names
//    of its contributors may be used to endorse or
//    promote products derived from this software
//    without specific prior written permission.
//
// This software is provided by the copyright holders
// and contributors "as is" and any express or implied
// warranties, including, but not limited to, the
// implied warranties of merchantability and fitness
// for a particular purpose are disclaimed. In no event
// shall the copyright owner or contributors be liable
// for any direct, indirect, incidental, special,
// exemplary, or consequential damages (including, but
// not limited to, procurement of substitute goods or
// services; loss of use, data, or profits; or business
// interruption) however caused and on any theory of
// liability, whether in contract, strict liability, or
// tort (including negligence or otherwise) arising in
// any way out of the use of this software, even if
// advised of the possibility of such damage.
//--------------------------------------------------

?>