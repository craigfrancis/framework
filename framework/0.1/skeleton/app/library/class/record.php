<?php

	class record extends record_base {

		protected function setup($config) {

			if ($config['where_id'] && !isset($config['log_values']['item_id'])) {
				$config['log_values']['item_id'] = $config['where_id'];
			}

			if (count($config['log_values']) > 0) {

				$config['log_table'] = DB_PREFIX . 'log';
				$config['log_values']['table'] = prefix_replace(DB_PREFIX, '', $config['table']);
				$config['log_values']['admin_id'] = ADMIN_ID;

			}

			parent::setup($config);

		}

	}

?>