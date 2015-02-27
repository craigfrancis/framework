
# Site config

Your initial config values should be setup in:

	/app/library/setup/config.php

Which is done via the `$config` array, for example:

	$config['name'] = 'Value';

This allows you to include this file via other systems, without providing the `config` object.

After this file has been processed, you should use the [config helper](../../doc/helpers/config.md) to set/get values.

---

## Encryption key

This should be considered private, and involves you typing in some random keys:

	define('ENCRYPTION_KEY', 'type-your-own-random-characters');

The intention of this constant is simply for encrypting information.

It's also used to ensure sessions are valid for this websites... so avoiding session fixation, and sessions being created on other websites hosted on the same box ([notes](https://www.sitepoint.com/notes-on-php-session-security/)).

---

## Servers

In your config.php file, you should set the 'SERVER' constant. This will allow your scripts to determine if they are running on a development server (stage), demo or live.

There are many ways to detect which server your running on, but my preferred method is to use the path:

	if (preg_match('/^\/(Library|Volumes)\//i', ROOT)) {

		define('SERVER', 'stage');

	} else if (prefix_match('/www/demo/', ROOT)) {

		define('SERVER', 'demo');

	} else {

		define('SERVER', 'live');

	}

Detection for **stage** is a simple regexp (as most OSX developers either use the /Library/ folder, or a case-sensitive volume). Then **demo** uses the [prefix_match](../../doc/system/functions.md)() function, and the default is to assume we are running on **live**.

This allows you to setup the [database connection](../../doc/system/database.md) details (probably different).

And if you are using the [email helper](../../doc/helpers/email.md), it might be worth setting the following on **stage**:

	$config['email.testing'] = 'admin@example.com';

It should also be noted that on **stage**, [development mode](../../doc/setup/debug.md) is enabled by default.

---

## Output

Hopefully most of these are self explanatory, and shown in the [development mode](../../doc/setup/debug.md) notes.

Some special cases though:

- **`output.protocols`**: Ideally set to array('https') on live (for only https connections).

- **`output.mime`**: Set to "application/xhtml+xml" on **stage**, to ensure good markup.

- **`output.framing`**: For basic [Framing protection](../../doc/security/framing.md).

- **`output.xss_reflected`**: For basic [XSS protection](../../doc/security/xss.md).

- **`output.pkp_*`**: For [PKP setup](../../doc/security/pkp.md).

- **`output.csp_*`**: For [CSP setup](../../doc/security/csp.md).

- **`output.js_*`**: For [JavaScript setup](../../doc/setup/resources.md).

- **`output.css_*`**: For [CSS setup](../../doc/setup/resources.md).

- **`output.timestamp_url`**: For [resource](../../doc/setup/resources.md) and [file](../../doc/helpers/file.md) URLs to include a timestamp.
