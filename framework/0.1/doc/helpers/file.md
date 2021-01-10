
# File helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/main/framework/0.1/library/class/file.php).

The helper is a simple wrapper that allows you to save to the /files/ directory.

	$file = new file('item_name');

	$file->file_save(123, '/path/to/source');

	if ($file->file_exists(123)) {
		echo $file->file_url_get(123);
		echo $file->file_path_get(123);
	}

---

## Image support

To save multiple versions of an image, such as:

	/files/item_name/original/123.jpg
	/files/item_name/100x100/123.jpg
	/files/item_name/500x500/123.jpg
	/files/item_name/120xX/123.jpg

Create the relevant folders as above, then use the `image_save()` method:

	$file = new file('item_name');
	$file->image_save(123, '/path/to/source');

Then once saved you can:

	$size = '100x100';

	if ($file->image_exists(123, $size)) {

		echo $file->image_url_get(123, $size);
		echo $file->image_path_get(123, $size);
		echo $file->image_html_get(123, $size);

		$file->image_delete(123);

	}

The 'original' image is there as a backup, allowing the `regenerate-images` gateway to re-create thumbnails... you should **not** use it on the website, as the uploaded file might not be an image ([potential security issue](../../doc/security/files.md)), or may have an inappropriate size.

The different sizes take the form of:

	100x300
		Always saved as 100px wide, 300px high, and is cropped.

	100xX
		Always saved as 100px wide, height scales with aspect ratio.

	100xX-200
		Always saved as 100px wide, height cropped to be between 0 and 200px.

	100x0-200_000000
		Always saved as 100px wide, height set between 0 and 200px, with a black background.

See the `resize` method on the [image helper](../../doc/helpers/image.md) for more details.

---

## More advanced saving

To save the file with an extension, you can either use the `file_ext` config (next section), or do it inline:

	$file->file_save(123, '/path/to/source', 'bin');

	if ($file->file_exists(123, 'bin')) {
		echo $file->file_url_get(123, 'bin');
		echo $file->file_path_get(123, 'bin');
	}

If you just have the file contents (e.g. not an uploaded file), then you can use:

	$file->file_save_contents(123, 'File contents');

Or if the file should be an image ([potential security issue](../../doc/security/files.md)), and you're not using the image support above, then you can use GD to ensure the saved file is an image:

	$config['file.item_name.file_ext'] = 'jpg';

	$file->file_save_image(123, '/path/to/source');

---

## Configuration

Usually the configuration is set in the [config.php](../../doc/setup/config.md) file, for example:

	$config['file.item_name.file_private'] = true;

Alternatively the `file` helper can be initialised with a config array, rather than the profile name:

	$file = new file(array(
			'profile' => 'item_name',
			'file_private' => true,
		));

The full list of config options include:

	file_private
		Set to true if the file should exist in /private/files/

	file_root
		Usually not set, defaults to /files/ or /private/files/ folders.

	file_url
		Usually not set, defaults to /a/files/ for public files (not used for private files).

	file_ext
		Usually not set, but can set a default extension for files (e.g. 'bin' or 'jpg').

	file_folder_division
		For large sets of files, set to '1000' to use sub-folders, e.g. /files/8000/8192

	file_missing_url
		If the file does not exist, file_url_get() will return this as a default.

Then when using the image functions (above), these additional options can be used:

	image_type
		Saves all the images as 'jpg' (default), 'gif', or 'png'.

	image_quality
		Instructs GD on the image quality - jpg default is 75, and 6 for png.

	image_preserve_unsafe
		If no re-sizing is required from origional image, keep that file (see warning below).

	image_url_prefix
		Adds a prefix (e.g. domain name) to the url, useful when using image_html_get()

	image_placeholder_url
		If the file does not exist, image_url_get() will suffix this with the size and extension.

	image_missing_url
		If the file does not exist, image_url_get() will return this as a default.

	image_background
		Background colour (e.g. '000000') if images should not be cropped.

The `image_preserve_unsafe` is "unsafe" as [file uploads](../../doc/security/files.md) cannot be trusted.
