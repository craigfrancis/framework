<?php

/***************************************************

	//--------------------------------------------------
	// Site config



	//--------------------------------------------------
	// Example setup



***************************************************/

	class order_base extends check {

		//--------------------------------------------------
		// Variables

			protected $order_id = NULL;
			protected $order_pass = NULL;
			protected $order_paid = NULL;

		//--------------------------------------------------
		// Setup

			public function __construct() {
				$this->_setup();
			}

			protected function _setup() {
			}

		//--------------------------------------------------
		// Configuration

			protected function db_get() {
				if ($this->db_link === NULL) {
					$this->db_link = new db();
				}
				return $this->db_link;
			}

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

		//--------------------------------------------------
		// Create

			public function create() {

				if ($this->order_id !== NULL) {
					exit_with_error('Cannot create a new order when one is already selected (' . $this->order_id . ')');
				}

				$order_pass = '';
				for ($k=0; $k<5; $k++) {
					$order_pass .= chr(mt_rand(97,122));
				}

				$this->order_id = $db->insert_id();
				$this->order_pass = $order_pass;
				$this->order_paid = '0000-00-00 00:00:00';

				session::set('order_ref', $this->ref_get());

			}

		//--------------------------------------------------
		// Reset

			public function reset() {

				$this->order_id = NULL;
				$this->order_pass = NULL;
				$this->order_paid = NULL;

			}

		//--------------------------------------------------
		// Select

			public function selected() {
				return ($this->order_id !== NULL);
			}

			public function select_by_id($id, $pass = NULL) {

				$db = $this->db_get();

				$where_sql = 'id = "' . $db->escape($id) . '"';

				if ($pass !== NULL || !$this->user_privileged_get()) {
					$where_sql .= ' AND pass = "' . $db->escape($pass) . '"';
				}

				$db->query('SELECT
								paid
							FROM
								' . $this->sqlTableOrder . '
							WHERE
								' . $where_sql);

				if ($row = $db->fetch_assoc()) {
					$this->order_id = $id;
					$this->order_pass = $row['pass'];
					$this->order_paid = $row['paid'];
				} else {
					$this->_reset();
				}

			}

			public function select_by_ref($ref) {
				if (preg_match('/^([0-9]+)-([a-z]{5})$/', $ref, $matches)) {
					$this->select_by_id($matches[1], $matches[2]);
				} else {
					$this->_reset();
				}
			}

			public function select_open() {

				$this->select_by_ref(session::get('order_ref'));

				if ($this->order_paid != '0000-00-00 00:00:00') {
					$this->_reset();
				}

			}

		//--------------------------------------------------
		// Details

			public function details_set($values) {

				if ($this->order_id === NULL) {
					$this->create();
				}

				$this->_cache_update();

			}

			public function details_get($fields) {
			}

		//--------------------------------------------------
		// Items

			public function item_add() {

				if ($this->order_id === NULL) {
					$this->create();
				}

				$this->_cache_update();

				return $db->insert_id();

			}

			public function items_get() {
			}

			public function item_edit_quantity($item_id, $quantity) {

				$this->_cache_update();

			}

		//--------------------------------------------------
		// Current basket

			public function currency_get() {
				return 'GBP';
			}

			public function total_get() {
				return 0;
			}

			// Delivery separate?

		//--------------------------------------------------
		// Events

			public function payment_paid() {
			}

			public function payment_settled() {
			}

		//--------------------------------------------------
		// Cache support

			protected function _cache_update() {
				// If you want to copy the details into a FULLTEXT index field for faster searching
			}

	}

//--------------------------------------------------
// Tables exist

	if (SERVER == 'stage') {

// 		debug_require_db_table('user', '
// 				CREATE TABLE [TABLE] (
// 					id int(11) NOT NULL AUTO_INCREMENT,
// 					email varchar(100) NOT NULL,
// 					pass tinytext NOT NULL,
// 					created datetime NOT NULL,
// 					edited datetime NOT NULL,
// 					deleted datetime NOT NULL,
// 					PRIMARY KEY (id),
// 					UNIQUE KEY email (email)
// 				);');

	}

?>