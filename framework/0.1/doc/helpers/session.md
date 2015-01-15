
# Session helper

To gain access to the PHP session, there is a wrapper class (helper) which works very much the same as the [config helper](../../doc/helpers/config.md).

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/session.php).

---

## Set values

Variables can be `set` with:

	session::set('name', 'New value');

And if you want to remove a variable:

	session::delete('name');

---

## Get values

To get a value, simply call:

	session::get('name');

Which will either return the value if set, or NULL.

A default can also by provided:

	session::get('name', 'default');

Like the [config variables](../../doc/setup/config.md), the names can include a dot in the name. This is to help with grouping related things together.

So for example, to return all the session variables for `name`:

	session::set('name.first', 1);
	session::set('name.second', 1);
	session::set('name.third', 3);

	debug(session::get_all('name'));

---

## Session management

While typically done automatically when you `set/get()` a value, a session can be started with:

	session::start();

And if you need to close a session (typically PHP does this for you, but you may want to release the session lock early):

	session::close();

And if your done with the session, and want to delete all its values:

	session::destroy();

Or as a shortcut to destroy, and then start a new session:

	session::reset();

To regenerate the session ID (see security section below):

	session::regenerate();

---

## Security

As mentioned in the [session security issues](../../doc/security/sessions.md), there is a potential issue whereby the session ID of a legitimate user is somehow obtained by an attacker (e.g. a hacker).

While you can't protect against all issues (e.g. packet sniffing on a http connection, or malware on the victims computer), you may be able to limit the damage.

For example, if the session ID is obtained when the victim is a normal guest to the website, then you can re-generate the session ID when they login/logout:

	session::regenerate();

This is done automatically for you if you're using the [user helper](../../doc/helpers/user.md).

Likewise if the victim is already a privileged user, their session can timeout after a period of inactivity.

For extra security you could lock sessions to a particular IP address or User Agent string... however these are not recommended, as they do change. For the User Agent string, Internet Explorer will change it when switching to compatibility mode. And for the IP address, if the victim is behind a proxy, they may either share the same IP as the attacker (e.g. a shared internet connection at a cafe), or the proxy may use multiple IP addresses (e.g. AOL customers).

Otherwise, the [security issues](../../doc/security/sessions.md) mentioned are covered by the session helper by default... for example, it will set the recommended cookie ini defaults, and check the session was created by your website.
