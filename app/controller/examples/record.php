<?php

	class examples_record_controller extends controller {

		public function action_index() {

			if (SERVER != 'stage') {
				exit('Disabled');
			}

			config::set('debug.show', false);

			mime_set('text/plain');

			$record = record_get(DB_PREFIX . 'form_fields', 1, array(
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
					'type_bool',
					'type_float',
					'type_double',
					'type_enum',
					'type_set',
				));

			echo debug_dump($record->fields_get()) . "\n";
			echo debug_dump($record->field_get('type_tinyint')) . "\n";
			echo debug_dump($record->values_get()) . "\n";
			echo debug_dump($record->value_get('type_tinyint')) . "\n";

			exit();

		}

	}

?>