<?php

	class cms_blocks_text_base extends check {

		//--------------------------------------------------
		// Variables

			protected $block_id;
			protected $form;
			protected $field_text;

		//--------------------------------------------------
		// Setup

			public function __construct($block_id, $form = NULL) {
				$this->block_id = $block_id;
			}

			public function form_set($form) {
				$this->form = $form;
			}

			public function values_get() {

				$db = db_get();

				$sql = 'SELECT
							cbt.text
						FROM
							' . DB_PREFIX . 'cms_block_text AS cbt
						WHERE
							cbt.block_id = ? AND
							cbt.deleted = "0000-00-00 00:00:00"';

				$parameters = array();
				$parameters[] = array('i', $this->block_id);

				if ($row = $db->fetch_row($sql, $parameters)) {
					return array(
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
						$cms_markdown = new cms_markdown();
						return '
							' . $cms_markdown->process_block_html($values['text']);
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

					$this->field_text = new form_field_textarea($this->form, 'Text', 'block_text_' . $this->block_id);
					$this->field_text->max_length_set('The text cannot be longer than XXX characters.', 65000);
					$this->field_text->cols_set(40);
					$this->field_text->rows_set(5);
					$this->field_text->print_include_set(false);

				//--------------------------------------------------
				// Current value

					if (!$this->form->submitted()) {

						$values_new = NULL;

						if ($values_old !== NULL) {
							$this->field_text->value_set($values_old['text']);
						}

					} else {

						$values_new = array(
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
					<div class="row cms_block cms_block_edit cms_block_text">
						<span class="label">' . $this->field_text->html_label() . '</span>
						<span class="input">' . $this->field_text->html_input() . '</span>
					</div>';
			}

			public function form_save($info) {

				$db = db_get();

				$now = new timestamp();

				if ($info['values_save'] == 'update') {

					$sql = 'UPDATE
								' . DB_PREFIX . 'cms_block_text AS cbt
							SET
								cbt.deleted = ?
							WHERE
								cbt.block_id = ? AND
								cbt.deleted = "0000-00-00 00:00:00"';

					$parameters = array();
					$parameters[] = array('s', $now);
					$parameters[] = array('i', $this->block_id);

					$db->query($sql, $parameters);

				}

				$db->insert(DB_PREFIX . 'cms_block_text', array(
						'block_id' => $this->block_id,
						'text' => $info['values_new']['text'],
						'created' => $now,
						'deleted' => '0000-00-00 00:00:00',
					));

			}

	}

?>