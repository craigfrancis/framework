
# Constants

You are free to create your own, but these can be used:

	ROOT            = /
	APP_ROOT        = /app/
	VIEW_ROOT       = /app/view/
	PUBLIC_ROOT     = /app/public/
	CONTROLLER_ROOT = /app/controller/

---

## Required constants

The framework will require you to set the `SERVER` constant:

	define('SERVER', 'live');

See the [config setup](../../doc/setup/config.md) for more details.

---

## Request mode

To see if the current script is running in via the [command line](../../doc/setup/cli.md):

	if (REQUEST_MODE == 'cli') {
	}

Or is a normal HTTP request (the default):

	if (REQUEST_MODE == 'http') {
	}

---

## Initialise framework only

If you want to include the [bootstrap file](../../doc/setup/bootstrap.md) to get the framework functionality, but without it actually processing a request, you can set:

	define('FRAMEWORK_INIT_ONLY', true);

For example:

	<?php

		define('ROOT', dirname(__FILE__));
		define('SERVER', 'stage');
		define('FRAMEWORK_INIT_ONLY', true);

		require_once(ROOT . '/path/to/bootstrap.php');

	?>
