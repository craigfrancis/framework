<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="en-GB" xml:lang="en-GB" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<title>Website</title>

	<meta http-equiv="content-type" content="<?= html(config::get('output.mime')) ?>; charset=<?= html(config::get('output.charset')) ?>" />

	<link rel="shortcut icon" type="image/x-icon" href="<?= html(config::get('url.prefix')) ?>/a/img/global/favicon.ico" />

	<?= config::get('output.head_html') ?>

</head>
<body id="<?= html(config::get('output.page_id')) ?>">

	<div id="pageWrapper">

		<div id="pageTitle">
			<h1>Website Title</h1>
		</div>

		<div id="pageContainer">

			<div id="pageNavigation">

				<h2>Site Navigation</h2>

			</div>

			<div id="pageContent">









<!-- END OF PAGE TOP -->

	<?= config::get('output.html') ?>

<!-- START OF PAGE BOTTOM -->









			</div>

		</div>

		<div id="pageFooter">
			<h2>Footer</h2>
			<ul>

				<li class="copyright">&copy; Company <?= html(date('Y')) ?></li>

			</ul>
		</div>

	</div>

	<?php
		//view_element('google_analytics');
	?>

</body>
</html>