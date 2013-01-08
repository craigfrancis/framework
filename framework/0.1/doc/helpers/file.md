
# File helper

See notes about security on [file uploads](../../doc/security/files.md).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/file.php).

We do keep the original image files, but don't use them on the website... they are used by the `regenerate-images` gateway.

---

## Image support

		// Save images to folders:
		//  files/item_name/original/123.jpg
		//  files/item_name/100x100/123.jpg
		//  files/item_name/500x500/123.jpg
		//  files/item_name/120xX/123.jpg

	$file = new file('item_name');
	$file->image_save(123, '/path/to/file.jpg');
