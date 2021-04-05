<?php

	class order_form_base extends form {

		//--------------------------------------------------
		// Variables

			protected $order_obj;
			protected $postcode_format = 'uk';
			protected $country_table = NULL;
			protected $country_options = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct($order) {
				$this->order_obj = $order; // So the form can call $this->db_get() during setup (ref `system_form_dedupe`).
				parent::__construct();
			}

			public function init() {
			}

			public function db_get() {
				return $this->order_obj->db_get();
			}

			public function country_options_get() {
				if ($this->country_table) {

					if ($this->country_options) {
						return $this->country_options;
					}

					$this->country_options = [];

					$db = $this->db_get();

					$sql = 'SELECT
								c.iso_2 AS id,
								c.name AS name
							FROM
								' . $this->country_table . ' AS c
							ORDER BY
								name';

					foreach ($db->fetch_all($sql) as $row) {
						$this->country_options[$row['id']] = $row['name'];
					}

					return $this->country_options;

				}
				return NULL;
			}

		//--------------------------------------------------
		// Email

			public function field_email_get() {
				$field_email = new form_field_email($this, 'Email');
				$field_email->db_field_set('email');
				$field_email->format_error_set('Your email does not appear to be correct.');
				$field_email->min_length_set('Your email is required.');
				$field_email->max_length_set('Your email cannot be longer than XXX characters.');
				return $field_email;
			}

		//--------------------------------------------------
		// Payment fields

				//--------------------------------------------------
				// Ref: https://www.whatwg.org/specs/web-apps/current-work/multipage/association-of-controls-and-forms.html#attr-fe-autocomplete-street-address
				//--------------------------------------------------

			public function field_payment_name_get() {
				$field_payment_name = new form_field_text($this, 'Name');
				$field_payment_name->db_field_set('payment_name');
				$field_payment_name->wrapper_class_add('payment required');
				$field_payment_name->autocomplete_set('billing name');
				$field_payment_name->min_length_set('Your payment name is required.');
				$field_payment_name->max_length_set('Your payment name cannot be longer than XXX characters.');
				return $field_payment_name;
			}

			public function field_payment_address_1_get() {
				$field_payment_address_1 = new form_field_text($this, 'Address line 1');
				$field_payment_address_1->db_field_set('payment_address_1');
				$field_payment_address_1->wrapper_class_add('payment required');
				$field_payment_address_1->autocomplete_set('billing address-line1');
				$field_payment_address_1->min_length_set('Your payment address line 1 is required.');
				$field_payment_address_1->max_length_set('Your payment address line 1 cannot be longer than XXX characters.');
				return $field_payment_address_1;
			}

			public function field_payment_address_2_get() {
				$field_payment_address_2 = new form_field_text($this, 'Address line 2');
				$field_payment_address_2->db_field_set('payment_address_2');
				$field_payment_address_2->wrapper_class_add('payment');
				$field_payment_address_2->autocomplete_set('billing address-line2');
				$field_payment_address_2->max_length_set('Your payment address line 2 cannot be longer than XXX characters.');
				return $field_payment_address_2;
			}

			public function field_payment_address_3_get() {
				$field_payment_address_3 = new form_field_text($this, 'Address line 3');
				$field_payment_address_3->db_field_set('payment_address_3');
				$field_payment_address_3->wrapper_class_add('payment');
				$field_payment_address_3->autocomplete_set('billing address-line3');
				$field_payment_address_3->max_length_set('Your payment address line 3 cannot be longer than XXX characters.');
				return $field_payment_address_3;
			}

			public function field_payment_town_city_get() {
				$field_payment_town_city = new form_field_text($this, 'Town or city');
				$field_payment_town_city->db_field_set('payment_town_city');
				$field_payment_town_city->wrapper_class_add('payment required');
				$field_payment_town_city->autocomplete_set('billing address-level2');
				$field_payment_town_city->min_length_set('Your payment town or city is required.');
				$field_payment_town_city->max_length_set('Your payment town or city cannot be longer than XXX characters.');
				return $field_payment_town_city;
			}

			public function field_payment_region_get() {
				$field_payment_region = new form_field_text($this, 'County or state');
				$field_payment_region->db_field_set('payment_region');
				$field_payment_region->wrapper_class_add('payment required');
				$field_payment_region->autocomplete_set('billing address-level1');
				$field_payment_region->min_length_set('Your payment county or state is required.');
				$field_payment_region->max_length_set('Your payment county or state cannot be longer than XXX characters.');
				return $field_payment_region;
			}

			public function field_payment_postcode_get() {
				if ($this->postcode_format !== NULL) {
					$field_payment_postcode = new form_field_postcode($this, 'Postcode');
					$field_payment_postcode->db_field_set('payment_postcode');
					$field_payment_postcode->format_error_set('Your payment postcode does not appear to be correct.');
				} else {
					$field_payment_postcode = new form_field_text($this, 'Postcode');
					$field_payment_postcode->db_field_set('payment_postcode');
					$field_payment_postcode->max_length_set('Your payment postcode cannot be longer than XXX characters.');
				}
				$field_payment_postcode->wrapper_class_add('payment required');
				$field_payment_postcode->autocomplete_set('billing postal-code');
				$field_payment_postcode->required_error_set('Your payment postcode is required.');
				return $field_payment_postcode;
			}

			public function field_payment_country_get() {
				$countries = $this->country_options_get();
				if ($countries) {
					$field_payment_country = new form_field_select($this, 'Payment country');
					$field_payment_country->db_field_set('payment_country', 'key');
					$field_payment_country->options_set($countries);
					$field_payment_country->label_option_set('');
					$field_payment_country->required_error_set('Your payment country is required.');
				} else {
					$field_payment_country = new form_field_text($this, 'Country');
					$field_payment_country->db_field_set('payment_country');
					$field_payment_country->wrapper_class_add('payment required');
					$field_payment_country->autocomplete_set('billing country');
					$field_payment_country->min_length_set('Your payment country is required.');
					$field_payment_country->max_length_set('Your payment country cannot be longer than XXX characters.');
				}
				return $field_payment_country;
			}

			public function field_payment_telephone_get() {
				$field_payment_telephone = new form_field_text($this, 'Telephone');
				$field_payment_telephone->db_field_set('payment_telephone');
				$field_payment_telephone->wrapper_class_add('payment required');
				$field_payment_telephone->autocomplete_set('billing tel');
				$field_payment_telephone->min_length_set('Your payment telephone number is required.');
				$field_payment_telephone->max_length_set('Your payment telephone number cannot be longer than XXX characters.');
				return $field_payment_telephone;
			}

		//--------------------------------------------------
		// Delivery fields

			public function field_delivery_different_get() {
				$field_delivery_different = new form_field_checkbox($this, 'Delivery different');
				$field_delivery_different->db_field_set('delivery_different');
				$field_delivery_different->wrapper_class_add('delivery required');
				$field_delivery_different->text_values_set('true', 'false');
				$field_delivery_different->input_first_set(true);
				return $field_delivery_different;
			}

			public function field_delivery_name_get() {

				$field_delivery_name = new form_field_text($this, 'Name');
				$field_delivery_name->db_field_set('delivery_name');
				$field_delivery_name->wrapper_class_add('delivery required');
				$field_delivery_name->autocomplete_set('shipping name');
				$field_delivery_name->max_length_set('Your delivery name cannot be longer than XXX characters.');

				if (!$this->field_exists('delivery_different')) {
					$field_delivery_name->min_length_set('Your delivery name is required.');
				}

				return $field_delivery_name;
			}

			public function field_delivery_address_1_get() {

				$field_delivery_address_1 = new form_field_text($this, 'Address line 1');
				$field_delivery_address_1->db_field_set('delivery_address_1');
				$field_delivery_address_1->wrapper_class_add('delivery required');
				$field_delivery_address_1->autocomplete_set('shipping address-line1');
				$field_delivery_address_1->max_length_set('Your delivery address line 1 cannot be longer than XXX characters.');

				if (!$this->field_exists('delivery_different')) {
					$field_delivery_address_1->min_length_set('Your delivery address line 1 is required.');
				}

				return $field_delivery_address_1;
			}

			public function field_delivery_address_2_get() {
				$field_delivery_address_2 = new form_field_text($this, 'Address line 2');
				$field_delivery_address_2->db_field_set('delivery_address_2');
				$field_delivery_address_2->wrapper_class_add('delivery');
				$field_delivery_address_2->autocomplete_set('shipping address-line2');
				$field_delivery_address_2->max_length_set('Your delivery address line 2 cannot be longer than XXX characters.');
				return $field_delivery_address_2;
			}

			public function field_delivery_address_3_get() {
				$field_delivery_address_3 = new form_field_text($this, 'Address line 3');
				$field_delivery_address_3->db_field_set('delivery_address_3');
				$field_delivery_address_3->wrapper_class_add('delivery');
				$field_delivery_address_3->autocomplete_set('shipping address-line3');
				$field_delivery_address_3->max_length_set('Your delivery address line 3 cannot be longer than XXX characters.');
				return $field_delivery_address_3;
			}

			public function field_delivery_town_city_get() {

				$field_delivery_town_city = new form_field_text($this, 'Town or city');
				$field_delivery_town_city->db_field_set('delivery_town_city');
				$field_delivery_town_city->wrapper_class_add('delivery required');
				$field_delivery_town_city->autocomplete_set('shipping address-level2');
				$field_delivery_town_city->max_length_set('Your delivery town or city cannot be longer than XXX characters.');

				if (!$this->field_exists('delivery_different')) {
					$field_delivery_town_city->min_length_set('Your delivery town or city is required.');
				}

				return $field_delivery_town_city;
			}

			public function field_delivery_region_get() {

				$field_delivery_region = new form_field_text($this, 'County or state');
				$field_delivery_region->db_field_set('delivery_region');
				$field_delivery_region->wrapper_class_add('delivery required');
				$field_delivery_region->autocomplete_set('shipping address-level1');
				$field_delivery_region->max_length_set('Your delivery county or state cannot be longer than XXX characters.');

				if (!$this->field_exists('delivery_different')) {
					$field_delivery_region->min_length_set('Your delivery county or state is required.');
				}

				return $field_delivery_region;
			}

			public function field_delivery_postcode_get() {

				if ($this->postcode_format !== NULL) {
					$field_delivery_postcode = new form_field_postcode($this, 'Postcode');
					$field_delivery_postcode->db_field_set('delivery_postcode');
					$field_delivery_postcode->format_error_set('Your delivery postcode does not appear to be correct.');
				} else {
					$field_delivery_postcode = new form_field_text($this, 'Postcode');
					$field_delivery_postcode->db_field_set('delivery_postcode');
					$field_delivery_postcode->max_length_set('Your delivery postcode cannot be longer than XXX characters.');
				}

				$field_delivery_postcode->wrapper_class_add('delivery required');
				$field_delivery_postcode->autocomplete_set('shipping postal-code');

				if (!$this->field_exists('delivery_different')) {
					$field_delivery_postcode->min_length_set('Your delivery postcode is required.');
				}

				return $field_delivery_postcode;

			}

			public function field_delivery_country_get() {
				$countries = $this->country_options_get();
				if ($countries) {

					$field_delivery_country = new form_field_select($this, 'Payment country');
					$field_delivery_country->db_field_set('delivery_country', 'key');
					$field_delivery_country->autocomplete_set('shipping country');
					$field_delivery_country->options_set($countries);
					$field_delivery_country->label_option_set('');

					if (!$this->field_exists('delivery_different')) {
						$field_delivery_country->required_error_set('Your delivery country is required.');
					}

				} else {

					$field_delivery_country = new form_field_text($this, 'Country');
					$field_delivery_country->db_field_set('delivery_country');
					$field_delivery_country->wrapper_class_add('delivery required');
					$field_delivery_country->autocomplete_set('shipping country');
					$field_delivery_country->max_length_set('Your delivery country cannot be longer than XXX characters.');

					if (!$this->field_exists('delivery_different')) {
						$field_delivery_country->min_length_set('Your delivery country is required.');
					}

				}
				return $field_delivery_country;
			}

			public function field_delivery_telephone_get() {

				$field_delivery_telephone = new form_field_text($this, 'Telephone');
				$field_delivery_telephone->db_field_set('delivery_telephone');
				$field_delivery_telephone->wrapper_class_add('delivery required');
				$field_delivery_telephone->autocomplete_set('shipping tel');
				$field_delivery_telephone->max_length_set('Your delivery telephone number cannot be longer than XXX characters.');

				if (!$this->field_exists('delivery_different')) {
					$field_delivery_telephone->min_length_set('Your delivery telephone number is required.');
				}

				return $field_delivery_telephone;
			}

		//--------------------------------------------------
		// Generic text field

			protected function _field_create($ref, $config) {

				//--------------------------------------------------
				// Name

					if (is_string($config)) {
						$name = $config;
					} else {
						$name = ref_to_human($ref);
					}

				//--------------------------------------------------
				// Field

					$field = new form_field_text($this, $name);
					$field->db_field_set($ref);
					$field->max_length_set('Your ' . strtolower($name) . ' cannot be longer than XXX characters.');

					return $field;

			}

	}

?>