
# Header Injection

Just allowing a newline character can allow an attacker to send their own headers:

	$unsafe = 'https://www.example.com' . "\n" . 'Set-Cookie: aaa=bbb;';

	header('Location: ' . $unsafe);
	header('Location: ' . head($unsafe)); // Better

	redirect($location);

This has been [fixed](https://php.net/releases/5_1_2.php) in PHP 5.1.2, but can still be a problem elsewhere (e.g. if setting headers with the PHP [mail](http://php.net/mail) function, rather than using the [email helper](../../../doc/helpers/email.md)).
