<?php

//--------------------------------------------------
// http://www.phpprime.com/doc/system/order/
//--------------------------------------------------

	class order_base extends check {

		//--------------------------------------------------
		// Variables

			protected $order_id = NULL;
			protected $order_data = NULL;
			protected $order_fields = [];
			protected $order_currency = 'GBP';

			protected $db_table_main = NULL;
			protected $db_table_item = NULL;

			protected $object_table = 'order_table';
			protected $object_payment = 'payment';

			private $db_link = NULL;
			private $form = NULL;

			private $order_items = NULL; // Cache

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
									payment_tax float NOT NULL,
									payment_name tinytext NOT NULL,
									payment_address_1 tinytext NOT NULL,
									payment_address_2 tinytext NOT NULL,
									payment_address_3 tinytext NOT NULL,
									payment_town_city tinytext NOT NULL,
									payment_region tinytext NOT NULL,
									payment_postcode tinytext NOT NULL,
									payment_country tinytext NOT NULL,
									payment_telephone tinytext NOT NULL,
									delivery_different enum(\'false\',\'true\') NOT NULL,
									delivery_name tinytext NOT NULL,
									delivery_address_1 tinytext NOT NULL,
									delivery_address_2 tinytext NOT NULL,
									delivery_address_3 tinytext NOT NULL,
									delivery_town_city tinytext NOT NULL,
									delivery_region tinytext NOT NULL,
									delivery_postcode tinytext NOT NULL,
									delivery_country tinytext NOT NULL,
									delivery_telephone tinytext NOT NULL,
									processed datetime NOT NULL,
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
					return $this->order_id . '-' . $this->order_data['pass'];
				}
			}

			public function db_get() { // Public so order table can access
				if ($this->db_link === NULL) {
					$this->db_link = db_get();
				}
				return $this->db_link;
			}

			protected function table_get($config = []) {
				$table = new $this->object_table($this, $config);
				$table->init();
				return $table;
			}

			public function form_get() {
				if ($this->form === NULL) {

					$db = $this->db_get();

					$this->form = new order_form();
					$this->form->order_ref_set($this);
					$this->form->db_set($this->db_get());
					$this->form->db_save_disable();
					$this->form->db_table_set_sql($db->escape_table($this->db_table_main));

					if ($this->order_id > 0) {

						$where_sql = '
							id = "' . $db->escape($this->order_id) . '" AND
							deleted = "0000-00-00 00:00:00"';

						$this->form->db_where_set_sql($where_sql);

					}

					$this->form->init();

				}
				return $this->form;
			}

		//--------------------------------------------------
		// Reset

			public function reset() {
				$this->order_id = NULL;
				$this->order_data = NULL;
			}

		//--------------------------------------------------
		// Session linking

			public function forget() {
				$this->reset();
				session::delete('order_ref');
			}

			public function remember() {
				session::set('order_ref', $this->ref_get());
			}

		//--------------------------------------------------
		// Select

			public function selected() {
				return ($this->order_id !== NULL);
			}

			public function select_open() {

				if ($this->order_id !== NULL && $this->order_data['payment_received'] == '0000-00-00 00:00:00') {
					return true; // Already selected
				}

				$selected = $this->select_by_ref(session::get('order_ref'));

				if ($selected && $this->order_data['payment_received'] != '0000-00-00 00:00:00') {
					$this->reset();
				}

				return ($this->order_id !== NULL);

			}

			public function select_paid() {

				if ($this->order_id !== NULL && $this->order_data['payment_received'] != '0000-00-00 00:00:00') {
					return true; // Already selected
				}

				$selected = $this->select_by_ref(session::get('order_ref'));

				if ($selected && $this->order_data['payment_received'] == '0000-00-00 00:00:00') {
					$this->reset();
				}

				return ($this->order_id !== NULL);

			}

			public function select_by_ref($ref) {

				if (preg_match('/^([0-9]+)-([0-9a-z]{5})$/i', $ref, $matches)) {
					$this->select_by_id($matches[1], $matches[2]);
				} else {
					$this->reset();
				}

				return ($this->order_id !== NULL);

			}

			public function select_by_id($id, $pass = NULL) {

				$db = $this->db_get();

				$where_sql = '
					id = ? AND
					deleted = "0000-00-00 00:00:00"';

				$parameters = [];
				$parameters[] = array('i', $id);

				if ($pass !== NULL || config::get('order.user_privileged', false) !== true) {
					$where_sql .= ' AND pass = ?';
					$parameters[] = array('s', $pass);
				}

				$fields_sql = [];
				foreach (array_merge(array('pass', 'created', 'payment_received'), $this->order_fields) as $field) {
					$fields_sql[] = $db->escape_field($field);
				}

				$sql = 'SELECT
							' . implode(', ', $fields_sql) . '
						FROM
							' . $db->escape_table($this->db_table_main) . '
						WHERE
							' . $where_sql;

				if ($row = $db->fetch_row($sql, $parameters)) {
					$this->order_id = $id;
					$this->order_data = $row;
				} else {
					$this->reset();
				}

				return ($this->order_id !== NULL);

			}

		//--------------------------------------------------
		// Values

			public function value_set($field, $value) {
				$this->values_set(array($field => $value));
			}

			public function values_set($values) {

				//--------------------------------------------------
				// Create order

					if ($this->order_id === NULL) {
						$this->create();
					}

				//--------------------------------------------------
				// Update

					$db = $this->db_get();

					$values['edited'] = new timestamp();

					$where_sql = '
						id = "' . $db->escape($this->order_id) . '" AND
						deleted = "0000-00-00 00:00:00"';

					$db->update($this->db_table_main, $values, $where_sql);

				//--------------------------------------------------
				// Local cache

					foreach ($values as $name => $value) {
						if (isset($this->order_data[$name])) {
							$this->order_data[$name] = $value;
						}
					}

				//--------------------------------------------------
				// Order update

					$this->order_update();

			}

			public function value_get($field) {
				if (isset($this->order_data[$field])) {
					return $this->order_data[$field];
				} else {
					$values = $this->values_get(array($field));
					return $values[$field];
				}
			}

			public function values_get($fields = NULL) {

				//--------------------------------------------------
				// Create order

					if ($this->order_id === NULL) {
						$this->create();
					}

					if (!is_array($fields) && $fields !== NULL) {
						exit_with_error('Fields list should be an array');
					}

				//--------------------------------------------------
				// Return

					$db = $this->db_get();

					$fields_sql = [];
					foreach ($fields as $field) {
						$fields_sql[] = $db->escape_field($field);
					}

					$sql = 'SELECT
								' . implode(', ', $fields_sql) . '
							FROM
								' . $db->escape_table($this->db_table_main) . '
							WHERE
								id = ? AND
								deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = array('i', $this->order_id);

					if ($row = $db->fetch_row($sql, $parameters)) {
						return $row;
					} else {
						return false;
					}

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

						if ($form->field_exists('delivery_region') && $form->field_get('delivery_region')->value_get() == '') {
							$form->field_get('delivery_region')->error_add('Your delivery county or state is required.');
						}

						if ($form->field_exists('delivery_postcode') && $form->field_get('delivery_postcode')->value_get() == '') {
							$form->field_get('delivery_postcode')->error_add('Your delivery postcode is required.');
						}

						if ($form->field_exists('delivery_country') && $form->field_get('delivery_country')->value_get() == '') {
							$form->field_get('delivery_country')->error_add('Your delivery country is required.');
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

					$now = new timestamp();

					$values = array_merge(array(
							'quantity' => 1,
						), $details, array(
							'order_id' => $this->order_id,
							'type' => 'item',
							'created' => $now,
							'deleted' => '0000-00-00 00:00:00',
						));

					if ($values['quantity'] > 0) {

						$db->insert($this->db_table_item, $values);

						$id = $db->insert_id();

					} else {

						$id = NULL;

					}

				//--------------------------------------------------
				// Order update

					if ($id !== NULL) {
						$this->order_update();
					}

				//--------------------------------------------------
				// Return

					return $id;

			}

			public function items_update() { // Typically used on the basket page

				//--------------------------------------------------
				// Order not selected

					if ($this->order_id === NULL) {
						return;
					}

				//--------------------------------------------------
				// Changes

					$changed = false;

				//--------------------------------------------------
				// Delete link

					$remove_id = request('item_remove');
					if ($remove_id !== NULL) {
						if ($this->_item_quantity_set($remove_id, 0)) {
							$changed = true;
						}
					}

				//--------------------------------------------------
				// Select fields

					foreach ($this->items_get() as $item) {

						$remove = request('item_remove_' . $item['id']);
						if ($remove !== NULL) {

							if ($this->_item_quantity_set($item['id'], 0)) {
								$changed = true;
							}

						} else {

							$quantity = request('item_quantity_' . $item['id']);
							if ($quantity !== NULL) {
								if ($this->_item_quantity_set($item['id'], $quantity)) {
									$changed = true;
								}
							}

						}

					}

				//--------------------------------------------------
				// Order update

					if ($changed) {
						$this->order_update();
					}

				//--------------------------------------------------
				// Return

					return $changed;

			}

			public function item_edit($item_id, $values) { // Not advisable... you normally only need item_quantity_set() to edit the quantity, or remove an item.

				//--------------------------------------------------
				// Order not selected

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'item_edit');
					}

				//--------------------------------------------------
				// Update

					if (isset($values['quantity'])) {
						$quantity = $values['quantity'];
						unset($values['quantity']);
					} else {
						$quantity = NULL;
					}

					$changed = $this->_item_quantity_set($item_id, $quantity, $values);

				//--------------------------------------------------
				// Order update

					if ($changed) {
						$this->order_update();
					}

			}

			public function item_quantity_set($item_id, $quantity) {

				//--------------------------------------------------
				// Check

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'item_quantity_set');
					}

				//--------------------------------------------------
				// Update

					$changed = $this->_item_quantity_set($item_id, $quantity);

				//--------------------------------------------------
				// Order update

					if ($changed) {
						$this->order_update();
					}

			}

			protected function _item_quantity_set($item_id, $quantity, $info = []) {

				//--------------------------------------------------
				// Config

					$db = $this->db_get();

					$now = new timestamp();

				//--------------------------------------------------
				// Has changed

					$edit = (($quantity > 0) || ($quantity === NULL && count($info) > 0));

					if ($edit) {

						$sql = 'SELECT
									*
								FROM
									' . $db->escape_table($this->db_table_item) . ' AS oi
								WHERE
									oi.id = ? AND
									oi.order_id = ? AND
									oi.type = "item" AND
									oi.deleted = "0000-00-00 00:00:00"';

						$parameters = [];
						$parameters[] = array('i', $item_id);
						$parameters[] = array('i', $this->order_id);

						if ($row = $db->fetch_row($sql, $parameters)) {

							if ($quantity === NULL) {
								$quantity = $row['quantity'];
							}

							if ($quantity == $row['quantity']) {
								$changed = false;
								foreach ($info as $field => $value) {
									if ($row[$field] != $value) {
										$changed = true;
										break;
									}
								}
								if (!$changed) {
									return false; // No change
								}
							}

						} else {

							exit_with_error('Cannot find item "' . $item_id . '" in the order "' . $this->order_id . '"');

						}

					}

				//--------------------------------------------------
				// Delete old record

					$sql = 'UPDATE
								' . $db->escape_table($this->db_table_item) . ' AS oi
							SET
								oi.deleted = ?
							WHERE
								oi.id = ? AND
								oi.order_id = ? AND
								oi.type = "item" AND
								oi.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = array('s', $now);
					$parameters[] = array('i', $item_id);
					$parameters[] = array('i', $this->order_id);

					$db->query($sql, $parameters);

				//--------------------------------------------------
				// New record

					if ($edit) {

						$db->insert($this->db_table_item, array_merge($row, $info, array( // Following values must be merged last, do not trust $info array.
								'id' => '',
								'order_id' => $this->order_id,
								'type' => 'item',
								'quantity' => $quantity, // Do not change the 'created' date, as the item order on the review page will change.
							)));

					}

				//--------------------------------------------------
				// Success

					return true;

			}

			public function item_count() {
				$items = 0;
				foreach ($this->items_get() as $item) { // In most cases items_get() is used elsewhere (cached data)... so usually quicker than doing an extra 'SELECT SUM(quantity)'
					$items += $item['quantity'];
				}
				return $items;
			}

			public function items_get() {

				//--------------------------------------------------
				// Order not open yet

					if ($this->order_id === NULL) {
						return [];
					}

				//--------------------------------------------------
				// Cached values

					if ($this->order_items === NULL) {
						$this->order_items = $this->items_get_data();
					}

					return $this->order_items;

			}

			protected function items_get_data() {

				//--------------------------------------------------
				// Tax details

					$tax_applied = in_array('item', $this->tax_types_get());
					$tax_included = in_array('item', $this->tax_included_get());
					$tax_percent = $this->tax_percent_get();

					$tax_ratio = (1 + ($tax_percent / 100));

				//--------------------------------------------------
				// Query

					$items = [];

					$db = $this->db_get();

					$sql = 'SELECT
								*
							FROM
								' . $db->escape_table($this->db_table_item) . ' AS oi
							WHERE
								oi.order_id = ? AND
								oi.type = "item" AND
								oi.deleted = "0000-00-00 00:00:00"
							ORDER BY
								oi.created';

					$parameters = [];
					$parameters[] = array('i', $this->order_id);

					foreach ($db->fetch_all($sql, $parameters) as $row) {

						//--------------------------------------------------
						// Details

							$details = $row;
							unset($details['deleted']);
							unset($details['order_id']);

						//--------------------------------------------------
						// Price details

							if (!$tax_applied) { // Very unlikely

								$details['price_net']   = round(($row['price']), 2);
								$details['price_tax']   = 0;
								$details['price_gross'] = $details['price_net'];

							} else if ($tax_included) {

								$details['price_tax']   = round(($row['price'] - ($row['price'] / $tax_ratio)), 2);
								$details['price_gross'] = round(($row['price']), 2);
								$details['price_net']   = round(($details['price_gross'] - $details['price_tax']), 2);

							} else {

								$details['price_net']   = round(($row['price']), 2);
								$details['price_tax']   = round((($details['price_net'] / 100) * $tax_percent), 2);
								$details['price_gross'] = round(($details['price_net'] + $details['price_tax']), 2);

							}

						//--------------------------------------------------
						// Store

							$items[$row['id']] = $details;

					}

				//--------------------------------------------------
				// Return

					return $items;

			}

		//--------------------------------------------------
		// Current basket

			protected function delivery_price_get() {
				return 0;
			}

			public function currency_get() {
				return $this->order_currency;
			}

			public function currency_char_get() {
				$currency = $this->currency_get();
				if ($currency == 'GBP') return '£';
			}

			public function tax_percent_get() {
				return config::get('order.tax_percent', 20);
			}

			public function tax_types_get() {
				return config::get('order.tax_types', array( // More of a yes/no for the different types (e.g. item/voucher/discount/delivery)
						'item',
					));
			}

			public function tax_included_get() {
				return config::get('order.tax_included', array( // If taxed, then do we add Tax to get Gross, or remove to get Net
						'item',
					));
			}

			public function totals_get() {

				//--------------------------------------------------
				// Tax details

					$tax_types = $this->tax_types_get();
					$tax_included = $this->tax_included_get();
					$tax_percent = $this->tax_percent_get();

					$tax_ratio = (1 + ($tax_percent / 100));

				//--------------------------------------------------
				// Defaults

					$db = $this->db_get();

					$return = array(
							'items' => [],
							'sum' => array(
									'net' => 0,
									'tax' => 0,
									'gross' => 0,
								),
							'tax' => array(
									'percent' => $tax_percent,
									'types' => $tax_types,
									'included' => $tax_included,
								),
						);

					foreach ($db->enum_values($this->db_table_item, 'type') as $type) {

						$return['items'][$type] = array(
								'net' => 0,
								'tax' => 0,
								'gross' => 0,
							);

					}

				//--------------------------------------------------
				// Items

					$order_items = $this->items_get();

					foreach ($order_items as $item) {

						$return['items']['item']['net']   += ($item['price_net']   * $item['quantity']);
						$return['items']['item']['tax']   += ($item['price_tax']   * $item['quantity']);
						$return['items']['item']['gross'] += ($item['price_gross'] * $item['quantity']);

					}

					$sum = $return['items']['item'];

				//--------------------------------------------------
				// Other items (e.g. delivery)

					$sql = 'SELECT
								oi.type,
								SUM(oi.price * oi.quantity) AS total
							FROM
								' . $db->escape_table($this->db_table_item) . ' AS oi
							WHERE
								oi.order_id = ? AND
								oi.type != "item" AND
								oi.deleted = "0000-00-00 00:00:00"
							GROUP BY
								oi.type';

					$parameters = [];
					$parameters[] = array('i', $this->order_id);

					foreach ($db->fetch_all($sql, $parameters) as $row) {

						$taxed = in_array($type, $tax_types);

						if (!$taxed) {

							$total_net   = round($row['total'], 2);
							$total_tax   = 0;
							$total_gross = $total_net;

						} else if (in_array($type, $tax_included)) {

							$total_tax   = round(($row['total'] - ($row['total'] / $tax_ratio)), 2);
							$total_gross = round(($row['total']), 2);
							$total_net   = round(($total_gross - $total_tax), 2);

						} else {

							$total_net   = round(($row['total']), 2);
							$total_tax   = round((($total_net / 100) * $tax_percent), 2);
							$total_gross = round(($total_net + $total_tax), 2);

						}

						$return['items'][$row['type']]['net'] += $total_net;
						$return['items'][$row['type']]['tax'] += $total_tax;
						$return['items'][$row['type']]['gross'] += $total_gross;

						if ($taxed) {
							$sum['net'] += $total_net;
							$sum['tax'] += $total_tax;
						}

						$sum['gross'] += $total_gross;

					}

				//--------------------------------------------------
				// Round amounts

					$return['sum']['net'] = round($sum['net'], 2);
					$return['sum']['tax'] = round($sum['tax'], 2);
					$return['sum']['gross'] = round($sum['gross'], 2);

				//--------------------------------------------------
				// Return

					return $return;

			}

		//--------------------------------------------------
		// Events

			public function payment_received($values = []) {

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'payment_received');
					}

				//--------------------------------------------------
				// Customer email

					$this->_email_customer('order-payment-received');

				//--------------------------------------------------
				// Store

					$this->values_set(array_merge($values, array(
							'payment_received' => new timestamp(),
						)));

			}

			public function payment_settled($values = []) {

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'payment_settled');
					}

				//--------------------------------------------------
				// Customer email

					$this->_email_customer('order-payment-settled');

				//--------------------------------------------------
				// Store

					$this->values_set(array_merge($values, array(
							'payment_settled' => new timestamp(),
						)));

			}

			public function processed($values = []) { // aka "dispatched"

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'processed');
					}

				//--------------------------------------------------
				// Customer email

					$this->_email_customer('order-processed');

				//--------------------------------------------------
				// Store

					$this->values_set(array_merge($values, array(
							'processed' => new timestamp(),
						)));

			}

		//--------------------------------------------------
		// Tables

			public function table_get_html($config = []) {

				$table = $this->table_get($config);

				return $table->table_get_html();

			}

			public function table_get_text($config = []) {

				$table = $this->table_get($config);

				return $table->table_get_text();

			}

		//--------------------------------------------------
		// Create

			protected function create_defaults() {
				return [];
			}

			protected function create() {

				//--------------------------------------------------
				// Details

					if ($this->order_id !== NULL) {
						exit_with_error('Cannot create a new order when one is already selected (' . $this->order_id . ')');
					}

				//--------------------------------------------------
				// Order values

					$order_pass = random_key(5);

					$defaults = $this->create_defaults();
					if (!is_array($defaults)) {
						$defaults = [];
					}

					$now = new timestamp();

					$values = array_merge(array(
							'pass' => $order_pass,
							'ip' => config::get('request.ip'),
							'created' => $now,
						), $defaults);

				//--------------------------------------------------
				// Insert

					$db = $this->db_get();

					$db->insert($this->db_table_main, $values);

					$this->order_id = $db->insert_id();

				//--------------------------------------------------
				// Store

					$this->order_data = [];

					foreach ($this->order_fields as $field) {
						$this->order_data[$field] = NULL;
					}

					$this->order_data['pass'] = $order_pass;
					$this->order_data['created'] = $now->format('db'); // Must be a string to match behaviour of $order->select_by_id()
					$this->order_data['payment_received'] = '0000-00-00 00:00:00';

				//--------------------------------------------------
				// Remember in session

					$this->remember();

			}

		//--------------------------------------------------
		// Delete

			public function delete() {

				//--------------------------------------------------
				// Details

					if ($this->order_id === NULL) {
						exit_with_error('An order needs to be selected', 'delete');
					}

				//--------------------------------------------------
				// Delete

					$this->values_set(array(
							'deleted' => new timestamp(),
						));

				//--------------------------------------------------
				// Remember in session

					$this->forget();

			}

		//--------------------------------------------------
		// Order update

			protected function order_update() {

				//--------------------------------------------------
				// Reset cache

					$this->order_items = NULL;

				//--------------------------------------------------
				// Delivery

					$this->order_update_delivery();

			}

			protected function order_update_delivery() {

				//--------------------------------------------------
				// Current delivery price

					$delivery_price = $this->delivery_price_get();

				//--------------------------------------------------
				// No change with the 1 record (if more, still replace)

					$db = $this->db_get();

					$sql = 'SELECT
								oi.price
							FROM
								' . $db->escape_table($this->db_table_item) . ' AS oi
							WHERE
								oi.order_id = ? AND
								oi.type = "delivery" AND
								oi.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = array('i', $this->order_id);

					$delivery = $db->fetch_all($sql, $parameters);

					if (count($delivery) == 1 && round($delivery[0]['price'], 2) == round($delivery_price, 2)) {
						return;
					}

				//--------------------------------------------------
				// Remove old delivery record

					$now = new timestamp();

					$sql = 'UPDATE
								' . $db->escape_table($this->db_table_item) . ' AS oi
							SET
								oi.deleted = ?
							WHERE
								oi.order_id = ? AND
								oi.type = "delivery" AND
								oi.deleted = "0000-00-00 00:00:00"';

					$parameters = [];
					$parameters[] = array('s', $now);
					$parameters[] = array('i', $this->order_id);

					$db->query($sql, $parameters);

				//--------------------------------------------------
				// Add new delivery record

					$db->insert($this->db_table_item, array(
							'order_id' => $this->order_id,
							'type' => 'delivery',
							'price' => $delivery_price,
							'quantity' => 1,
							'created' => $now,
							'deleted' => '0000-00-00 00:00:00',
						));

			}

		//--------------------------------------------------
		// Emails

			private function _email_customer($template) {

				//--------------------------------------------------
				// Does the template exist

					if (!is_dir(PUBLIC_ROOT . '/a/email/' . safe_file_name($template))) {
						return false;
					}

				//--------------------------------------------------
				// Build email

					$email = new email();
					$email->subject_default_set(link_to_human($template)); // Include a <title> in the html version of the email to override.
					$email->template_set($template);

				//--------------------------------------------------
				// Order details

					$order_details = $this->values_get();

					foreach ($order_details as $field => $value) {
						$email->template_value_set(strtoupper($field), $value);
					}

				//--------------------------------------------------
				// Order table

					$table = $this->table_get(array(
							'email_mode' => true,
						));

					$email->template_value_set_text('TABLE', $table->table_get_text());
					$email->template_value_set_html('TABLE', $table->table_get_html());

				//--------------------------------------------------
				// Testing

					if (false) {
						if (true) { // HTML

							mime_set('text/html');
							exit($email->html());

						} else {

							mime_set('text/plain');
							exit($email->text());

						}
					}

				//--------------------------------------------------
				// Send to customer

					$email->send($order_details['email']);

			}

	}

?>