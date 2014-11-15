<?php

	class model extends model_base {

		protected function setup($model_name, $config) {

			if (count($config['log_values']) > 0) {

				$config['log_table'] = DB_PREFIX . 'log';
				$config['log_values']['admin_id'] = ADMIN_ID;

				if (!isset($config['log_values']['item_type'])) {
					$config['log_values']['item_type'] = $model_name;
				}

			}

			parent::setup($model_name, $config);

		}

	}

?>