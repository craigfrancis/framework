<?php

//--------------------------------------------------
// Resources

	resources::css_auto();

//--------------------------------------------------
// Navigation

	$nav = new nav();
	$nav->link_add('/', 'Home');

?>
<!DOCTYPE html>
<html lang="<?= html(config::get('output.lang')) ?>" xml:lang="<?= html(config::get('output.lang')) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $this->head_get_html(); ?>

	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body id="<?= html($this->page_ref_get()) ?>">

	<div id="page_wrapper">

		<header id="page_title">
			<h1><?= html($this->title_get()) ?></h1>
		</header>

		<div id="page_container">

			<nav id="page_navigation">

				<h2>Site Navigation</h2>

				<?= $nav->html(); ?>

			</nav>

			<div id="page_content" role="main">









<!-- END OF PAGE TOP -->

	<?= $this->message_get_html() ?>

	<?= $this->view_get_html() ?>

<!-- START OF PAGE BOTTOM -->









			</div>

		</div>

		<footer id="page_footer">
			<h2>Footer</h2>
			<ul>

				<li class="copyright">© <?= html(config::get('output.site_name', 'Company Name')) ?> <?= html(date('Y')) ?></li>

			</ul>
		</footer>

	</div>

	<?= $this->foot_get_html(); ?>

</body>
</html>