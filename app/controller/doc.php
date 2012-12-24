<?php

	class doc_controller extends controller {

		public function route() {

			//--------------------------------------------------
			// Config

				$doc_root_path = FRAMEWORK_ROOT . '/doc/';

			//--------------------------------------------------
			// Requested page

				$request_path = config::get('request.path');

				if ($request_path == '/doc/') {
					redirect(url('/doc/introduction/'));
				}

				if (substr($request_path, 0, 5)  == '/doc/') $request_path = substr($request_path, 5);
				if (substr($request_path, -1)    == '/')     $request_path = substr($request_path, 0, -1);

				$doc_file_path = realpath($doc_root_path . $request_path . '.txt');

			//--------------------------------------------------
			// Navigation

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
					$security_nav->link_add('/doc/security/strings/', 'Strings', array('child' => $security_string_nav));
					$security_nav->link_add('/doc/security/csrf/', 'Cross site request forgery');
					$security_nav->link_add('/doc/security/framing/', 'Site framing'); // X-Frame-Options header
					$security_nav->link_add('/doc/security/transport/', 'Strict transport security');
					$security_nav->link_add('/doc/security/csp/', 'Content security policy');
					$security_nav->link_add('/doc/security/logins/', 'Login and passwords'); // Identification/verification, multiple sessions, failed logins, password hashing (slow), lost passwords
					$security_nav->link_add('/doc/security/files/', 'File uploads'); // Uploading a php file to a public location, or images containing exploits.

					$doc_nav->link_add('/doc/security/', 'Security', array('child' => $security_nav));

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

					$doc_nav->link_add('/doc/setup/', 'Setup', array('child' => $setup_nav));

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
					$helpers_nav->link_add('/doc/helpers/config/', 'Config');
					$helpers_nav->link_add('/doc/helpers/session/', 'Session');
					$helpers_nav->link_add('/doc/helpers/cookie/', 'Cookie');
					$helpers_nav->link_add('/doc/helpers/resources/', 'Resources');
					$helpers_nav->link_add('/doc/helpers/functions/', 'Functions');
					$helpers_nav->link_add('/doc/helpers/database/', 'Database');
					$helpers_nav->link_add('/doc/helpers/url/', 'URL');
					$helpers_nav->link_add('/doc/helpers/form/', 'Form', array('child' => $form_nav));
					$helpers_nav->link_add('/doc/helpers/email/', 'Email');
					$helpers_nav->link_add('/doc/helpers/file/', 'File');
					$helpers_nav->link_add('/doc/helpers/nav/', 'Navigation');
					$helpers_nav->link_add('/doc/helpers/table/', 'Table');
					$helpers_nav->link_add('/doc/helpers/paginator/', 'Paginator');
					$helpers_nav->link_add('/doc/helpers/user/', 'User');

					$doc_nav->link_add('/doc/helpers/', 'Helpers', array('child' => $helpers_nav));

				//--------------------------------------------------
				// Store

					$this->set('section_title', 'Documentation');
					$this->set('section_nav', $doc_nav);

			//--------------------------------------------------
			// Document HTML

				//--------------------------------------------------
				// Get text

					if (!is_file($doc_file_path)) {
						render_error('page-not-found');
						exit();
					}

					$doc_text = file_get_contents($doc_file_path);

				//--------------------------------------------------
				// Special cases

					if ($request_path == 'introduction') {

						$controller_example = file_get_contents(ROOT . '/app/controller/contact.php');
						$controller_example = preg_replace('/^/m', "\t", $controller_example);
						$controller_example = trim($controller_example);

						$doc_text = str_replace('<?php [SEE EXAMPLE] ?>', $controller_example, $doc_text);

					}

				//--------------------------------------------------
				// Conversion

					$cms_markdown = new cms_markdown();

					$doc_html = $cms_markdown->process_block_html($doc_text);

				//--------------------------------------------------
				// Special cases

					if ($request_path == 'introduction') {

						$replace = array(
							'/contact/' => '/<strong>contact</strong>/',
							'/app/view/contact.ctp' => '/app/view/<strong>contact</strong>.ctp',
							'contact_controller' => '<strong>contact</strong>_controller',
							'action_index' => 'action_<strong>index</strong>',
							'new form(' => 'new <strong>form</strong>(',
							'new email(' => 'new <strong>email</strong>(',
						);

						$doc_html = str_replace(array_keys($replace), array_values($replace), $doc_html);

					}

				//--------------------------------------------------
				// Save

					$this->set('doc_html', $doc_html);

			//--------------------------------------------------
			// Response

				$response = response_get();
				$response->page_id_set('p_doc');
				$response->view_path_set(VIEW_ROOT . '/doc.ctp');

		}

	}

?>