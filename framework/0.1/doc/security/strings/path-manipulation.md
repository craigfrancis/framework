# Path Manipulation

If building a path for a file, be careful of:

- Invalid characters.
- Going up the directory tree (e.g. "./a/../../private").

One option is to use the [`safe_file_name`](../../../doc/system/functions.md)() function:

	$path = '/file/path/' . safe_file_name($name);

And be careful if saving an [uploaded file](../../../doc/security/files.md).
