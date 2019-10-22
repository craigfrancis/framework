<?php

	class record extends record_base {

		protected function where_set_done($update) {

			$this->log_table_set_sql(DB_PREFIX . 'log', 'item_id', [
					'item_type' => $this->table_get_short(),
					'admin_id' => ADMIN_ID,
				]);

		}

	}

?>