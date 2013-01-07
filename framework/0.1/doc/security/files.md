
# File uploads

When saving files, re-name... we don't want the user to upload a "evil.php" file, which can be loaded (and executed by the server).

Uploaded images should be re-saved with GD at the required size... we don't want them to contain [JavaScript code](http://adblockplus.org/blog/the-hazards-of-mime-sniffing), or be engineered to cause a buffer overflow, or simply that the end user uploaded a raw image from their digital camera (at 10MB+).

See notes on the [file helper](../../doc/helpers/file.md).
