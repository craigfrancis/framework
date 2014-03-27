<?php

//--------------------------------------------------
// Resources

	$response->css_auto();

	// $response->js_add('/a/js/script.js');

//--------------------------------------------------
// Navigation

	$nav = new nav();
	$nav->link_add('/', 'Home');
	$nav->link_add('/contact/', 'Contact us');

?>
<!DOCTYPE html>
<html lang="<?= html($response->lang_get()) ?>" xml:lang="<?= html($response->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $response->head_get_html(); ?>

	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body id="<?= html($response->page_id_get()) ?>">

	<div id="page_wrapper">

		<header id="page_header" role="banner">
			<h1><a href="/"><?= html($response->title_get()) ?></a></h1>
		</header>

		<div id="page_container">

			<nav id="page_navigation" role="navigation">

				<h2>Navigation</h2>

				<?= $nav->html(); ?>

			</nav>

			<main id="page_content" role="main">









<!-- END OF PAGE TOP -->

	<?php if (isset($title_html)) { ?>

		<div id="page_title">
			<?= $title_html . "\n" ?>
		</div>

	<?php } ?>

	<?= $response->message_get_html(); ?>

	<?= $response->view_get_html(); ?>

<!-- START OF PAGE BOTTOM -->









			</main>

		</div>

		<footer id="page_footer" role="contentinfo">
			<h2>Footer</h2>
			<p class="copyright">© <?= html(config::get('output.site_name', 'Company Name')) ?> <?= html(date('Y')) ?></p>
		</footer>

	</div>

	<?= $response->foot_get_html(); ?>

</body>
</html>