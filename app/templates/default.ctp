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
			$doc_nav->link_add('/doc/introduction/', 'Introduction');

		//--------------------------------------------------
		// Security

			$security_string_nav = new nav();
			$security_string_nav->link_add('/doc/security/strings/sql-injection/', 'SQL injection');
			$security_string_nav->link_add('/doc/security/strings/html-injection/', 'HTML injection');
			$security_string_nav->link_add('/doc/security/strings/url-manipulation/', 'URL manipulation');
			$security_string_nav->link_add('/doc/security/strings/header-injection/', 'Header injection');
			$security_string_nav->link_add('/doc/security/strings/cli-injection/', 'CLI Injection');
			$security_string_nav->link_add('/doc/security/strings/regexp-injection/', 'RegExp Injection');
			$security_string_nav->link_add('/doc/security/strings/path-manipulation/', 'Path manipulation');

			$security_nav = new nav();
			$security_nav->sub_nav_add('/doc/security/strings/', 'Strings', $security_string_nav);
			$security_nav->link_add('/doc/security/csrf/', 'Cross site request forgery');
			$security_nav->link_add('/doc/security/framing/', 'Site framing'); // X-Frame-Options header
			$security_nav->link_add('/doc/security/transport/', 'Strict transport security');
			$security_nav->link_add('/doc/security/csp/', 'Content security policy');
			$security_nav->link_add('/doc/security/logins/', 'Login and passwords'); // Identification/verification, multiple sessions, failed logins, password hashing (slow), lost passwords
			$security_nav->link_add('/doc/security/files/', 'File uploads'); // Uploading a php file to a public location, or images containing exploits.

			$doc_nav->sub_nav_add('/doc/security/', 'Security', $security_nav);

		//--------------------------------------------------
		// Setup

			$setup_nav = new nav();
			$setup_nav->link_add('/doc/setup/structure/', 'Structure');
			$setup_nav->link_add('/doc/setup/bootstrap/', 'Bootstrap');
			$setup_nav->link_add('/doc/setup/config/', 'Config');
			$setup_nav->link_add('/doc/setup/constants/', 'Constants');
			$setup_nav->link_add('/doc/setup/debug/', 'Debug');
			$setup_nav->link_add('/doc/setup/controllers/', 'Controllers');
			$setup_nav->link_add('/doc/setup/views/', 'Views');
			$setup_nav->link_add('/doc/setup/templates/', 'Templates');
			$setup_nav->link_add('/doc/setup/gateways/', 'Gateways');
			$setup_nav->link_add('/doc/setup/jobs/', 'Jobs');
			$setup_nav->link_add('/doc/setup/favicon/', 'Favicon');
			$setup_nav->link_add('/doc/setup/robots/', 'Robots (txt)');
			$setup_nav->link_add('/doc/setup/sitemap/', 'Sitemap (xml)');
			$setup_nav->link_add('/doc/setup/testing/', 'Testing');

			$doc_nav->sub_nav_add('/doc/setup/', 'Setup', $setup_nav);

		//--------------------------------------------------
		// Helpers

			$helpers_nav = new nav();
			$helpers_nav->link_add('/doc/helpers/config/', 'Config');
			$helpers_nav->link_add('/doc/helpers/session/', 'Session');
			$helpers_nav->link_add('/doc/helpers/cookie/', 'Cookie');
			$helpers_nav->link_add('/doc/helpers/functions/', 'Functions');
			$helpers_nav->link_add('/doc/helpers/database/', 'Database');
			$helpers_nav->link_add('/doc/helpers/url/', 'URL');
			$helpers_nav->link_add('/doc/helpers/form/', 'Form');
			$helpers_nav->link_add('/doc/helpers/email/', 'Email');
			$helpers_nav->link_add('/doc/helpers/file/', 'File');
			$helpers_nav->link_add('/doc/helpers/nav/', 'Navigation');
			$helpers_nav->link_add('/doc/helpers/table/', 'Table');
			$helpers_nav->link_add('/doc/helpers/paginator/', 'Paginator');
			$helpers_nav->link_add('/doc/helpers/user/', 'User');

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