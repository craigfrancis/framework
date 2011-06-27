<?php

//--------------------------------------------------
// Debug

	if (config::get('debug.level') >= 4) {
		debug_progress('Start view', 2);
	}

//--------------------------------------------------
// Example CSS version set

	$this->css_version_set(1); // TODO: Remove

	if (config::get('debug.level') >= 4) {
		debug_progress('CSS Version', 2);
	}

//--------------------------------------------------
// Navigation

	$root_path = ROOT_APP . '/view/';
	$root_folders = array();
	if ($handle = opendir($root_path)) {
		while (false !== ($file = readdir($handle))) {
			if (substr($file, 0, 1) != '.') {

				if (is_file($root_path . $file) && substr($file, -4) == '.ctp' && $file != 'home.ctp') {

					$root_folders[] = substr($file, 0, -4);

				} else if (is_dir($root_path . $file)) {

					$root_folders[] = $file;

				}

			}
		}
		closedir($handle);
	}
	sort($root_folders);

	$nav = new nav();
	$nav->link_add(config::get('url.prefix') . '/', 'Home');

	foreach ($root_folders as $folder) {
		$nav->link_add(config::get('url.prefix') . '/' . urlencode($folder) . '/', link_to_human($folder));
	}

	if (config::get('debug.level') >= 4) {
		debug_progress('Navigation', 2);
	}

?>
<!DOCTYPE html>
<html lang="<?= config::get('output.lang') ?>" xml:lang="<?= config::get('output.lang') ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $this->head_html() ?>

	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body id="p_<?= html($this->page_ref_get()) ?>">

	<div id="page_wrapper">

		<div id="page_title">
			<h1><?= html($this->title()) ?></h1>
		</div>

		<div id="page_container">

			<div id="page_navigation">

				<h2>Site Navigation</h2>

				<?= $nav ?>

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

				<li class="copyright">© <?= html(config::get('email.from_name', 'Company')) ?> <?= html(date('Y')) ?></li>

			</ul>
		</div>

	</div>

	<?= $this->tracking_html(); ?>

	<?php //view_element('google_analytics'); ?>

</body>
</html>