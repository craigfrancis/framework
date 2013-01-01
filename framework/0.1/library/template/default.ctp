<?php

//--------------------------------------------------
// CSS

	$this->css_add(gateway_url('framework-file', 'default.css'));

//--------------------------------------------------
// Navigation

	//--------------------------------------------------
	// Root folders

		$root_path = VIEW_ROOT . '/';
		$root_folders = array();
		if ($handle = opendir($root_path)) {
			while (false !== ($file = readdir($handle))) {

				if (is_file($root_path . $file) && substr($file, -4) == '.ctp' && $file != 'home.ctp') {
					$folder = substr($file, 0, -4);
					$root_folders[$folder] = ref_to_link($folder);
				}

			}
			closedir($handle);
		}

	//--------------------------------------------------
	// Sub pages

		$sub_pages = array();

		foreach ($root_folders as $root_folder => $root_url) {

			$sub_pages[$root_folder] = array();

			$folder_path = $root_path . $root_folder . '/';
			if (is_dir($folder_path)) {

				if ($handle = opendir($folder_path)) {
					while (false !== ($file = readdir($handle))) {

						if (is_file($folder_path . $file) && substr($file, -4) == '.ctp') {
							$folder = substr($file, 0, -4);
							$sub_pages[$root_folder][$folder] = ref_to_link($folder);
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

		foreach ($root_folders as $root_folder => $root_url) {

			$root_url = config::get('url.prefix') . '/' . urlencode($root_url) . '/';

			if (count($sub_pages[$root_folder]) > 0) {

				$sub_nav = new nav();

				foreach ($sub_pages[$root_folder] as $sub_folder => $sub_url) {
					$sub_nav->link_add($root_url . $sub_url . '/', ref_to_human($sub_folder));
				}

				$nav->link_add($root_url, ref_to_human($root_folder), array('child' => $sub_nav));

			} else {

				$nav->link_add($root_url, ref_to_human($root_folder));

			}

		}

?>
<!DOCTYPE html>
<html lang="<?= html($this->lang_get()) ?>" xml:lang="<?= html($this->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $this->head_get_html(); ?>

	<!--[if lt IE 9]>
		<script src="//html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->

</head>
<body id="<?= html($this->page_id_get()) ?>">

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

	<?= $this->message_get_html(); ?>

	<?= $this->view_get_html(); ?>

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