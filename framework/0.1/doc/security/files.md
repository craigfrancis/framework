
# File uploads

When saving files, re-name... we don't want the user to upload a "evil.php" file, which might be executed by the server.

Likewise, you don't want multiple users uploading "Photo.jpg".

Ideally you would create a new record in the database for each upload (recording the file name, when it was uploaded, by who, etc), and use the record ID for the file name.

And images should be re-saved at the required size... we don't want everyone to download a 10MB+ image (straight from a digital camera), for it to contain [JavaScript code](http://adblockplus.org/blog/the-hazards-of-mime-sniffing), or be engineered to cause a buffer overflow.

See notes on the [file helper](../../doc/helpers/file.md).
