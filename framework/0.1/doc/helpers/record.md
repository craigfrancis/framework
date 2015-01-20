
# Record

When dealing with a **single** database record, stored in a table such as:

	CREATE TABLE prefix_table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			name tinytext NOT NULL,
			created datetime NOT NULL,
			edited datetime NOT NULL,
			deleted datetime NOT NULL,
			PRIMARY KEY (id)
		);

The record helper can be loaded with the function call:

	$record = record_get(DB_PREFIX . 'table_name', $item_id, array(
			'name',
		));

Or with a config array:

	$record = record_get(array(
			'table' => DB_PREFIX . 'table_name',
			'where_id' => $item_id,
			'fields' => array('name'),
			// 'deleted' => array('type' => 'record'),
			// 'log_table' => DB_PREFIX . 'log',
			// 'log_values' => array(
			// 		'item_type' => 'record',
			// 		'item_id' => $item_id,
			// 		'admin_id' => ADMIN_ID,
			// 	),
		));

You can then return the record values (or field information) with:

	debug($record->values_get());
	debug($record->value_get('name'));

	debug($record->fields_get());
	debug($record->field_get('name'));

This setup works really well with the form helper (described below).

---

## Deleted records

The record helper assumes that the table will have a `deleted` DATETIME field.

As NULL represent a missing record, this should default to "0000-00-00 00:00:00".

Then if set to a particular date/time, the user is automatically shown a 'deleted' page instead - this is done with the `error_send()` function.

This 'deleted' page can be customised by creating:

	/app/view/error/deleted.ctp

	<?php
		debug($type);
		debug($timestamp->format('jS F Y, \a\t g:ia'));
		debug($record['values']);
		debug($record['config']);
	?>

---

## Log table

If you want to record every edit that is made (e.g. for auditing purposes), then you can either specify the table and extra values every time:

	$record = record_get(array(
			// ...
			'log_table' => DB_PREFIX . 'log',
			'log_values' => array(
					'item_type' => 'record',
					'item_id' => $item_id,
					'admin_id' => ADMIN_ID,
				),
		));

Or extend the record class, for every record edited via this helper:

	/app/library/class/record.php

	<?php

		class record extends record_base {

			protected function setup($config) {

				if ($config['where_id'] && !isset($config['log_values']['item_id'])) {
					$config['log_values']['item_id'] = $config['where_id'];
				}

				if (count($config['log_values']) > 0) {

					$item_type = prefix_replace(DB_PREFIX, $config['table']);

					$config['log_table'] = DB_PREFIX . 'log';
					$config['log_values']['item_type'] = $item_type;
					$config['log_values']['admin_id'] = ADMIN_ID;

				}

				parent::setup($config);

			}

		}

	?>

Then create the 'log_table', such as:

	CREATE TABLE prefix_log (
			item_id int(11) NOT NULL,
			item_type varchar(50) NOT NULL,
			field varchar(50) NOT NULL,
			old_value text NOT NULL,
			new_value text NOT NULL,
			admin_id int(11) NOT NULL,
			created datetime NOT NULL,
			KEY item_id (item_id,item_type,field)
		) ;

A record will be added to this table every time a field is changed.

---

## Form helper

It plays well with the [form helper](../../doc/helpers/form.md), such as:

	//--------------------------------------------------
	// Details

		$action_edit = ($item_id != 0);

		$record = record_get(DB_PREFIX . 'table_name', $item_id, array(
				'name',
			));

		if ($action_edit) {

			if ($row = $record->values_get()) {

				$item_name = $row['name'];

			} else {

				exit_with_error('Cannot find record id "' . $item_id . '"');

			}

		}

	//--------------------------------------------------
	// Form setup

		$form = new form();
		$form->form_class_set('basic_form');
		$form->db_record_set($record);

		$field_name = new form_field_text($form, 'Name');
		$field_name->db_field_set('name');
		$field_name->min_length_set('The name is required.');
		$field_name->max_length_set('The name cannot be longer than XXX characters.');

	//--------------------------------------------------
	// Form submitted

		if ($form->submitted()) {

			//--------------------------------------------------
			// Validation



			//--------------------------------------------------
			// Form valid

				if ($form->valid()) {

					//--------------------------------------------------
					// Save

						if ($action_edit) {
							$form->db_save();
						} else {
							$item_id = $form->db_insert();
						}

					//--------------------------------------------------
					// Next page

						$form->dest_redirect(url(array('id' => $item_id)));

				}

		}
