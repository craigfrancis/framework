<?php

//--------------------------------------------------
// Debug

	if (config::get('debug.level') >= 4) {
		debug_progress('Start view', 2);
	}

//--------------------------------------------------
// Navigation

	//--------------------------------------------------
	// Root folders

		$root_path = VIEW_ROOT . '/';
		$root_folders = array();
		if ($handle = opendir($root_path)) {
			while (false !== ($file = readdir($handle))) {

				if (is_file($root_path . $file) && substr($file, -4) == '.ctp' && $file != 'home.ctp') {
					$root_folders[] = substr($file, 0, -4);
				}

			}
			closedir($handle);
		}
		$root_folders = array_unique($root_folders);

	//--------------------------------------------------
	// Sub pages

		$sub_pages = array();

		foreach ($root_folders as $folder) {

			$sub_pages[$folder] = array();

			$folder_path = $root_path . $folder . '/';
			if (is_dir($folder_path)) {

				if ($handle = opendir($folder_path)) {
					while (false !== ($file = readdir($handle))) {

						if (is_file($folder_path . $file) && substr($file, -4) == '.ctp') {
							$sub_pages[$folder][] = substr($file, 0, -4);
						}

					}
					closedir($handle);
				}

			}

		}

	//--------------------------------------------------
	// Build nav

		$nav = new nav();
		$nav->link_add(config::get('url.prefix') . '/', 'Home');

		foreach ($root_folders as $folder) {

			$root_url = config::get('url.prefix') . '/' . urlencode($folder) . '/';

			if (count($sub_pages[$folder]) > 0) {

				$sub_nav = new nav();

				foreach ($sub_pages[$folder] as $sub_page) {
					$sub_nav->link_add($root_url . $sub_page . '/', link_to_human($sub_page));
				}

				$nav->sub_nav_add($root_url, link_to_human($folder), $sub_nav);

			} else {

				$nav->link_add($root_url, link_to_human($folder));

			}

		}

		if (config::get('debug.level') >= 4) {
			debug_progress('Navigation', 2);
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
<body id="p_<?= html($this->page_ref_get()) ?>">

	<div id="page_wrapper">

		<div id="page_title">
			<h1><?= html($this->title_get()) ?></h1>
		</div>

		<div id="page_container">

			<div id="page_navigation">

				<h2>Site Navigation</h2>

				<?= $nav->html(); ?>

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

	<?php //view_element('google_analytics'); ?>

</body>
</html>