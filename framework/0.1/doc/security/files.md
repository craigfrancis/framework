
# File uploads

When saving files, re-name...

1. We don't want the user to upload a "evil.php" file, which might be executed by the server (although the web server config should not execute php scripts in the /files/ folder).

2. We don't want multiple users uploading "Photo.jpg".

And images should be re-saved as well...

1. They might be specially crafted to exploit a security vulnerability in your visitors browser (e.g. a buffer overflow, whereas your server should be kept up to date).

2. They might simply contain [JavaScript code](http://adblockplus.org/blog/the-hazards-of-mime-sniffing), and the browser may execute it.

3. They are probably the wrong size, either in px, or as a 10MB+ image - usually straight from a digital camera.

---

Ideally you would create a new record in the database for each upload (recording the file name, when it was uploaded, by who, etc), and use the record ID for the file name.

Then use the [file helper](../../doc/helpers/file.md) to safe the file or image, via `file_save()` or `image_save()` respectively.
