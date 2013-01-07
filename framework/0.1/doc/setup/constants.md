
# Constants

You are free to create your own, but these can be used:

	ROOT            = /
	APP_ROOT        = /app/
	VIEW_ROOT       = /app/view/
	PUBLIC_ROOT     = /app/public/
	CONTROLLER_ROOT = /app/controller/

---

## Required constants

The framework will require you to set the `ENCRYPTION_KEY` and `SERVER` constants:

	define('ENCRYPTION_KEY', 'type-your-own-random-characters');
	define('SERVER', 'live');

See the [config setup](/doc/setup/config/) for more details.

---

## CLI mode

To see if the current script is running in via the command line:

	if (defined('CLI_MODE')) {
	}

---

## Initialise framework only

If you want to include the [bootstrap file](/doc/setup/bootstrap/) to get the framework functionality, but without it actually processing a request, try setting:

	define('FRAMEWORK_INIT_ONLY', true);

For example:

	<?php

		define('ROOT', dirname(__FILE__));
		define('ENCRYPTION_KEY', XXX);
		define('SERVER', 'stage');
		define('FRAMEWORK_INIT_ONLY', true);

		require_once(ROOT . '/path/to/bootstrap.php');

	?>
