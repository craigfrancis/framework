
# Login and passwords

Notes about hashing passwords... slow, one way.

There is a "password" helper which is a basic wrapper for `password_hash/password_verify/password_needs_rehash`, while providing backwards computability support for the old md5 style hashing.

There are 2 password reset methods, the email version does not expose a list of valid accounts... also does rate limiting.

Note about session fixation... and possibly CSRF (it uses the form helper).
