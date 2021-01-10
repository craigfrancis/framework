<?php

	class examples_browser_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Config

				$response = response_get();

				$search_term = 'Craig';

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Search');

				$field_search = new form_field_info($form, 'Search');
				$field_search->value_set($search_term);

			//--------------------------------------------------
			// Form submitted

				if ($form->submitted()) {

					//--------------------------------------------------
					// Validation



					//--------------------------------------------------
					// Form valid

						if ($form->valid()) {

							//--------------------------------------------------
							// Browser object

								$browser = new connection_browser();
								$browser->user_agent_set('Mozilla/5.0');

							//--------------------------------------------------
							// First page

								$browser->get('https://google.co.uk'); // Performs a redirect to www.google.co.uk

							//--------------------------------------------------
							// Search form

								$browser->form_select();
								$browser->form_field_set('q', $search_term);
								$browser->form_submit();

							//--------------------------------------------------
							// Follow link

								$query = '(//div[@role="main"]//a)[1]'; // If Google recognises UA as supporting JS, it won't link though /url gateway.
								$query = '(//a[contains(@href,"/url")])[1]';

								// debug($browser->nodes_get_html($query));

								$browser->link_follow($query);

							//--------------------------------------------------
							// Print

								config::set('debug.show', false);

								mime_set('text/plain');

								echo debug_dump($browser->url_get()) . "\n\n";
								echo debug_dump($browser->data_get());

								exit();

						}

				}

			//--------------------------------------------------
			// Form defaults

				if ($form->initial()) {
				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

		}

	}

?>