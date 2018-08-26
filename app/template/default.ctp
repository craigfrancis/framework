<?php

//--------------------------------------------------
// CSS

	$response->css_auto();

//--------------------------------------------------
// Navigation

	//--------------------------------------------------
	// Top

		$top_nav = new nav();
		$top_nav->link_add('/', 'Home');
		$top_nav->link_add('/doc/', 'Documentation');
		$top_nav->link_add('/examples/', 'Examples');
		$top_nav->link_add('/contact/', 'Contact');

	//--------------------------------------------------
	// Side

		$section_nav = new nav();
		$section_title = 'PHP Prime';

		if (request_folder_get(0) == 'doc') {

			//--------------------------------------------------
			// Documentation

				$section_title = 'Documentation';

				$section_nav->link_add('/doc/introduction/', 'Introduction');

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
				$security_nav->link_add('/doc/security/strings/', 'Strings', array('child' => $security_string_nav));
				$security_nav->link_add('/doc/security/csrf/', 'Cross site request forgery');
				$security_nav->link_add('/doc/security/xss/', 'Cross site scripting');
				$security_nav->link_add('/doc/security/framing/', 'Site framing'); // X-Frame-Options header
				$security_nav->link_add('/doc/security/transport/', 'Strict transport security');
				$security_nav->link_add('/doc/security/pkp/', 'Public key pinning');
				$security_nav->link_add('/doc/security/csp/', 'Content security policy');
				$security_nav->link_add('/doc/security/logins/', 'Login and passwords'); // Identification/verification, multiple sessions, failed logins, password hashing (slow), lost passwords
				$security_nav->link_add('/doc/security/files/', 'File uploads'); // Uploading a php file to a public location, or images containing exploits.
				$security_nav->link_add('/doc/security/sessions/', 'Sessions'); // Uploading a php file to a public location, or images containing exploits.

				$section_nav->link_add('/doc/security/', 'Security', array('child' => $security_nav));

			//--------------------------------------------------
			// Setup

				$server_nav = new nav();
				$server_nav->link_add('/doc/setup/server/apache/', 'Apache');
				$server_nav->link_add('/doc/setup/server/nginx/', 'Nginx');

				$resources_nav = new nav();
				$resources_nav->link_add('/doc/setup/resources/favicon/', 'Favicon');
				$resources_nav->link_add('/doc/setup/resources/robots/', 'Robots (txt)');
				$resources_nav->link_add('/doc/setup/resources/sitemap/', 'Sitemap (xml)');

				$setup_nav = new nav();
				$setup_nav->link_add('/doc/setup/structure/', 'Structure');
				$setup_nav->link_add('/doc/setup/server/', 'Server', array('child' => $server_nav));
				$setup_nav->link_add('/doc/setup/views/', 'Views');
				$setup_nav->link_add('/doc/setup/templates/', 'Templates');
				$setup_nav->link_add('/doc/setup/units/', 'Units');
				$setup_nav->link_add('/doc/setup/controllers/', 'Controllers');
				$setup_nav->link_add('/doc/setup/routes/', 'Routes');
				$setup_nav->link_add('/doc/setup/gateways/', 'Gateways');
				$setup_nav->link_add('/doc/setup/jobs/', 'Jobs');
				$setup_nav->link_add('/doc/setup/config/', 'Config');
				$setup_nav->link_add('/doc/setup/constants/', 'Constants');
				$setup_nav->link_add('/doc/setup/resources/', 'Resources', array('child' => $resources_nav));
				$setup_nav->link_add('/doc/setup/debug/', 'Debug');
				$setup_nav->link_add('/doc/setup/bootstrap/', 'Bootstrap');
				$setup_nav->link_add('/doc/setup/cli/', 'CLI');

				$section_nav->link_add('/doc/setup/', 'Setup', array('child' => $setup_nav));

			//--------------------------------------------------
			// System

				$system_nav = new nav();
				$system_nav->link_add('/doc/system/response/', 'Response');
				$system_nav->link_add('/doc/system/functions/', 'Functions');
				$system_nav->link_add('/doc/system/database/', 'Database');
				$system_nav->link_add('/doc/system/uploading/', 'Uploading');
				$system_nav->link_add('/doc/system/tester/', 'Tester');

				$section_nav->link_add('/doc/system/', 'System', array('child' => $system_nav));

			//--------------------------------------------------
			// Helpers

				$form_setup_nav = new nav();
				$form_setup_nav->link_add('/doc/helpers/form/setup/', 'Database');

				$form_fields_nav = new nav();
				$form_fields_nav->link_add('/doc/helpers/form/fields/text/', 'Text');
				$form_fields_nav->link_add('/doc/helpers/form/fields/text-area/', 'Text area');
				$form_fields_nav->link_add('/doc/helpers/form/fields/email/', 'Email');
				$form_fields_nav->link_add('/doc/helpers/form/fields/password/', 'Password');
				$form_fields_nav->link_add('/doc/helpers/form/fields/number/', 'Number');
				$form_fields_nav->link_add('/doc/helpers/form/fields/currency/', 'Currency');
				$form_fields_nav->link_add('/doc/helpers/form/fields/postcode/', 'Postcode');
				$form_fields_nav->link_add('/doc/helpers/form/fields/url/', 'URL');
				$form_fields_nav->link_add('/doc/helpers/form/fields/date/', 'Date');
				$form_fields_nav->link_add('/doc/helpers/form/fields/time/', 'Time');
				$form_fields_nav->link_add('/doc/helpers/form/fields/select/', 'Select');
				$form_fields_nav->link_add('/doc/helpers/form/fields/file/', 'File');
				$form_fields_nav->link_add('/doc/helpers/form/fields/image/', 'Image');
				$form_fields_nav->link_add('/doc/helpers/form/fields/check-box/', 'Check box');
				$form_fields_nav->link_add('/doc/helpers/form/fields/check-boxes/', 'Check boxes');
				$form_fields_nav->link_add('/doc/helpers/form/fields/radios/', 'Radios');
				$form_fields_nav->link_add('/doc/helpers/form/fields/info/', 'Info');
				$form_fields_nav->link_add('/doc/helpers/form/fields/html/', 'HTML');

				$form_nav = new nav();
				$form_nav->link_add('/doc/helpers/form/', 'Introduction');
				$form_nav->link_add('/doc/helpers/form/setup/', 'Setup', array('child' => $form_setup_nav));
				$form_nav->link_add('/doc/helpers/form/fields/', 'Fields', array('child' => $form_fields_nav));

				$helpers_nav = new nav();
				$helpers_nav->link_add('/doc/helpers/cms-text/', 'CMS Text');
				$helpers_nav->link_add('/doc/helpers/config/', 'Config');
				$helpers_nav->link_add('/doc/helpers/cookie/', 'Cookie');
				$helpers_nav->link_add('/doc/helpers/email/', 'Email');
				$helpers_nav->link_add('/doc/helpers/file/', 'File');
				$helpers_nav->link_add('/doc/helpers/form/', 'Form'); // , array('child' => $form_nav)
				$helpers_nav->link_add('/doc/helpers/gpg/', 'GPG');
				$helpers_nav->link_add('/doc/helpers/image/', 'Image');
				$helpers_nav->link_add('/doc/helpers/loading/', 'Loading');
				$helpers_nav->link_add('/doc/helpers/lock/', 'Lock');
				$helpers_nav->link_add('/doc/helpers/nav/', 'Navigation');
				$helpers_nav->link_add('/doc/helpers/nearest/', 'Nearest');
				$helpers_nav->link_add('/doc/helpers/order/', 'Order');
				$helpers_nav->link_add('/doc/helpers/paginator/', 'Paginator');
				$helpers_nav->link_add('/doc/helpers/payment/', 'Payment');
				$helpers_nav->link_add('/doc/helpers/query/', 'Query');
				$helpers_nav->link_add('/doc/helpers/record/', 'Record');
				$helpers_nav->link_add('/doc/helpers/session/', 'Session');
				$helpers_nav->link_add('/doc/helpers/socket/', 'Socket');
				$helpers_nav->link_add('/doc/helpers/table/', 'Table');
				$helpers_nav->link_add('/doc/helpers/timestamp/', 'Timestamp');
				$helpers_nav->link_add('/doc/helpers/url/', 'URL');
				$helpers_nav->link_add('/doc/helpers/user/', 'User');

				$section_nav->link_add('/doc/helpers/', 'Helpers', array('child' => $helpers_nav));

			//--------------------------------------------------
			// Notes

				$notes_nav = new nav();
				$notes_nav->link_add('/doc/notes/history/', 'History');
				$notes_nav->link_add('/doc/notes/frameworks/', 'Frameworks');
				$notes_nav->link_add('/doc/notes/white-site/', 'White site');

				$section_nav->link_add('/doc/notes/', 'Notes', array('child' => $notes_nav));

		} else if (request_folder_get(0) == 'examples') {

			//--------------------------------------------------
			// Examples

				$section_title = 'Examples';

				$section_nav->link_add('/examples/form/', 'Form');
				$section_nav->link_add('/examples/encryption/', 'Encryption');
				$section_nav->link_add('/examples/image/', 'Image');
				$section_nav->link_add('/examples/loading/', 'Loading');
				$section_nav->link_add('/examples/lock/', 'Lock');
				$section_nav->link_add('/examples/table/', 'Table');
				$section_nav->link_add('/examples/browser/', 'Browser');
				$section_nav->link_add('/examples/conversions/', 'Conversions');

		}

?>
<!DOCTYPE html>
<html lang="<?= html($response->lang_get()) ?>" xml:lang="<?= html($response->lang_get()) ?>" xmlns="http://www.w3.org/1999/xhtml">
<head>

	<?= $response->head_get_html() ?>

	<link rel="author" href="http://www.craigfrancis.co.uk/contact/" />

</head>
<body id="<?= html($response->page_id_get()) ?>">

	<div id="page_wrapper">

		<header id="page_header" role="banner">

			<h1><?= html($response->title_get()) ?></h1>

			<?= $top_nav->html(); ?>

		</header>

		<div id="page_container">

			<nav id="page_navigation" role="navigation">

				<h2><?= html($section_title); ?></h2>

				<?= $section_nav->html(); ?>

			</nav>

			<main id="page_content" role="main">









<!-- END OF PAGE TOP -->

	<?php if (isset($title_html)) { ?>

		<div id="page_title">
			<?= $title_html . "\n" ?>
		</div>

	<?php } ?>

	<?= $response->view_get_html() ?>

<!-- START OF PAGE BOTTOM -->









			</main>

		</div>

		<footer id="page_footer" role="contentinfo">
			<h2>Footer</h2>
			<ul>

				<li class="copyright">© <?= html(config::get('output.site_name', 'Company Name')) ?> <?= html(date('Y')) ?></li>

			</ul>
		</footer>

	</div>

	<?= $response->foot_get_html(); ?>

</body>
</html>