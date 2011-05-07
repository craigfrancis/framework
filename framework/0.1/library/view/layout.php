<?php

//--------------------------------------------------
// Navigation

	// Loop though files and folders in ROOT_SITE . '/view/'

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="<?= config::get('output.lang') ?>" xml:lang="<?= config::get('output.lang') ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<title><?= config::get('output.title') ?></title>

	<meta http-equiv="content-type" content="<?= html(config::get('output.mime')) ?>; charset=<?= html(config::get('output.charset')) ?>" />

	<link rel="shortcut icon" type="image/x-icon" href="<?= html(config::get('url.prefix')) ?>/a/img/global/favicon.ico" />

	<style type="text/css">
		<?= str_replace("\n", "\n\t\t", file_get_contents(ROOT_FRAMEWORK . '/library/view/layout.css')) ?>
	</style>

	<?= config::get('output.head_html') ?>

</head>
<body id="p_<?= html(config::get('output.page_ref_request')) ?>">

	<div id="page_wrapper">

		<div id="page_title">
			<h1><?= config::get('output.title') ?></h1>
		</div>

		<div id="page_container">

			<div id="page_navigation">

				<h2>Site Navigation</h2>

			</div>

			<div id="page_content">









<!-- END OF PAGE TOP -->

	<?= config::get('output.message_html') ?>

	<?= config::get('output.html') ?>

<!-- START OF PAGE BOTTOM -->









			</div>

		</div>

		<div id="page_footer">
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