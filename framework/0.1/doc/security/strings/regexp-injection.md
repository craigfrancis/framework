# RegExp Injection

Otherwise known as Regular Expressions, and typically run with the preg functions:

- [`preg_match`](https://php.net/preg_match)()
- [`preg_match_all`](https://php.net/preg_match_all)()
- [`preg_replace`](https://php.net/preg_replace)()
- [`preg_split`](https://php.net/preg_split)()

If you are using a user supplied variable (rare), then use the [`preg_quote`](https://php.net/preg_quote)() function.

So for example, using the multi-line regular expression to replace line prefix:

	$str = preg_replace('/^'. preg_quote($prefix, '/') . '/m', '', $str);
