
# Templates

After the output from the [view](../../doc/setup/views.md) or [units](../../doc/setup/units.md) has been created, it is simply included in the template:

	/app/template/default.ctp

	<?php

		$response->css_auto();

		$nav = new nav();
		$nav->link_add('/', 'Home');

	?>
	<!DOCTYPE html>
	<html lang="<?= html($response->lang_get()) ?>" xml:lang="<?= html($response->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
	<head>

		<?= $response->head_get_html(); ?>

	</head>
	<body id="<?= html($response->page_id_get()) ?>">

		<header id="page_header" role="banner">
			<p><img src="/a/img/logo.png" alt="Site Logo" /></p>
		</header>

		<nav id="page_navigation" role="navigation">
			<?= $nav->html(); ?>
		</nav>

		<main id="page_content" role="main">
			<?= $response->view_get_html(); ?>
		</main>

		<?= $response->foot_get_html(); ?>

	</body>
	</html>

The **`$response->head_get_html();`** will add the following:

- `<meta charset="UTF-8">`
- `<title>` Set with the [response object](../../doc/system/response.md).
- `<meta name="description">` Set with the [response object](../../doc/system/response.md).
- `<link rel="shortcut icon">` For the [favicon.ico](../../doc/setup/resources/favicon.md).
- `<link rel="canonical">`
- `<link rel="stylesheet">` For the [style sheets](../../doc/setup/resources.md).
- `<script>`

And then anything else you have added with `$response->head_add_html();`

Note that these `<script>` tags are added with:

	$response->js_add('/a/js/js-loading.js', 'async', 'head');

---

## Variables

Like in [views](../../doc/setup/views.md), if the [controller](../doc/setup/controllers.md) sets a variable, such as:

	$response = response_get();
	$response->set('name', 'value');

Then it can also be used in the template ctp file, e.g.

	<?= html($name) ?>

The `$response` variable is also available by default.

---

## Response object

The [response object](../../doc/system/response.md) provides a few methods, the 3 most important are:

	$response->view_get_html();

	$response->head_get_html();
	$response->foot_get_html();

The last 2 will return the necessary HTML for the CSS, JavaScript, etc.
