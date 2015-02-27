
# CMS Text

You can view the source on [GitHub](https://github.com/craigfrancis/framework/blob/master/framework/0.1/library/class/cms/cms-text.php).

Helper function:

	<?= cms_text_html('content'); ?>

Example where the heading is separate... this is useful if the heading will also be used in the site navigation, or on the page <title>:

	<?= cms_text_html(array('section' => 'heading', 'wrapper_tag' => 'h1')); ?>
	<?= cms_text_html(array('section' => 'content')); ?>

---

## Configuration

Change path... must be done before calling the above helper function, but often used when you have a /item/123/ type page, and don't want the 123 being used in the path:

	config::set('cms.default.path', '/item/');

To make editable:

	config::set('cms.default.editable', ADMIN_LOGGED_IN);

---

## Full object version

Initialisation with profile or config:

	$cms_text = new cms_text('profile');

	$cms_text = new cms_text(array(
			'profile'     => 'example',
			'revision'    => 0,
			'processor'   => 'markdown',
			'editable'    => false, // See above
			'log_missing' => true,
			'versions'    => array(), // See below
			'priority'    => array(), // See below
		));

The 'profile' allows you to have use config variables:

	$config['cms.profile.name'] = 'value';

To retrieve the HTML content:

	echo $cms_text->html('section');

	echo $cms_text->html(array(
			'path'        => '/',
			'section'     => 'content',
			'default'     => 'Lorem ipsum dolor sit amet...',
			'variables'   => array('count' => 5), // e.g. "You have [COUNT] messages" - note the issue with 1 message (singular) in English
			'wrapper_tag' => 'div',
			'editable'    => false, // Default from init
			'log_missing' => true, // Default from init
			'global'      => false, // Make globally available to all pages (e.g. the page title)
			'marker'      => 'marker',
		));

---

## Version support

Set via config:

	$config['cms.default.versions'] = array();
	$config['cms.default.priority'] = array();

Or when creating the `cms_text` object:

	$cms_text = new cms_text(array(
			'priority' => array(),
		));

Version example - should be fixed for the profile:

	versions = array(
			'English'         => array('lang' => 'en', 'country' => ''),
			'French'          => array('lang' => 'fr', 'country' => ''),
			'Spanish'         => array('lang' => 'es', 'country' => ''),
			'Canadian French' => array('lang' => 'fr', 'country' => 'ca'),
		);

Priority examples:

	priority = array('English'); // Try English, then move onto the default.

	priority = array(
			'Canadian French',
			'French',
			'English',
		); // Try Canadian French first, French, English, then the default.

---

Helper methods

	cms_text::cache_files();
	cms_text::cache_clear();

This will return/clear all cache files, or you can pass in a path for a particular page.

---

Explain about `cms_tags/cms_markdown`.

https://www.w3.org/International/articles/composite-messages/Overview

https://www.localeapp.com/

And the cms_text controller for the admin.

