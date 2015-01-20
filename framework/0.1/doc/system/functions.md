# Functions

Just to add a few generic functions that either PHP should provide, or are just handy shortcuts.

Some of the helpers also provide their own functions as shortcuts, which you can view on their respective pages (e.g. the [url](../../doc/helpers/url.md) helper).

---

## Escaping values

Perhaps the most important functions on the website:

	html($text);
		// echo '<p>Hi ' . html($name) . '</p>';

	html_decode($html);
		// $text = html_decode('&lt;');

	html_tag($tag, $attributes);
		// echo html_tag('input', array('type' => 'text'));

	xml($text);
		// echo '<tag>' . xml($val) . '</tag>';

	head($text);
		// header('Name: ' . head($val));

	csv($text);
		// You will probably want to look at the table helper.

	safe_file_name($name);
		// $path = '/file/path/' . safe_file_name($name);

See the security section about [strings](../../doc/security/strings.md) to understand why these are useful.

---

## Request values

When you want to get a value from GET/POST/REQUEST, then simply use:

	request('name');
	request('name', 'GET');
	request('name', 'POST');

	http://www.example.com/?name=Craig
		echo '<p>Hi ' . html(request('name')) . '</p>';
		echo '<p>Hi Craig</p>';

This function will return NULL if the variable does not exist.

For other variables, you should probably use the [cookie](../../doc/helpers/cookie.md) and [session](../../doc/helpers/session.md) helpers.

---

# String functions

	prefix_match($prefix, $string);
		// prefix_match('/prefix/', '/prefix/match/');
		// true

	prefix_replace($prefix, $string);
		// prefix_replace('aaa_', 'aaa_bbb');
		// 'bbb'

	is_email($email);
		// email.check_domain = true;
		// is_email('noreply@invalid-domain.com');
		// false

	path_to_array($path)
		// path_to_array('/path/ignore/../to/./folder/');
		// [0] => "path"
		// [1] => "to"
		// [2] => "folder"

	cut_to_length($text, $length, $trim_to_char, $trim_suffix);
		// cut_to_length('This is some text', 11, ' ');
		// 'This is…'

	cut_to_words($text, $words, $trim);
		// cut_to_words('This is, some text', 2);
		// 'This is'

---

## Request handling

	request_folder_get(1);
		// http://www.example.com/admin/products/
		// 'products'

	request_folder_match(array('member', 'login'));
		// Returns true for /member/login/

	message_set('Thank you for...');
		// Message to be shown on next page, when the template uses $response->message_get_html();

	error_send('page-not-found');
		// Will also exit() for you, with the 'page-not-found' error.

	report_add('My message');
	report_add('My message', $type);
		// Record in database, and send an email if type is 'notice' (default) or 'error'.

	exit_with_error('Something has gone wrong');
	exit_with_error('Something has gone wrong', 'Hidden information');
		// Processing stops, an error page displayed, and the error logged (database + email).

	mime_set('text/plain');
		// Changes the mime type sent to the browser.

	redirect($url);
	redirect($url, 301);
		// Will also exit() for you, rather than just sending the Location header.

	system_redirect($old_url, $new_url);
		// Setup a redirect, used when calling error_send('page-not-found');

	http_download_file($path, $mime, $name, $mode);
		// http_download_file('/path/to/file.txt', 'text/plain');
		// exit();

	http_download_content($content, $mime, $name, $mode);
		// http_download_content('File content', 'text/plain', 'file.txt');
		// exit();

	http_cache_headers($expires, $last_modified, $etag);
		// http_cache_headers((60*30), filemtime($file_path));
		// Will exit() with 304 response if browser already has latest version.

	http_response_code(404);
		// See PHP documentation.

	https_only();
	https_available();
		// Returns true or false, depending on output.protocols.

	https_required();
		// Will redirect to https version of url if necessary, depending on output.protocols.

---

## File helpers

To delete a folder and its contents:

	rrmdir($path);

To work with versioned files (see [resource versioning](../../doc/setup/resources.md)).

	$file_url = timestamp_url('/a/img/logo.png');

Create a temporary folder in /private/tmp/xxx/:

	$folder_path = tmp_folder('xxx');

---

## Conversion functions

These are the main text based conversion functions, the results of which can be seen on the [examples](/examples/conversions/) page.

	human_to_ref($text);
	human_to_link($text);
	human_to_camel($text);

	ref_to_human($text);
	ref_to_link($text);
	ref_to_camel($text);

	link_to_human($text);
	link_to_ref($text);
	link_to_camel($text);

	camel_to_human($text);
	camel_to_ref($text);
	camel_to_link($text);

And the other conversion functions:

	file_size_to_human($size); // 261512 = 255KB
	file_size_to_bytes($size); // 255KB = 261120

	timestamp_to_human($input_seconds);
		// timestamp_to_human(235);
		// '3 minutes, and 55 seconds'

	format_currency($value, $currency_char, $decimal_places);
		// output.currency_char = '£'
		// format_currency(12);
		// '£12.00'

	format_url_path($url);
		// format_url_path('/a_b@/');
		// '/a-b/'

	format_postcode($postcode, $country);
		// format_postcode('A a1 11A A', 'UK');
		// 'AA11 1AA'
		// format_postcode('Invalid', 'UK');
		// NULL

---

# Debug

While not really covered on this page, there are a range of useful [debug functions](../../doc/setup/debug.md), for example:

	debug($var);

	echo debug_dump($var);

	debug_exit();

---

# Misc functions

	strip_slashes_deep($array);
	array_key_sort($array, 'name', SORT_STRING, SORT_DESC);
	is_assoc($array);
	mb_str_pad();
	random_key($length); // base 58 string, e.g. good for URL's
	random_bytes($length);
