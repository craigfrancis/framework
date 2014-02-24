<?php

	class examples_browser_controller extends controller {

		public function action_index() {

			//--------------------------------------------------
			// Disabled

				if (version_compare(PHP_VERSION, '5.2.0', '<')) {
					return;
				}

			//--------------------------------------------------
			// Resources

				$response = response_get();

			//--------------------------------------------------
			// Form setup

				$form = new form();
				$form->form_class_set('basic_form');
				$form->form_button_set('Search');

				$field_search = new form_field_text($form, 'Search');
				$field_search->min_length_set('Your search is required.');
				$field_search->max_length_set('Your search cannot be longer than XXX characters.', 250);

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

								$browser = new socket_browser();
								$browser->user_agent_set('Mozilla/5.0');

							//--------------------------------------------------
							// First page

								$browser->get('http://google.co.uk'); // Performs a redirect to www.google.co.uk

							//--------------------------------------------------
							// Search form

								$browser->form_select();

								$browser->form_field_set('q', $field_search->value_get());

								$browser->form_submit();

							//--------------------------------------------------
							// Follow link

								$query = '(//div[@role="main"]//a)[1]'; // If Google recognises UA as supporting JS, it won't link though /url gateway.
								$query = '(//a[contains(@href,"/url")])[1]';

								// debug($browser->nodes_get_html($query));

								$browser->link_follow($query);

							//--------------------------------------------------
							// Print

								debug($browser->url_get());
								debug($browser->data_get());

								exit();

						}

				}

			//--------------------------------------------------
			// Form defaults

				if ($form->initial()) {
					$field_search->value_set('Craig Francis');
				}

			//--------------------------------------------------
			// Variables

				$response->set('form', $form);

		}

	}

?>