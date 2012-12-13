<?php

/***************************************************

	//--------------------------------------------------
	// Site config



	//--------------------------------------------------
	// Example setup

		$order = new order();
		$order->select_open();

	//--------------------------------------------------
	// Item count - quick summary for a basket count

		echo $order->items_count_get();

	//--------------------------------------------------
	// Add an item

		$order = new order();
		$order->select_open();

		$order->item_add(array(
				'item_id' => $id,
				'item_code' => $code,
				'item_name' => $name,
				'price' => $price,
			));

	//--------------------------------------------------
	// Edit basket with 'delete' links (CSRF issue)

		$order->items_update();

		$table_html = $order->table_get_html(array(
				'quantity_edit' => 'link',
			));

	//--------------------------------------------------
	// Edit basket with 'quantity' select fields

		//--------------------------------------------------
		// Controller

			$form = new form();

			if ($form->submitted() && $form->valid()) {

				$order->items_update();

				if (strtolower(trim(request('button'))) == 'update totals') {
					redirect(http_url('/basket/'));
				} else {
					redirect(http_url('/basket/checkout/'));
				}

			}

			$table_html = $order->table_get_html(array(
					'quantity_edit' => array('type' => 'select'),
				));

			$this->set('form', $form);
			$this->set('table_html', $table_html);
			$this->set('empty_basket', ($order->items_count_get() == 0));

		//--------------------------------------------------
		// View

			<?= $form->html_start(); ?>
				<fieldset>

					<?= $form->html_error_list(); ?>

					<?= $order_table_html; ?>

					<?php if (!$empty_basket) { ?>

						<div class="submit">
							<input type="submit" name="button" value="Update totals" />
							<input type="submit" name="button" value="Checkout" />
						</div>

					<?php } ?>

				</fieldset>
			<?= $form->html_end(); ?>

	//--------------------------------------------------
	// Checkout page

		$order = new order();

		if (!$order->select_open()) {
			redirect(http_url('/basket/'));
		}

		$form = $order->form_get();
		$form->form_class_set('basic_form');
		$form->form_button_set('Continue');

		$form->print_group_start('Payment details');
		$form->field_get('payment_name');
		$form->field_get('payment_address_1');
		$form->field_get('payment_address_2');
		$form->field_get('payment_address_3');
		$form->field_get('payment_town_city');
		$form->field_get('payment_postcode');
		$form->field_get('payment_country');
		$form->field_get('payment_telephone');

		$form->print_group_start('Delivery details');
		$form->field_get('delivery_different');
		$form->field_get('delivery_name');
		$form->field_get('delivery_address_1');
		$form->field_get('delivery_address_2');
		$form->field_get('delivery_address_3');
		$form->field_get('delivery_town_city');
		$form->field_get('delivery_postcode');
		$form->field_get('delivery_country');
		$form->field_get('delivery_telephone');

		if ($form->submitted()) {

			$result = $order->save();

			if ($result) {
				redirect(http_url('/basket/payment/'));
			}

		} else {

			// Defaults

		}

		$this->set('form', $form);

***************************************************/

	class order_base extends check {

		//--------------------------------------------------
		// Variables

			protected $order_id = NULL;
			protected $order_pass = NULL;
			protected $order_created = NULL;
			protected $order_paid = NULL;
			protected $order_currency = 'GBP';
			protected $order_items = NULL; // Cache

			protected $db_table_main = NULL;
			protected $db_table_item = NULL;

			protected $db_link = NULL;
			protected $table = NULL;
			protected $form = NULL;

			protected $object_table = 'order_table';
			protected $object_payment = 'payment';

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->setup();
			}

			protected function setup() {

				//--------------------------------------------------
				// Tables

					if ($this->db_table_main === NULL) $this->db_table_main = DB_PREFIX . 'order';
					if ($this->db_table_item === NULL) $this->db_table_item = DB_PREFIX . 'order_item';

					if (config::get('debug.level') > 0) {

						debug_require_db_table($this->db_table_main, '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									pass tinytext NOT NULL,
									ip tinytext NOT NULL,
									email varchar(100) NOT NULL,
									created datetime NOT NULL,
									edited datetime NOT NULL,
									payment_received datetime NOT NULL,
									payment_settled datetime NOT NULL,
									payment_vat float NOT NULL,
									payment_name tinytext NOT NULL,
									payment_address_1 tinytext NOT NULL,
									payment_address_2 tinytext NOT NULL,
									payment_address_3 tinytext NOT NULL,
									payment_town_city tinytext NOT NULL,
									payment_postcode tinytext NOT NULL,
									payment_country tinytext NOT NULL,
									payment_telephone tinytext NOT NULL,
									delivery_different enum(\'false\',\'true\') NOT NULL,
									delivery_name tinytext NOT NULL,
									delivery_address_1 tinytext NOT NULL,
									delivery_address_2 tinytext NOT NULL,
									delivery_address_3 tinytext NOT NULL,
									delivery_town_city tinytext NOT NULL,
									delivery_postcode tinytext NOT NULL,
									delivery_country tinytext NOT NULL,
									delivery_telephone tinytext NOT NULL,
									dispatched datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id)
								);');

						debug_require_db_table($this->db_table_item, '
								CREATE TABLE [TABLE] (
									id int(11) NOT NULL AUTO_INCREMENT,
									order_id int(11) NOT NULL,
									type enum(\'item\',\'voucher\',\'discount\',\'delivery\') NOT NULL,
									item_id int(11) NOT NULL,
									item_code varchar(30) NOT NULL,
									item_name tinytext NOT NULL,
									price decimal(10,2) NOT NULL,
									quantity int(11) NOT NULL,
									created datetime NOT NULL,
									deleted datetime NOT NULL,
									PRIMARY KEY (id),
									KEY order_id (order_id)
								);');

					}

			}

		//--------------------------------------------------
		// Configuration

			public function id_get() {
				return $this->order_id;
			}

			public function ref_get() {
				if ($this->order_id === NULL) {
					return NULL;
				} else {
					return $this->order_id . '-' . $this->order_pass;
				}
			}

			public function user_privileged_get() {
				return false; // Function could return true if admin (used to bypass the "pass" check in select_by_id())
			}

			public function db_get() { // Public so order table can access
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

			protected function table_get() {
				if ($this->table === NULL) {
					$this->table = new $this->object_table($this);
				}
				return $this->table;
			}

			public function form_get() {
				if ($this->form === NULL) {

					$db = $this->db_get();

					$this->form = new order_form();
					$this->form->order_ref_set($this);
					$this->form->db_set($this->db_get());
					$this->form->db_save_disable();
					$this->form->db_table_set_sql($db->escape_field($this->db_table_main));

					if ($this->order_id > 0) {

						$where_sql = '
							id = "' . $db->escape($this->order_id) . '" AND
							deleted = "0000-00-00 00:00:00"';

						$this->form->db_where_set_sql($where_sql);

					}

				}
				return $this->form;
			}

		//--------------------------------------------------
		// Reset

			public function reset() {

				$this->order_id = NULL;
				$this->order_pass = NULL;
				$this->order_created = NULL;
				$this->order_paid = NULL;

			}

		//--------------------------------------------------
		// Select

			public function selected() {
				return ($this->order_id !== NULL);
			}

			public function select_open() {

				$this->select_by_ref(session::get('order_ref'));

				if ($this->order_paid != '0000-00-00 00:00:00') {
					$this->reset();
				}

				return ($this->order_id !== NULL);

			}

			public function select_by_ref($ref) {

				if (preg_match('/^([0-9]+)-([a-z]{5})$/', $ref, $matches)) {
					$this->select_by_id($matches[1], $matches[2]);
				} else {
					$this->reset();
				}

			}

			public function select_by_id($id, $pass = NULL) {

				$db = $this->db_get();

				$where_sql = '
					id = "' . $db->escape($id) . '" AND
					deleted = "0000-00-00 00:00:00"';

				if ($pass !== NULL || !$this->user_privileged_get()) {
					$where_sql .= ' AND pass = "' . $db->escape($pass) . '"';
				}

				$db->query('SELECT
								pass,
								created,
								payment_received
							FROM
								' . $db->escape_field($this->db_table_main) . '
							WHERE
								' . $where_sql);

				if ($row = $db->fetch_row()) {
					$this->order_id = $id;
					$this->order_pass = $row['pass'];
					$this->order_created = $row['created'];
					$this->order_paid = $row['payment_received'];
				} else {
					$this->reset();
				}

			}

		//--------------------------------------------------
		// Values

			public function values_set($values) {

				//--------------------------------------------------
				// Create order

					if ($this->order_id === NULL) {
						$this->create();
					}

				//--------------------------------------------------
				// Update

					$db = $this->db_get();

					$values['edited'] = date('Y-m-d H:i:s');

					$where_sql = '
						id = "' . $db->escape($this->order_id) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$db->update($this->db_table_main, $values, $where_sql);

				//--------------------------------------------------
				// Other updates

					$this->delivery_update();
					$this->cache_update();

			}

			public function values_get($fields) {
			}

		//--------------------------------------------------
		// Save functionality (e.g. checkout form)

			public function save() {

				//--------------------------------------------------
				// Create order

					if ($this->order_id === NULL) {
						$this->create();
					}

				//--------------------------------------------------
				// Validation

					$this->validate_save();

					$form = $this->form_get();

					if (!$form->valid()) {
						return false;
					}

				//--------------------------------------------------
				// Update

					$values = $form->data_db_get();

					if (count($values) > 0) {
						$this->values_set($values);
					}

				//--------------------------------------------------
				// Success

					return true;

			}

			public function validate_save() {

				//--------------------------------------------------
				// Form reference

					$form = $this->form_get();

				//--------------------------------------------------
				// Optionally required fields

					if ($form->field_exists('delivery_different') && $form->field_get('delivery_different')->value_get() == 'true') {

						if ($form->field_exists('delivery_name') && $form->field_get('delivery_name')->value_get() == '') {
							$form->field_get('delivery_name')->error_add('Your delivery name is required.');
						}

						if ($form->field_exists('delivery_address_1') && $form->field_get('delivery_address_1')->value_get() == '') {
							$form->field_get('delivery_address_1')->error_add('Your delivery address line 1 is required.');
						}

						if ($form->field_exists('delivery_town_city') && $form->field_get('delivery_town_city')->value_get() == '') {
							$form->field_get('delivery_town_city')->error_add('Your delivery town or city is required.');
						}

						if ($form->field_exists('delivery_country') && $form->field_get('delivery_country')->value_get() == '') {
							$form->field_get('delivery_country')->error_add('Your delivery country is required.');
						}

						if ($form->field_exists('delivery_postcode') && $form->field_get('delivery_postcode')->value_get() == '') {
							$form->field_get('delivery_postcode')->error_add('Your delivery postcode is required.');
						}

						if ($form->field_exists('delivery_telephone') && $form->field_get('delivery_telephone')->value_get() == '') {
							$form->field_get('delivery_telephone')->error_add('Your delivery telephone number is required.');
						}

					}

			}



		//--------------------------------------------------
		// Items

			public function item_add($details = NULL) {

				//--------------------------------------------------
				// Validation

					if ($this->order_id === NULL) {
						$this->create();
					}

					if (!is_array($details)) {
						exit_with_error('When using item_add on an order, you must pass in an array.');
					}

					if (!isset($details['price'])) {
						exit_with_error('When using item_add on an order, you must supply the price.');
					}

				//--------------------------------------------------
				// Insert

					$db = $this->db_get();

					$values = array_merge(array(
							'quantity' => 1,
						), $details, array(
							'id' => '',
							'order_id' => $this->order_id,
							'type' => 'item',
							'created' => date('Y-m-d H:i:s'),
							'deleted' => '0000-00-00 00:00:00',
						));

					if ($values['quantity'] > 0) {

						$db->insert($this->db_table_item, $values);

						$id = $db->insert_id();

					} else {

						$id = NULL;

					}

				//--------------------------------------------------
				// Other updates

					if ($id !== NULL) {

						$this->order_items = NULL;

						$this->delivery_update();
						$this->cache_update();

					}

				//--------------------------------------------------
				// Return

					return $id;

			}

			public function item_quantity_set($item_id, $quantity) {

				//--------------------------------------------------
				// Check

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'item_quantity_set');
					}

				//--------------------------------------------------
				// Update

					$db = $this->db_get();

					if ($quantity <= 0) {

						//--------------------------------------------------
						// Simple delete

							$db->query('UPDATE
											' . $db->escape_field($this->db_table_item) . ' AS oi
										SET
											oi.deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
										WHERE
											oi.id = "' . $db->escape($item_id) . '" AND
											oi.order_id = "' . $db->escape($this->order_id) . '" AND
											oi.type = "item" AND
											oi.deleted = "0000-00-00 00:00:00"');

					} else {

						//--------------------------------------------------
						// Deleted backup (with new ID)

							$sql = 'SELECT
										*
									FROM
										' . $db->escape_field($this->db_table_item) . ' AS oi
									WHERE
										oi.id = "' . $db->escape($item_id) . '" AND
										oi.order_id = "' . $db->escape($this->order_id) . '" AND
										oi.type = "item" AND
										oi.deleted = "0000-00-00 00:00:00"';

							if ($row = $db->fetch($sql)) {

								$row['id'] = '';
								$row['deleted'] = date('Y-m-d H:i:s');

								$db->insert($this->db_table_item, $row);

							} else {

								exit_with_error('Cannot find item "' . $item_id . '" in the order "' . $this->order_id . '"');

							}

						//--------------------------------------------------
						// Update the quantity

							$db->query('UPDATE
											' . $db->escape_field($this->db_table_item) . ' AS oi
										SET
											oi.quantity = "' . $db->escape($quantity) . '"
										WHERE
											oi.id = "' . $db->escape($item_id) . '" AND
											oi.type = "item" AND
											oi.order_id = "' . $db->escape($this->order_id) . '" AND
											oi.deleted = "0000-00-00 00:00:00"');

					}

				//--------------------------------------------------
				// Other updates

					$this->order_items = NULL;

					$this->delivery_update();
					$this->cache_update();

			}

			public function items_update() {

				//--------------------------------------------------
				// Order not selected

					if ($this->order_id === NULL) {
						return;
					}

				//--------------------------------------------------
				// Delete link

					$delete_id = request('item_delete');
					if ($delete_id !== NULL) {
						$this->item_quantity_set($delete_id, 0);
					}

				//--------------------------------------------------
				// Select fields

					foreach ($this->items_get() as $item) {
						$quantity = request('item_quantity_' . $item['id']);
						if ($quantity !== NULL) {
							$this->item_quantity_set($item['id'], $quantity);
						}
					}

			}

			public function items_count_get() {

				if ($this->order_id === NULL) {
					return 0;
				}

				$db = $this->db_get();

				$sql = 'SELECT
							SUM(oi.quantity) AS c
						FROM
							' . $db->escape_field($this->db_table_item) . ' AS oi
						WHERE
							oi.order_id = "' . $db->escape($this->order_id) . '" AND
							oi.type = "item" AND
							oi.deleted = "0000-00-00 00:00:00"';

				if ($row = $db->fetch($sql)) {
					return $row['c'];
				} else {
					return 0;
				}

			}

			public function items_get() {

				//--------------------------------------------------
				// Order not open yet

					if ($this->order_id === NULL) {
						return array();
					}

				//--------------------------------------------------
				// Cached values

					if ($this->order_items) {
						return $this->order_items;
					}

				//--------------------------------------------------
				// Query

					$items = array();

					$db = $this->db_get();

					$sql = 'SELECT
								*
							FROM
								' . $db->escape_field($this->db_table_item) . ' AS oi
							WHERE
								oi.order_id = "' . $db->escape($this->order_id) . '" AND
								oi.type = "item" AND
								oi.deleted = "0000-00-00 00:00:00"';

					foreach ($db->fetch_all($sql) as $row) {

						$details = $row;
						unset($details['deleted']);
						unset($details['order_id']);

						$items[$row['id']] = $details;

					}

				//--------------------------------------------------
				// Return

					$this->order_items = $items;

					return $items;

			}

		//--------------------------------------------------
		// Current basket

			public function currency_get() {
				return $this->order_currency;
			}

			public function currency_char_get() {
				$currency = $this->currency_get();
				if ($currency == 'GBP') return '£';
			}

			public function vat_percent_get() {
				return config::get('order.vat_percent', 20);
			}

			public function vat_item_applied_get() {
				return config::get('order.vat_item_applied', true); // If the price set on items have had vat applied
			}

			public function vat_item_types_get() {
				return array(
						'item', // Could add 'delivery'
					);
			}

			public function totals_get() {

				//--------------------------------------------------
				// Defaults

					$db = $this->db_get();

					$return = array(
							'items' => array(),
							'amount' => array(),
							'vat' => array(),
						);

					foreach ($db->enum_values($this->db_table_item, 'type') as $type) {
						$return['items'][$type] = 0;
					}

				//--------------------------------------------------
				// Items

					$sql = 'SELECT
								oi.type,
								SUM(oi.price) AS total
							FROM
								' . $db->escape_field($this->db_table_item) . ' AS oi
							WHERE
								oi.order_id = "' . $db->escape($this->order_id) . '" AND
								oi.deleted = "0000-00-00 00:00:00"
							GROUP BY
								oi.type';

					foreach ($db->fetch_all($sql) as $row) {
						$return['items'][$row['type']] = $row['total'];
					}

				//--------------------------------------------------
				// Amounts

					$vat_item_applied = $this->vat_item_applied_get();
					$vat_item_types = $this->vat_item_types_get();
					$vat_percent = $this->vat_percent_get();

					$items_total_inc_vat = 0;
					$items_total_ex_vat = 0;
					foreach ($return['items'] as $type => $value) {
						if (in_array($type, $vat_item_types)) {
							$items_total_inc_vat += $value;
						} else {
							$items_total_ex_vat += $value;
						}
					}

					if ($vat_item_applied) {

						$vat_ratio = (1 + ($vat_percent / 100));

						$totals['vat'] = ($items_total_inc_vat - ($items_total_inc_vat / $vat_ratio));
						$totals['gross'] = $items_total_inc_vat;
						$totals['net'] = ($totals['gross'] - $totals['vat']);

					} else {

						$totals['net'] = $items_total_inc_vat;
						$totals['vat'] = (($totals['net'] / 100) * $vat_percent);
						$totals['gross'] = ($totals['net'] + $totals['vat']);

					}

					$totals['gross'] += $items_total_ex_vat;

					$return['amount']['net'] = round($totals['net'], 2);
					$return['amount']['vat'] = round($totals['vat'], 2);
					$return['amount']['gross'] = round($totals['gross'], 2);

					$return['vat']['percent'] = $vat_percent;
					$return['vat']['item_applied'] = $vat_item_applied;
					$return['vat']['item_types'] = $vat_item_types;

				//--------------------------------------------------
				// Return

					return $return;

			}

		//--------------------------------------------------
		// Events

			public function payment_received() {

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'payment_received');
					}

				//--------------------------------------------------
				// Customer email

					$this->_email_customer('order-payment-received');

			}

			public function payment_settled() {

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'payment_settled');
					}

				//--------------------------------------------------
				// Customer email

					$this->_email_customer('order-payment-settled');

			}

			public function dispatched() {

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'dispatched');
					}

			}

		//--------------------------------------------------
		// Tables

			public function table_get_html($config = NULL) {

				$table = $this->table_get();

				return $table->table_get_html($config);

			}

			public function table_get_text() {

				$table = $this->table_get();

				return $table->table_get_text();

			}

		//--------------------------------------------------
		// Emails

			private function _email_customer($template) {

				//--------------------------------------------------
				// Does the template exist

					if (!is_file(PUBLIC_ROOT . '/a/email/' . safe_file_name($template))) {
						return false;
					}

				//--------------------------------------------------
				// Build email

					$email = new email();
					$email->template_set($template);

				//--------------------------------------------------
				// Order table

					$url_prefix = https_url('/');
					if (substr($url_prefix, -1) == '/') {
						$url_prefix = substr($url_prefix, 0, -1);
					}

					$config = array(
							'url_prefix' => $url_prefix, // Images and links get full url
						);

					$table = $this->table_get();

					$email->template_value_set_text('TABLE', $table->table_get_html($config));
					$email->template_value_set_html('TABLE', $table->table_get_text($config));

			}

		//--------------------------------------------------
		// Create

			protected function create($defaults = NULL) {

				if ($this->order_id !== NULL) {
					exit_with_error('Cannot create a new order when one is already selected (' . $this->order_id . ')');
				}

				$order_pass = '';
				for ($k=0; $k<5; $k++) {
					$order_pass .= chr(mt_rand(97,122));
				}

				if (!is_array($defaults)) {
					$defaults = array();
				}

				$date = date('Y-m-d H:i:s');

				$values = array_merge(array(
						'id' => '',
						'pass' => $order_pass,
						'ip' => config::get('request.ip'),
						'created' => $date,
					), $defaults);

				$db = $this->db_get();
				$db->insert($this->db_table_main, $values);

				$this->order_id = $db->insert_id();
				$this->order_pass = $order_pass;
				$this->order_created = $date;
				$this->order_paid = '0000-00-00 00:00:00';

				session::set('order_ref', $this->ref_get());

			}

		//--------------------------------------------------
		// Delivery support

			protected function delivery_price_get() {
				return 10;
			}

			protected function delivery_update() {

				//--------------------------------------------------
				// Current delivery price

					$delivery_price = $this->delivery_price_get();

				//--------------------------------------------------
				// No change with the 1 record (if more, still replace)

					$db = $this->db_get();

					$sql = 'SELECT
								oi.price
							FROM
								' . $db->escape_field($this->db_table_item) . ' AS oi
							WHERE
								oi.order_id = "' . $db->escape($this->order_id) . '" AND
								oi.type = "delivery" AND
								oi.deleted = "0000-00-00 00:00:00"';

					$delivery = $db->fetch_all($sql);

					if (count($delivery) == 1 && round($delivery[0]['price'], 2) == round($delivery_price, 2)) {
						return;
					}

				//--------------------------------------------------
				// Replace delivery record

					$db->query('UPDATE
									' . $db->escape_field($this->db_table_item) . ' AS oi
								SET
									oi.deleted = "' . $db->escape(date('Y-m-d H:i:s')) . '"
								WHERE
									oi.order_id = "' . $db->escape($this->order_id) . '" AND
									oi.type = "delivery" AND
									oi.deleted = "0000-00-00 00:00:00"');

					$db->insert($this->db_table_item, array(
							'id' => '',
							'order_id' => $this->order_id,
							'type' => 'delivery',
							'price' => $delivery_price,
							'quantity' => 1,
							'created' => date('Y-m-d H:i:s'),
							'deleted' => '0000-00-00 00:00:00',
						));

			}

		//--------------------------------------------------
		// Cache support

			protected function cache_update() {
				// If you want to copy the details into a FULLTEXT index field for faster searching
			}

	}

?>