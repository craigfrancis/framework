
# Login and passwords

Notes about hashing passwords... slow, one way.

There is a [password helper](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/password.php) which is a basic wrapper for:

- [`password_hash`](https://php.net/password_hash)()
- [`password_verify`](https://php.net/password_verify)()
- [`password_needs_rehash`](https://php.net/password_needs_rehash)()

While providing backwards computability support for the old md5 style hashing.

---

There are 2 password reset methods, the email version does not expose a list of valid accounts... also does rate limiting.

Note about session fixation... and possibly CSRF (it uses the form helper).
