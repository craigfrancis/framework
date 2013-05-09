
# Navigation helper

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/nav.php).

Example setup:

	$nav = new nav();
	$nav->link_add('/', 'Home');
	$nav->link_add('/contact/', 'Contact us');
	$nav->link_add('/help/', 'Help');

And to print:

	echo $nav->html();

---

## Using groups

	$nav = new nav();

	$nav->group_add('Group A');
	$nav->link_add('/a1/', 'A1');
	$nav->link_add('/a2/', 'A2');
	$nav->link_add('/a3/', 'A3');

	$nav->group_add('Group B');
	$nav->link_add('/b1/', 'B1');
	$nav->link_add('/b2/', 'B2');
	$nav->link_add('/b3/', 'B3');

---

## Sub navigation

	$sub_nav = new nav();
	$sub_nav->link_add('/about/1/', 'Page 1');
	$sub_nav->link_add('/about/2/', 'Page 2');
	$sub_nav->link_add('/about/3/', 'Page 3');

	$nav = new nav();
	$nav->link_add('/', 'Home');
	$nav->link_add('/about/', 'About', array('child' => $sub_nav));

By default the sub-nav will not be rendered until the path matches the parent link.

To disable this behaviour you can call:

	$nav->automatically_expand_children(false);

To have all sub-navs open:

	$nav->expand_all_children(true);

Or just specify it on a per link basis:

	$nav->link_add('/about/', 'About', array('child' => $sub_nav, 'open' => true));

---

## Selected link

Normally links will be selected automatically, based on the best match of `request.uri`.

Instead you can manually specify which link is selected:

	$nav->link_add('/path/', 'Name', true);
	$nav->link_add('/path/', 'Name', array('selected' => true));

Or to just switch off the automatic process, just call:

	$nav->automatically_select_link(false);

Alternatively, if the 'request.uri' is not what you want to match against, you can instead call:

	$nav->path_set('/path/');

---

## Custom link text

If the navigation text needs to be set elsewhere (e.g. for localisation), then the nav object can be expended with

	/app/library/class/nav.php

	class nav extends nav_base {
		public function link_name_get($url)
		public function link_name_get($url, $config)
	}

An example of this is can be found for the [white-site](../../doc/notes/white-site.md).

---

## White space

The whitespace indent can be set to something other than 3 tabs either by:

	$nav->indent_set(3);

Or if you want to completely remove the white space:

	$nav->include_white_space(false);

---

## Other configuration

For the full list of link configuration options:

	$nav->link_add('/about/', 'About', array(
			'selected' => true,
			'child' => $sub_nav,
			'open' => true,
			'html' => false, // If second parameter is already html encoded (e.g. an image)
			'item_class' => 'xxx',
			'link_class' => 'xxx',
			'link_title' => 'xxx',
		));

Or if you just want to add a custom `class` to the main unordered list:

	$nav->main_class_set('xxx');
