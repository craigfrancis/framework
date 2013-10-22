<?php

/*--------------------------------------------------*/
/* Example usage

	//--------------------------------------------------
	// Edit form

		$cms_blocks = new cms_blocks('news', $article_id);
		$cms_blocks->form_setup($form);

		if ($form->submitted()) {
			if ($form->valid()) {
				$cms_blocks->form_save();
			}
		}

		$this->set('cms_blocks_html', $cms_blocks->form_html());

		echo $cms_blocks_html;

	//--------------------------------------------------
	// Output

		$cms_blocks = new cms_blocks('news', $article_id);

		$this->set('article_html', $cms_blocks->html());

	//--------------------------------------------------
	// SQL

		CREATE TABLE thr_cms_block (
			id int(11) NOT NULL AUTO_INCREMENT,
			parent_type varchar(30) NOT NULL,
			parent_id int(11) NOT NULL,
			sort int(11) NOT NULL,
			type varchar(30) NOT NULL,
			created datetime NOT NULL,
			edited datetime NOT NULL,
			deleted datetime NOT NULL,
			PRIMARY KEY (id),
			KEY page_type (parent_type,parent_id,deleted)
		);

		CREATE TABLE thr_cms_block_heading (
			block_id int(11) NOT NULL,
			level tinyint(4) NOT NULL,
			text tinytext NOT NULL,
			created datetime NOT NULL,
			deleted datetime NOT NULL,
			UNIQUE KEY block_id (block_id,deleted)
		);

		CREATE TABLE thr_cms_block_text (
			block_id int(11) NOT NULL,
			text text NOT NULL,
			created datetime NOT NULL,
			deleted datetime NOT NULL,
			UNIQUE KEY block_id (block_id,deleted)
		);

/*--------------------------------------------------*/

	class cms_blocks_base extends check {

		//--------------------------------------------------
		// Variables

			protected $parent_type = NULL;
			protected $parent_id = 0;
			protected $form = NULL;
			protected $block_types = array('heading', 'text');
			protected $fields = array();
			protected $field_objects = array();
			protected $field_add = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($parent_type, $parent_id) {
				$this->setup($parent_type, $parent_id);
			}

			protected function setup($parent_type, $parent_id) {

				//--------------------------------------------------
				// Parent

					$this->parent_type = $parent_type;
					$this->parent_id = $parent_id;

				//--------------------------------------------------
				// Tables

					if (config::get('debug.level') > 0) {

						debug_require_db_table(DB_PREFIX . 'cms_block', '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									parent_type varchar(30) NOT NULL,
									parent_id int(11) NOT NULL,
									sort int(11) NOT NULL,
									type varchar(30) NOT NULL,
									created datetime NOT NULL,
									edited datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id),
									KEY page_type (parent_type, parent_id, deleted)
								);');

					}

			}

		//--------------------------------------------------
		// Display support

			public function html() {

// TODO: Cache support, optional markdown in 'text' mode, javascript for drag/drop.

				$db = db_get();

				$html = '';

				$sql = 'SELECT
							cb.id,
							cb.type
						FROM
							' . DB_PREFIX . 'cms_block AS cb
						WHERE
							cb.parent_type = "' . $db->escape($this->parent_type) . '" AND
							cb.parent_id = "' . $db->escape($this->parent_id) . '" AND
							cb.deleted = "0000-00-00 00:00:00"
						ORDER BY
							cb.sort';

				foreach ($db->fetch_all($sql) as $row) {

					$class = 'cms_blocks_' . $row['type'];

					if (class_exists($class)) {
						$object = new $class($row['id']);
					} else {
						exit_with_error('Cannot find class "' . $class . '" for CMS Blocks.');
					}

					$html .= $object->html();

				}

				return $html;

			}

		//--------------------------------------------------
		// Form support (admin)

			public function form_setup($form) {

				//--------------------------------------------------
				// Resources

					$db = db_get();

					$this->form = $form;

				//--------------------------------------------------
				// Edit

					//--------------------------------------------------
					// Current fields

						$db_fields = array();

						$sql = 'SELECT
									cb.id,
									cb.type,
									cb.sort
								FROM
									' . DB_PREFIX . 'cms_block AS cb
								WHERE
									cb.parent_type = "' . $db->escape($this->parent_type) . '" AND
									cb.parent_id = "' . $db->escape($this->parent_id) . '" AND
									cb.deleted = "0000-00-00 00:00:00"
								ORDER BY
									cb.sort';

						foreach ($db->fetch_all($sql) as $row) {
							$db_fields[$row['id']] = array(
									'type' => $row['type'],
									'sort_old' => $row['sort'],
								);
						}

					//--------------------------------------------------
					// Fields in order

						$this->fields = array();

						if ($form->submitted()) {
							$field_order = explode('x', $form->hidden_value_get('cms-block-order'));
							foreach ($field_order as $id) {
								if (isset($db_fields[$id])) {
									$this->fields[$id] = $db_fields[$id];
									unset($db_fields[$id]);
								}
							}
						}

						foreach ($db_fields as $id => $type) {
							$this->fields[$id] = $type;
						}

						$form->hidden_value_set('cms-block-order', implode('x', array_keys($this->fields)));

					//--------------------------------------------------
					// Setup

						$sort = 1;

						foreach ($this->fields as $id => $info) {

							$info['sort_new'] = $sort++;

							$class = 'cms_blocks_' . $info['type'];

							if (class_exists($class)) {
								$object = new $class($id);
								$object->form_set($form);
							} else {
								exit_with_error('Cannot find class "' . $class . '" for CMS Blocks.');
							}

							$result = $object->form_setup();
							if (is_array($result)) {
								$this->fields[$id] = array_merge($info, $result);
							}

							$this->field_objects[$id] = $object; // Separate array for easier debug($this->fields);

						}

				//--------------------------------------------------
				// Add

					$this->field_add = new form_field_select($form, 'Add block');
					$this->field_add->label_option_set('');
					$this->field_add->options_set($this->block_types);
					$this->field_add->print_include_set(false);

				//--------------------------------------------------
				// Javascript

					$response = response_get();
					$response->js_add(gateway_url('framework-file', 'cms-blocks.js'));

			}

			public function form_html() {

				//--------------------------------------------------
				// HTML

					$html = '';

					foreach ($this->field_objects as $id => $object) {
						$html .= $object->form_html();
					}

					$html .= '
						<div class="row cms_block cms_block_add">
							<span class="label">' . $this->field_add->html_label() . '</span>
							<span class="input">' . $this->field_add->html_input() . '</span>
						</div>';

				//--------------------------------------------------
				// Return

					return $html;

			}

			public function form_save() {

				//--------------------------------------------------
				// Resources

					$db = db_get();

					$form = $this->form;

				//--------------------------------------------------
				// Edit blocks

					foreach ($this->fields as $id => $info) {

						//--------------------------------------------------
						// Save values

							$values_save = (isset($info['values_save']) ? $info['values_save'] : NULL);

							if ($values_save === NULL) {
								if (!isset($info['values_new']) || $info['values_new'] === NULL) {

									$values_save = false; // No new values, so cannot save

								} else if (!isset($info['values_old']) || $info['values_old'] === NULL) {

									$values_save = 'insert'; // No old value, so assume we are creating

								} else {

									foreach ($info['values_new'] as $field => $value) {
										if (!isset($info['values_old'][$field])) {

											exit_with_error('Cannot find new value for field "' . $field . '", on block "' . $id . '" (' . $info['type'] . ')');

										} else if ((strval($info['values_old'][$field]) !== strval($value))) {

											$values_save = 'update';

										}
									}

								}
							}

							if ($values_save) {
								$this->field_objects[$id]->form_save(array_merge($info, array('values_save' => $values_save)));
							}

						//--------------------------------------------------
						// Save sort order

							if ($info['sort_new'] != $info['sort_old']) {

								$db->query('UPDATE
												' . DB_PREFIX . 'cms_block AS cb
											SET
												cb.sort = "' . $db->escape($info['sort_new']) . '",
												cb.edited = "' . $db->escape(date('Y-m-d H:i:s')) . '"
											WHERE
												cb.id = "' . $db->escape($id) . '" AND
												cb.deleted = "0000-00-00 00:00:00"');

							}

					}

				//--------------------------------------------------
				// Add block

					$add_type = $this->field_add->value_get();
					if ($add_type != NULL) {

						$sql = 'SELECT
									MAX(cb.sort) AS sort
								FROM
									' . DB_PREFIX . 'cms_block AS cb
								WHERE
									cb.parent_type = "' . $db->escape($this->parent_type) . '" AND
									cb.parent_id = "' . $db->escape($this->parent_id) . '" AND
									cb.deleted = "0000-00-00 00:00:00"';

						if ($row = $db->fetch_row($sql)) {
							$add_sort = (intval($row['sort']) + 1);
						} else {
							$add_sort = 1;
						}

						$db->insert(DB_PREFIX . 'cms_block', array(
								'id' => '',
								'parent_type' => $this->parent_type,
								'parent_id' => $this->parent_id,
								'sort' => $add_sort,
								'type' => $add_type,
								'created' => date('Y-m-d H:i:s'),
								'edited' => date('Y-m-d H:i:s'),
								'deleted' => '0000-00-00 00:00:00',
							));

					}

			}

	}

?>