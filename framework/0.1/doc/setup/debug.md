
# Debug

This really only comes into play when your in development mode (see below).

However there are some useful functions which are always present:

	debug($var);

	echo debug_dump($var);

	debug_exit();

---

# Development mode

You can enable this by either setting the `SERVER` constant to 'stage', or the via the [config](../../doc/setup/config.md):

	define('SERVER', 'stage');

	$config['debug.level'] = 4;
	$config['debug.show'] = true;

When enabled all HTML pages should get a little bit of debug information at the bottom of the page.

---

A copy of the site config is shown in the [C] notes.

$config['debug.db_required_fields']
