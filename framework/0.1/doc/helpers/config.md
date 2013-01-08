
# Config helper

See the setup section for an overview of the [site config](../../doc/setup/config.md).

But for a quick overview, you can access the config values though this helper.

	config::set('name', $value);

	config::get('name');
	config::get('name', 'Default');

In the Object Orientated world, the config helper works as a singleton. While this is known to have issues in many cases, in this case it makes it very easy to get/set information that should be globally available.

---

## Set values

Once the [config.php](../../doc/setup/config.md) file has been processed, variables can be `set` with:

	config::set('name', 'New value');

And if you want to set a default, when a value has not already been set:

	config::set_default('name', 'Default value');

---

## Get values

To get a value, simply call:

	config::get('name');

Which will either return the value if set, or NULL.

A default can also by provided:

	config::get('name', 'default');

You will notice that most [config variables](../../doc/setup/config.md) will include a dot in the name. This is to help with grouping related things together.

So for example, to return all the config variables for `name`:

	config::set('name.first', 1);
	config::set('name.second', 1);
	config::set('name.third', 3);

	debug(config::get_all('name'));

---

## Arrays

While rarely used, as a config variable may be an array, there are a few functions to help:

	config::array_push('name', 'value1');
	config::array_push('name', 'value2');
	config::array_set('name', 'key', 'value3');

	debug(config::array_get('name', 'key', 'default'));
	debug(config::get('name'));

But its probably best to use a local variable, and do a single `set()` at the end.
