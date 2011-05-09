<?php

//--------------------------------------------------
// Navigation

	// Loop though files and folders in ROOT_APP . '/view/'

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html lang="<?= config::get('output.lang') ?>" xml:lang="<?= config::get('output.lang') ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $this->head_html() ?>

</head>
<body id="p_<?= html($this->page_ref()) ?>">

	<div id="page_wrapper">

		<div id="page_title">
			<h1><?= html($this->title()) ?></h1>
		</div>

		<div id="page_container">

			<div id="page_navigation">

				<h2>Site Navigation</h2>

			</div>

			<div id="page_content">









<!-- END OF PAGE TOP -->

	<?= $this->message_html() ?>

	<?= $this->view_html() ?>

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