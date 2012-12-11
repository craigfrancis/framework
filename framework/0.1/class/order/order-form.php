<?php

	class order_form_base extends form {

		protected $order;

		public function order_ref_set($order) {
			$this->order = $order;
		}

		public function field_delivery_different_get() {
			$field_delivery_different = new form_field_check_box($this, 'Delivery different');
			$field_delivery_different->db_field_set('delivery_different');
			$field_delivery_different->text_values_set('true', 'false');
			$field_delivery_different->input_first_set(true);
			return $field_delivery_different;
		}

		protected function _field_create($ref, $config) {

			//--------------------------------------------------
			// Config

				$defaults = array(
						'name' => ref_to_human($ref),
						'required' => true,
						'input_class' => NULL,
					);

				if (!is_array($config)) {
					if (is_string($config)) {
						$config = array('name' => $config);
					} else {
						$config = array();
					}
				}

				$config = array_merge($defaults, $config);

			//--------------------------------------------------
			// Field

				$field = new form_field_text($this, $config['name']);
				$field->db_field_set($ref);

				if ($config['input_class']) {
					$field->input_class_set($config['input_class']);
				}

				if ($config['required']) {
					$field->min_length_set('Your ' . strtolower($config['name']) . ' is required.');
				}

				$field->max_length_set('Your ' . strtolower($config['name']) . ' cannot be longer than XXX characters.');

				return $field;

		}

	}

?>