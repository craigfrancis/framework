<?php

	class cms_blocks_heading_base extends check {

		//--------------------------------------------------
		// Variables

			protected $block_id;
			protected $form;
			protected $field_text;
			protected $field_level;

		//--------------------------------------------------
		// Setup

			public function __construct($block_id) {
				$this->block_id = $block_id;
			}

			public function form_set($form) {
				$this->form = $form;
			}

			public function values_get() {

				$db = db_get();

				$sql = 'SELECT
							cbh.level,
							cbh.text
						FROM
							' . DB_PREFIX . 'cms_block_heading AS cbh
						WHERE
							cbh.block_id = ? AND
							cbh.deleted = "0000-00-00 00:00:00"';

				$parameters = [];
				$parameters[] = ['i', $this->block_id];

				if ($row = $db->fetch_row($sql, $parameters)) {
					return array(
							'level' => $row['level'],
							'text' => $row['text'],
						);
				} else {
					return NULL;
				}

			}

		//--------------------------------------------------
		// Display mode

			public function html() {

				//--------------------------------------------------
				// Current value

					$values = $this->values_get();

				//--------------------------------------------------
				// Return html

					if ($values) {
						$heading = 'h' . ($values['level'] + 1);
						return '
							<' . html($heading) . '>' . nl2br(html($values['text'])) . '</' . html($heading) . '>';
					}

			}

		//--------------------------------------------------
		// Form mode

			public function form_setup() {

				//--------------------------------------------------
				// Current value

					$values_old = $this->values_get();

				//--------------------------------------------------
				// Field

					$this->field_text = new form_field_text($this->form, 'Heading', 'block_text_' . $this->block_id);
					$this->field_text->max_length_set('The heading cannot be longer than XXX characters.', 250);
					$this->field_text->print_include_set(false);

					$this->field_level = new form_field_select($this->form, 'Level', 'block_level_' . $this->block_id);
					$this->field_level->options_set(array(1, 2, 3));
					$this->field_level->print_include_set(false);

				//--------------------------------------------------
				// Current value

					if (!$this->form->submitted()) {

						$values_new = NULL;

						if ($values_old !== NULL) {
							$this->field_level->value_set($values_old['level']);
							$this->field_text->value_set($values_old['text']);
						}

					} else {

						$values_new = array(
								'level' => $this->field_level->value_get(),
								'text' => $this->field_text->value_get(),
							);

					}

				//--------------------------------------------------
				// Return

					return array(
							'values_old' => $values_old,
							'values_new' => $values_new,
							'values_save' => NULL, // Auto decide
						);

			}

			public function form_html() {
				return '
					<div class="row cms_block cms_block_edit cms_block_heading">
						<span class="label">' . $this->field_text->html_label() . '</span>
						<span class="input">' . $this->field_text->html_input() . '</span>
						<span class="info">
							' . $this->field_level->html_label() . '
							' . $this->field_level->html_input() . '
						</span>
					</div>';
			}

			public function form_save($info) {

				$db = db_get();

				$now = new timestamp();

				if ($info['values_save'] == 'update') {

					$sql = 'UPDATE
									' . DB_PREFIX . 'cms_block_heading AS cbh
							SET
								cbh.deleted = ?
							WHERE
								cbh.block_id = ? AND
								cbh.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = ['s', $now];
					$parameters[] = ['i', $this->block_id];

					$db->query($sql, $parameters);

				}

				$db->insert(DB_PREFIX . 'cms_block_heading', array(
						'block_id' => $this->block_id,
						'level' => $info['values_new']['level'],
						'text' => $info['values_new']['text'],
						'created' => $now,
						'deleted' => '0000-00-00 00:00:00',
					));

			}

	}

?>