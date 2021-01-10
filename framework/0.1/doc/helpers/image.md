
# Image helper

To see some how the image helper can be used, look at the [examples](/examples/image/).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/main/framework/0.1/library/class/image.php).

---

## Load image

	$image = new image('1.jpg');

	$image->output_jpg();
	$image->output_jpg(90);

	$image->output_png();

	$image->save_jpg('/path/to/file.jpg');
	$image->save_jpg('/path/to/file.jpg', 90);

---

##  Combine images

	$image_sub = new image('2.gif');

	$image = new image('1.jpg');

	$image->image_add($image_sub, [
			'width' => 100,
			'height' => 200,
		]);

	$image->output_gif();

	exit();

---

##  Resize image

	$image = new image('1.jpg'); // Presuming the size is 100x200

	$config = [ // Scales this image to 500x1000
			'width' => 500,
		];

	$config = [ // Scales to the biggest dimension (in this case the width), then crops the rest of the other dimension.
			'width' => 500,
			'height' => 100,
		];

	$config = [ // Scaled to 200x400 (to satisfy min width), and would have cropped the image if the max height was 300.
			'width_min' => 200,
			'width_max' => 400,
			'height_min' => 100,
			'height_max' => 500,
		];

	$config = [ // Scaled to 200x300, but the picture changes to 150x300, with a black border left/right (no cropping).
			'width_min' => 200,
			'width_max' => 400,
			'height_min' => 100,
			'height_max' => 300,
			'background' => '000000',
		];

	$config = [ // Scaled to 200x300, but the picture stays at 100x200, with a black border.
			'width_min' => 200,
			'width_max' => 400,
			'height_min' => 100,
			'height_max' => 300,
			'background' => '000000',
			'scale' => false,
		];

	$image->resize($config);
	$image->save_jpg('/path/to/file.jpg');

---

##  Two images, Resize, and Alpha

	$bg = new image('background.png');
	$bg->alpha_blend_set(true);

	$fg = new image('example.png');
	$fg->resize(['height' => $bg->height_get()]);
	$fg->sharpen();

	$bg->image_add($fg, [
			'left' => ($bg->width_get() - $fg->width_get()),
		]);

	$bg->output_png();
