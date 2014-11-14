<?php

	class examples_model_controller extends controller {

		public function action_index() {

			if (SERVER != 'stage') {
				exit('Disabled');
			}

			config::set('debug.show', false);
			mime_set('text/plain');

			$model = model_get('form_fields', array(
					'fields' => array(
							'id',
							'type_tinyint',
							'type_smallint',
							'type_mediumint',
							'type_int',
							'type_bigint',
							'type_char5',
							'type_varchar5',
							'type_binary5',
							'type_varbinary5',
							'type_decimal10_2',
							'type_tinytext',
							'type_tinyblob',
							'type_text',
							'type_blob',
							'type_mediumtext',
							'type_mediumblob',
							'type_longtext',
							'type_longblob',
							'type_date',
							'type_datetime',
							'type_time',
							'type_year',
							'type_timestamp',
							'type_bool',
							'type_float',
							'type_double',
							'type_enum',
							'type_set',
						),
					'where_sql' => '
						id = 1'
				));


			debug($model->fetch_fields());
			debug($model->fetch_field('type_tinyint'));
			debug($model->fetch_values());
			debug($model->fetch_value('type_tinyint'));

			$db = db_get();
			$old = array();
			foreach ($db->fetch_fields(DB_PREFIX . 'form_fields') as $field_name => $field_info) {
				unset($field_info['default']);
				unset($field_info['extra']);
				unset($field_info['options']);
				unset($field_info['definition']);
				$old[$field_name] = $field_info;
			}

			$changes = array();
			foreach ($model->fetch_fields() as $field_name => $field_info) {
				foreach ($field_info as $info_name => $info_value) {
					if ($old[$field_name][$info_name] != $info_value) {
						$changes[$field_name][$info_name] = array($old[$field_name][$info_name], $info_value);
					}
				}
			}
			debug($changes);

			exit();

		}

	}

?>