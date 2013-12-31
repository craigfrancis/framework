
# Content Security Policy

Otherwise known as CSP, it allows your website to inform the browser which resources can be loaded.

It is currently 'enabled' by default, and 'enforced' in [development mode](../../doc/setup/debug.md).

Ideally a new website will just start with:

	$config['output.csp_enabled'] = true;
	$config['output.csp_enforced'] = true;

To debug, just look in the browser console.

To customise the header, start with something like:

	$config['output.csp_directives'] = array(
			'default-src' => array(
					"'none'", // Ideal default
				),
			'img-src' => array(
					"'self'",
				),
			'script-src' => array(
					"'self'",
				),
			'style-src' => array(
					"'self'",
				),
			'connect-src' => array(
					"'self'",
				),
		);

For additional resources (e.g. on a per-page basis) you can also call:

	$response = response_get();
	$response->csp_add_source('frame-src', array('https://www.example.com'));

---

## Reporting

By default you should have a `system_report_csp` table, which is populated by the browser posting data to /a/api/csp-report/

If you want to record additional information in this table, you can set a config variable:

	/app/library/setup/setup.php

	<?php

		config::set('output.csp_report_extra', array(
				'user_id'   => strval(USER_ID),
				'user_name' => strval(USER_NAME),
			));

	?>

Alternatively you could create a function which is called from the API:

	$config['output.csp_report_handle'] = 'csp_report';

	function csp_report($report, $data_raw) {
		// Your code
	}

If this function returns an array, then it will work the same as `output.csp_report_extra`.


