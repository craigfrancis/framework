
# Content Security Policy

Otherwise known as CSP, it allows your website to inform the browser which resources can be loaded.

It is currently 'enabled' by default, and 'enforced' in [development mode](../../doc/setup/debug.md).

Ideally a new website will start with:

	$config['output.csp_enabled'] = true;
	$config['output.csp_enforced'] = true;
	$config['output.csp_report'] = false;

To customise the directives, start with something like:

	$config['output.csp_directives'] = array(
			'default-src'  => array("'none'"), // Ideal default
			'form-action'  => array("'self'"),
			'style-src'    => array("'self'"),
			'img-src'      => array("'self'"),
			'script-src'   => array("'self'"),
		);

For additional resources (e.g. on a per-page basis) you can also call:

	$response = response_get();
	$response->csp_source_add('frame-src', 'http://www.example.com');
	$response->csp_source_add('img-src', array('http://www.example.com', 'http://www.example.org'));

So for example, Google Maps might require:

	$response = response_get();
	$response->csp_source_add('style-src',  array('"unsafe-inline"'));
	$response->csp_source_add('script-src', array('"unsafe-inline"', '"unsafe-eval"', 'https://*.googleapis.com', 'https://*.gstatic.com'));
	$response->csp_source_add('img-src',    array('https://*.googleapis.com', 'https://*.gstatic.com'));

---

## Reporting

By default you should have a `system_report_csp` table, which is populated when the browser posts data to /a/api/csp-report/

If you want to record additional information in this table, you can either set the config variable:

	/app/library/setup/setup.php

	<?php

		config::set('output.csp_report_extra', array(
				'user_id'   => strval(USER_ID),
				'user_name' => strval(USER_NAME),
			));

	?>

Or you can create a function which is called from the API:

	$config['output.csp_report_handle'] = 'csp_report';

	function csp_report($report, $data_raw) {
		// Your code
	}

If this function returns an array, then it will work the same as `output.csp_report_extra`.
