
# Robots.txt

To help search engines determine how the content on your website should be indexed, you can provide a `robots.txt` file.

In most cases the default `robots.txt` will work fine for you - where search engines are told to index the content on Live, but not in other environments (e.g. Demo).

This is not a security feature, and should not be used to [protect private information](../../doc/security/logins.md). For that you might want to look at the [user helper](../../doc/helpers/user.md), or if you have a Demo site, just simply add a [HTTP Auth](http://httpd.apache.org/docs/2.2/howto/auth.html#gettingitworking).

To add your own `robots.txt`, simply create the file:

	/app/view/robots.txt
