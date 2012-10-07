<?php

//--------------------------------------------------
// Resources

	resources::css_auto();

//--------------------------------------------------
// Navigation

	//--------------------------------------------------
	// Documentation

		//--------------------------------------------------
		// Start

			$doc_nav = new nav();
			$doc_nav->link_add('#', 'Introduction');

		//--------------------------------------------------
		// Security

			$security_string_nav = new nav();
			$security_string_nav->link_add('/doc/security/strings/sql-injection/', 'SQL injection');
			$security_string_nav->link_add('/doc/security/strings/html-injection/', 'HTML injection');
			$security_string_nav->link_add('/doc/security/strings/header-injection/', 'Header injection');
			$security_string_nav->link_add('/doc/security/strings/url-manipulation/', 'URL manipulation');
			$security_string_nav->link_add('/doc/security/strings/path-manipulation/', 'Path manipulation'); // File uploads going to custom location (e.g. ".."), see safe_file_name()

			$security_nav = new nav();
			$security_nav->sub_nav_add('/doc/security/strings/', 'Strings', $security_string_nav, NULL, true);
			$security_nav->link_add('#', 'Cross site request forgery');
			$security_nav->link_add('#', 'Site framing'); // X-Frame-Options header
			$security_nav->link_add('#', 'Strict transport security');
			$security_nav->link_add('#', 'Content security policy');
			$security_nav->link_add('#', 'Login and passwords'); // Identification/verification, multiple sessions, failed logins, password hashing (slow), lost passwords
			$security_nav->link_add('#', 'File uploads'); // Uploading a php file to a public location, or images containing exploits.

			$doc_nav->sub_nav_add('/doc/security/', 'Security', $security_nav);

		//--------------------------------------------------
		// Structure

			$structure_app_nav = new nav();
			$structure_app_nav->link_add('#', 'Controller');
			$structure_app_nav->link_add('#', 'Gateway');
			$structure_app_nav->link_add('#', 'Jobs');
			$structure_app_nav->link_add('#', 'Library');
			$structure_app_nav->link_add('#', 'Public');
			$structure_app_nav->link_add('#', 'Setup');
			$structure_app_nav->link_add('#', 'Templates');
			$structure_app_nav->link_add('#', 'View');

			$structure_nav = new nav();
			$structure_nav->sub_nav_add('/doc/structure/app/', 'App', $structure_app_nav);
			$structure_nav->link_add('#', 'Files');
			$structure_nav->link_add('#', 'Framework');
			$structure_nav->link_add('#', 'Httpd');
			$structure_nav->link_add('#', 'Logs');
			$structure_nav->link_add('#', 'Private');
			$structure_nav->link_add('#', 'Resources');

			$doc_nav->sub_nav_add('/doc/structure/', 'Structure', $structure_nav);

		//--------------------------------------------------
		// Helpers

			$helpers_nav = new nav();
			$helpers_nav->link_add('#', 'Config');
			$helpers_nav->link_add('#', 'Functions'); // debug, escaping (html/csv), string (format_currency), mime_set/render_error, redirect
			$helpers_nav->link_add('#', 'Session');
			$helpers_nav->link_add('#', 'Cookie');
			$helpers_nav->link_add('#', 'URL');
			$helpers_nav->link_add('#', 'Email');
			$helpers_nav->link_add('#', 'File');
			$helpers_nav->link_add('#', 'Navigation');
			$helpers_nav->link_add('#', 'Table');
			$helpers_nav->link_add('#', 'Paginator');
			$helpers_nav->link_add('#', 'Form'); // inc save_request_restore/save_request_redirect
			$helpers_nav->link_add('#', 'User');

			$doc_nav->sub_nav_add('/doc/helpers/', 'Helpers', $helpers_nav);

	//--------------------------------------------------
	// Main

		$nav = new nav();
		$nav->link_add('/', 'Home');
		$nav->link_add('/contact/', 'Contact');
		$nav->link_add('/form-export/', 'Form');
		$nav->link_add('/loading/', 'Loading');
		$nav->link_add('/table/', 'Table');
		$nav->link_add('/browser/', 'Browser');
		$nav->link_add('/conversions/', 'Conversions');
		$nav->sub_nav_add('/doc/', 'Documentation', $doc_nav);

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

</body>
</html>