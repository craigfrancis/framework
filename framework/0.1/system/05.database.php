<?php

	// TODO: When called statically? - use variable from config?

	class db extends check {

		private $result;
		private $link;

		public function connect($name = NULL, $user = NULL, $pass = NULL, $host = NULL) {

			if (!$this->link) {

				if ($name === NULL && $user === NULL && $pass === NULL && $host === NULL) {
					$this->link = config::get('db.link');
				}

				if (!$this->link) {

					if ($name === NULL) $name = config::get('db.name');
					if ($user === NULL) $user = config::get('db.user');
					if ($pass === NULL) $pass = config::get('db.pass');
					if ($host === NULL) $host = config::get('db.host');

					$this->link = @mysql_connect($host, $user, $pass, true);
					if (!$this->link) {
						$this->error('A connection could not be established with the database - ' . mysql_error(), true);
					}

					if (!@mysql_select_db($name, $this->link)) {
						$this->error('Selecting the database failed', true);
					}

					if (config::get('output.charset') == 'UTF-8') {
						$charset = 'utf8';
					} else if (config::get('output.charset') == 'ISO-8859-1') {
						$charset = 'latin1';
					} else {
						$charset = NULL;
					}

					if ($charset !== NULL) {
						if (function_exists('mysql_set_charset')) {
						 	mysql_set_charset($charset, $this->link);
						} else {
							mysql_query('SET NAMES ' . $charset);
						}
					}

					config::set_default('db.link', $this->link);

				}

			}

		}

		public function error($query = 'N/A', $use_query_as_error = false) {

			if ($this->link) {
				$extra = mysql_errno($this->link) . ': ' . mysql_error($this->link);
			} else if (mysql_errno() != 0) {
				$extra = mysql_errno() . ': ' . mysql_error() . ' (no link)';
			} else {
				$extra = '';
			}

			if (function_exists('exit_with_error') && config::get('db.error_thrown') !== true) {
				config::set('db.error_thrown', true);
				exit_with_error('An error has occurred with the database', $query . "\n\n" . $extra);
			} else {
				header('HTTP/1.0 500 Internal Server Error');
				exit('<p>I have an error: <br />' . htmlentities($use_query_as_error ? $query : $extra) . '</p>');
			}

		}

		public function escape($val) {

			$this->connect();

			if (function_exists('mysql_real_escape_string')) {
				return mysql_real_escape_string($val, $this->link);

			} else if (function_exists('mysql_escape_string')) {
				return mysql_escape_string($val);

			} else {
				return addslashes($val);
			}

		}

		public function escape_string($val) {
			return '"' . $this->escape($val) . '"';
		}

		public function escape_like($val) {
			$val = $this->escape($val);
			$val = str_replace('_', '\_', $val);
			$val = str_replace('%', '\%', $val);
			return $val;
		}

		public function escape_reg_exp($val) {
			return $this->escape(preg_quote($val));
		}

		public function escape_field($field) {
			$sql_field = '`' . str_replace('`', '', $field) . '`'; // Back tick is an illegal character
			$sql_field = str_replace('.', '`.`', $sql_field); // Allow table definition
			return $sql_field;
		}

		public function query($query, $run_debug = true) {
			$this->connect();
			if ($run_debug && function_exists('debug_database')) {
				$this->result = debug_database($this, $query);
			} else {
				$this->result = mysql_query($query, $this->link) or $this->error($query);
			}
			return $this->result;
		}

		public function num_rows($result = null) {
			if ($result === null) $result = $this->result;
			return mysql_num_rows($result);
		}

		public function fetch_assoc($result = null) {
			if ($result === null) $result = $this->result;
			return mysql_fetch_assoc($result);
		}

		public function fetch_array($result = null) {
			if ($result === null) $result = $this->result;
			return mysql_fetch_array($result);
		}

		public function result($row, $col, $result = null) {
			if ($result === null) $result = $this->result;
			return mysql_result($result, $row, $col);
		}

		public function insert_id() {
			return mysql_insert_id($this->link);
		}

		public function affected_rows() {
			return mysql_affected_rows($this->link);
		}

		public function enum_values($sql_table, $field) {
			$this->query('SHOW COLUMNS FROM ' . $sql_table . ' LIKE "' . $this->escape($field) . '"');
			if ($row = $this->fetch_assoc()) {
				return explode("','", preg_replace("/(enum|set)\('(.+?)'\)/", '\2', $row['Type']));
			} else {
				$this->error('Could not return enum values for field "' . $field . '"');
			}
		}

		public function insert($sql_table, $values, $on_duplicate = NULL) {

			$sql_fields = implode(', ', array_map(array($this, 'escape_field'), array_keys($values)));
			$sql_values = implode(', ', array_map(array($this, 'escape_string'), $values));

			if ($on_duplicate === NULL) {

				$this->result = $this->query('INSERT INTO ' . $sql_table . ' ('. $sql_fields . ') VALUES (' . $sql_values . ')');

			} else if (!is_array($on_duplicate)) {

				$this->result = $this->query('INSERT INTO ' . $sql_table . ' ('. $sql_fields . ') VALUES (' . $sql_values . ') ON DUPLICATE KEY UPDATE ' . $on_duplicate);

			} else {

				$sql_set = array();
				foreach ($on_duplicate as $field_name => $field_value) {
					$sql_set[] = $this->escape_field($field_name) . ' = ' . $this->escape_string($field_value);
				}
				$sql_set = implode(', ', $sql_set);

				$this->result = $this->query('INSERT INTO ' . $sql_table . ' ('. $sql_fields . ') VALUES (' . $sql_values . ') ON DUPLICATE KEY UPDATE ' . $sql_set);

			}

			return $this->result; // insert_id or affected_rows

		}

		public function update($sql_table, $values, $sql_where) {

			$sql_set = array();
			foreach ($values as $field_name => $field_value) {
				$sql_set[] = $this->escape_field($field_name) . ' = ' . $this->escape_string($field_value);
			}
			$sql_set = implode(', ', $sql_set);

			$this->result = $this->query('UPDATE ' . $sql_table . ' SET '. $sql_set . ' WHERE ' . $sql_where);
			return $this->result; // affected_rows

		}

		public function select($sql_table, $fields, $sql_where, $limit = NULL) {

			if ($fields === 1) {
				$sql_fields = '1';
			} else if ($fields === NULL) {
				$sql_fields = '*';
			} else {
				$sql_fields = implode(', ', array_map(array($this, 'escape_field'), $fields));
			}

			$sql_limit = ($limit === NULL ? '' : ' LIMIT ' . intval($limit));

			$this->result = $this->query('SELECT ' . $sql_fields . ' FROM ' . $sql_table . ' WHERE ' . $sql_where . $sql_limit);
			return $this->result; // num_rows or fetch_assoc

		}

		public function delete($sql_table, $sql_where) {

			$this->result = $this->query('DELETE FROM ' . $sql_table . ' WHERE ' . $sql_where);
			return $this->result; // affected_rows

		}

	}




/*--------------------------------------------------

	Can be called statically or dynamically:

		$rst_a = db::query('
			SELECT
			FROM');

		$rst_a = $db->query('
			SELECT
			FROM');

		$rst_a = $this->db->query('
			SELECT
			FROM');

	Useful for the form object which may need to
	known which $link to use:

		$form = new form(
				'database_table' => array(DB_T_PREFIX . 'example_table', 'a', $db), // Alias and database connection
			);

		if ($db === NULL) {
			$db = db; // ???
		}

	How about a generic

		$rst_a = db_query('SELECT
							FROM');

--------------------------------------------------*/


//--------------------------------------------------
//
// $rs = db::query('SELECT
// 					FROM');
//
// $rst = db:q('SELECT
// 			FROM');
//
// $rst = $my_db:q('SELECT
// 				FROM');
//
// $rst = $this->db:q('SELECT
// 					FROM');
//
// In this example, we could return a "db_result" object,
// which could be used in the form of:
//
// if ($rst->num_rows() > 0) {
// }
//
// while ($rst->next()) {
// 	$user[]['address'] = $rst->fetch_assoc();
// 	$user[]['address'] = $rst->get('address');
// 	$user[]['address'] = $rst->get_address();
// 	$user[]['address'] = $rst->address;
// }
//
// This may be helpful when searching for all
// references to address... but remember tab
// completion.
//
//--------------------------------------------------
//
//	db('SELECT
//			*
//		FROM
//			' . DB_T_PREFIX . 'table_name
//		WHERE
//			field = val';
//
//	db::ex('SELECT
//				*
//			FROM
//				' . DB_T_PREFIX . 'table_name
//			WHERE
//				field = val';
//
//	$db->query('SELECT
//					*
//				FROM
//					' . DB_T_PREFIX . 'table_name
//				WHERE
//					field = val');
//
//--------------------------------------------------
//
// $rs = DB::query('SELECT
// 					FROM');
//	db::ex('SELECT
//			FROM');
//	$db->query('SELECT
//				FROM');
//	$this->query('SELECT
//				FROM');
//
//--------------------------------------------------

?>