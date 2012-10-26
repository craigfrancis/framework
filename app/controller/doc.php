<?php

	class doc_controller extends controller {

		public function route() {

			//--------------------------------------------------
			// Config

				$doc_root_path = VIEW_ROOT . '/doc/';

			//--------------------------------------------------
			// Requested page

				$request_path = config::get('request.path');

				if ($request_path == '/doc/') {
					redirect(url('/doc/introduction/'));
				}

				if (substr($request_path, 0, 5)  == '/doc/') $request_path = substr($request_path, 5);
				if (substr($request_path, -1)    == '/')     $request_path = substr($request_path, 0, -1);

				$doc_file_path = realpath($doc_root_path . $request_path . '.txt');

				if (!is_file($doc_file_path)) {
					render_error('page-not-found');
				}

			//--------------------------------------------------
			// Convert to HTML

				$doc_text = file_get_contents($doc_file_path);

				$cms_markdown = new cms_markdown();

				$doc_html = $cms_markdown->process_html($doc_text);

				$this->set('doc_html', $doc_html);

			//--------------------------------------------------
			// View

				$this->page_ref_set('p_doc');
				$this->view_path_set(VIEW_ROOT . '/doc.ctp');

		}

	}

?>