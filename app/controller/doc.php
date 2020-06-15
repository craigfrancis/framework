<?php

	class doc_controller extends controller {

		public function route() {

			//--------------------------------------------------
			// Resources

				$response = response_get();

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

				$doc_file_path = realpath($doc_root_path . $request_path . '.md');

			//--------------------------------------------------
			// Document HTML

				//--------------------------------------------------
				// Get text

					if (!is_file($doc_file_path)) {
						error_send('page-not-found');
						exit();
					}

					$doc_text = file_get_contents($doc_file_path);

				//--------------------------------------------------
				// Form example

					if ($request_path == 'introduction' || $request_path == 'setup/units') {

						$controller_example = file_get_contents(ROOT . '/app/unit/contact/contact-form.php');
						$controller_example = preg_replace('/^/m', "\t", $controller_example);
						$controller_example = trim($controller_example);

						$doc_text = str_replace('<?php [SEE EXAMPLE] ?>', $controller_example, $doc_text);

					}

					$doc_text = preg_replace('/\(((..\/)+doc\/.*?).md(#[a-z_]+)?\)/', '(../$1/$3)', $doc_text);

				//--------------------------------------------------
				// Conversion

					$cms_markdown = new cms_markdown();

					$doc_html = $cms_markdown->process_block_html($doc_text);

				//--------------------------------------------------
				// Special cases

					$replace = NULL;

					if ($request_path == 'introduction') {

						$replace = array(
							'/contact/' => '/<strong>contact</strong>/',
							'/app/view/contact.ctp' => '/app/view/<strong>contact</strong>.ctp',
							'contact_controller' => '<strong>contact</strong>_controller',
							'action_index' => 'action_<strong>index</strong>',
							'\'contact_form\'' => '\'<strong>contact_form</strong>\'',
							'new form(' => 'new <strong>form</strong>(',
							'new email(' => 'new <strong>email</strong>(',
						);

					} else if ($request_path == 'helpers/functions') {

						$replace = array(
							'table helper' => '<a href="/doc/helpers/table/">table helper</a>',
							'output.currency_char' => '<a href="/doc/setup/config/">output.currency_char</a>',
							'output.protocols' => '<a href="/doc/setup/config/">output.protocols</a>',
							'email.check_domain' => '<a href="/doc/setup/config/">email.check_domain</a>',
						);

					} else if ($request_path == 'helpers/url') {

						$replace = array(
							'output.protocols' => '<a href="/doc/setup/config/">output.protocols</a>',
						);

					} else if ($request_path == 'helpers/cms-text') {

						$replace = array(
							'cms.profile.name' => 'cms.<strong>profile</strong>.name',
						);

					} else if ($request_path == 'helpers/nav') {

						$replace = array(
							'$sub_nav' => '<strong>$sub_nav</strong>',
						);

					} else if ($request_path == 'helpers/file') {

						$replace = array(
							'$file' => '<strong>$file</strong>',
							'item_name' => '<strong>item_name</strong>',
							'123' => '<strong>123</strong>',
						);

					}

					if ($replace) {
						$doc_html = str_replace(array_keys($replace), array_values($replace), $doc_html);
					}

				//--------------------------------------------------
				// Save

					$response->set('doc_html', $doc_html);

			//--------------------------------------------------
			// Response

				$response->page_id_set('p_doc');
				$response->set('page_class', str_replace('/', '_', $request_path));
				$response->view_path_set(VIEW_ROOT . '/doc.ctp');

		}

	}

?>