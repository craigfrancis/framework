<?php

//--------------------------------------------------
// Resources

	resources::css_auto();

//--------------------------------------------------
// Navigation

	//--------------------------------------------------
	// Top

		$top_nav = new nav();
		$top_nav->link_add('/', 'Home');
		$top_nav->link_add('/doc/', 'Documentation');
		$top_nav->link_add('/contact/', 'Contact');

	//--------------------------------------------------
	// Side

		if (!isset($section_title)) {
			$section_title = 'PHP Prime';
		}

		if (!isset($section_nav)) {

			$section_nav = new nav();
			$section_nav->link_add('/', 'Home');
			$section_nav->link_add('/form-export/', 'Form');
			$section_nav->link_add('/loading/', 'Loading');
			$section_nav->link_add('/table/', 'Table');
			$section_nav->link_add('/browser/', 'Browser');
			$section_nav->link_add('/conversions/', 'Conversions');

		}

?>
<!DOCTYPE html>
<html lang="<?= html(config::get('output.lang')) ?>" xml:lang="<?= html(config::get('output.lang')) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $this->head_get_html() ?>

	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body id="<?= html($this->page_ref_get()) ?>">

	<div id="page_wrapper">

		<div id="page_title">

			<h1><?= html($this->title_get()) ?></h1>

			<?= $top_nav->html(); ?>

		</div>

		<div id="page_container">

			<div id="page_navigation">

				<h2><?= html($section_title); ?></h2>

				<?= $section_nav->html(); ?>

			</div>

			<div id="page_content">









<!-- END OF PAGE TOP -->

	<?= $this->message_get_html() ?>

	<?= $this->view_get_html() ?>

<!-- START OF PAGE BOTTOM -->









			</div>

		</div>

		<div id="page_footer">
			<h2>Footer</h2>
			<ul>

				<li class="copyright">© <?= html(config::get('output.site_name', 'Company Name')) ?> <?= html(date('Y')) ?></li>

			</ul>
		</div>

	</div>

	<?= $this->tracking_get_html(); ?>

</body>
</html>