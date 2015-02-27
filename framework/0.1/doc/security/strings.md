# Strings overview

The great thing about **strings** is that they are very simple (just a set of characters). This makes them a fantastic way to transfer data from one system to another.

But depending on what we're doing with these **strings**, they will probably need to be processed (aka escaped) in some way. So that whatever receives them, won't misunderstand what they mean.

As an example, take the apostrophe (or the single quote character) in "O'briain". If that was sent to a database via SQL, it could be seen as the special character which says that the name has finished:

	UPDATE
		users
	SET
		name = 'O'Brian'
	WHERE
		id = 1;

As you can see, the SQL has a problem and needs to be fixed. Which can be done by escaping the value like so:

	name = 'O\'Brian'

And it's this handling of **strings** which EVERY programmer needs to understand, otherwise you have a range of security issues:

- [SQL injection](../../doc/security/strings/sql-injection.md)
- [HTML injection](../../doc/security/strings/html-injection.md)
- [URL manipulation](../../doc/security/strings/url-manipulation.md)
- [Header injection](../../doc/security/strings/header-injection.md)
- [CLI injection](../../doc/security/strings/cli-injection.md)
- [RegExp injection](../../doc/security/strings/regexp-injection.md)
- [Path manipulation](../../doc/security/strings/path-manipulation.md)

---

## Naming conventions

I personally believe that with an understanding of the above issues, and how you should escape variables, a simple naming convention will resolve most issues.

For example, how about a search term that needs to be passed to the download page via a link, we could:

	$search = (isset($_REQUEST['q']) ? $_REQUEST['q'] : '');

	$download_url = '/path/?output=download&q=' . urlencode($search);

	$download_html = '<a href="' . htmlentities($download_url) . '">Download</a>';

	echo $download_html;

Now this example does not use any [helpers](../../doc/helpers.md), to show how it can work with any PHP code.

So to help track how the values are being escaped, we can use these suffixes for variables and function names:

	$var = 'value';
	$var_url = urlencode($var);
	$var_html = htmlentities($var); // or $var_url if it's a link
	$var_sql = $db->escape($var);

You will notice this is a form of [Hungarian Notation](https://en.wikipedia.org/wiki/Hungarian_notation), but in a way that I believe to be very useful.
