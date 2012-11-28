<?php

	class db extends check {

		private $result;
		private $connection;
		private $link;

		public function __construct($connection = 'default') {
			$this->connection = $connection;
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
			$field_sql = '`' . str_replace('`', '', $field) . '`'; // Back tick is an illegal character
			$field_sql = str_replace('.', '`.`', $field_sql); // Allow table definition
			return $field_sql;
		}

		public function query($sql, $run_debug = true) {
			$this->connect();
			if ($run_debug && function_exists('debug_database')) {
				$this->result = debug_database($this, $sql);
			} else {
				$this->result = mysql_query($sql, $this->link);
			}
			if (!$this->result) {
				$this->_error($sql);
			}
			return $this->result;
		}

		public function num_rows($result = null) {
			if ($result === null) $result = $this->result;
			return mysql_num_rows($result);
		}

		public function fetch($sql = NULL) {
			if ($sql !== NULL) {
				$this->query($sql);
			}
			return $this->fetch_row();
		}

		public function fetch_all($sql = NULL) {
			$data = array();
			if ($sql !== NULL) {
				$this->query($sql);
			}
			while ($row = mysql_fetch_assoc($this->result)) {
				$data[] = $row;
			}
			return $data;
		}

		public function fetch_row($result = null) {
			if ($result === null) $result = $this->result;
			return mysql_fetch_assoc($result);
		}

		public function fetch_result($row = 0, $col = 0, $result = null) {
			if ($result === null) $result = $this->result;
			if (mysql_num_rows($result) > $row) {
				return mysql_result($result, $row, $col);
			} else {
				return NULL;
			}
		}

		public function insert_id() {
			return mysql_insert_id($this->link);
		}

		public function affected_rows() {
			return mysql_affected_rows($this->link);
		}

		public function enum_values($table_sql, $field) {
			$sql = 'SHOW COLUMNS FROM ' . $table_sql . ' LIKE "' . $this->escape($field) . '"';
			if ($row = $this->fetch($sql)) {
				return explode("','", preg_replace("/(enum|set)\('(.*?)'\)/", '\2', $row['Type']));
			} else {
				$this->_error('Could not return enum values for field "' . $field . '"');
			}
		}

		public function insert($table_sql, $values, $on_duplicate = NULL) {

			$fields_sql = implode(', ', array_map(array($this, 'escape_field'), array_keys($values)));

			$values_sql = array();
			foreach ($values as $value) {
				if ($value === NULL) {
					$values_sql[] = 'NULL';
				} else {
					$values_sql[] = $this->escape_string($value);
				}
			}
			$values_sql = implode(', ', $values_sql);

			if ($on_duplicate === NULL) {

				return $this->query('INSERT INTO ' . $table_sql . ' ('. $fields_sql . ') VALUES (' . $values_sql . ')');

			} else if (!is_array($on_duplicate)) {

				return $this->query('INSERT INTO ' . $table_sql . ' ('. $fields_sql . ') VALUES (' . $values_sql . ') ON DUPLICATE KEY UPDATE ' . $on_duplicate);

			} else {

				$set_sql = array();
				foreach ($on_duplicate as $field_name => $field_value) {
					$set_sql[] = $this->escape_field($field_name) . ' = ' . $this->escape_string($field_value);
				}
				$set_sql = implode(', ', $set_sql);

				return $this->query('INSERT INTO ' . $table_sql . ' ('. $fields_sql . ') VALUES (' . $values_sql . ') ON DUPLICATE KEY UPDATE ' . $set_sql);

			}

		}

		public function update($table_sql, $values, $where_sql) {

			$set_sql = array();
			foreach ($values as $field_name => $field_value) {
				$set_sql[] = $this->escape_field($field_name) . ' = ' . ($field_value === NULL ? 'NULL' : $this->escape_string($field_value));
			}
			$set_sql = implode(', ', $set_sql);

			return $this->query('UPDATE ' . $table_sql . ' SET '. $set_sql . ' WHERE ' . $where_sql);

		}

		public function select($table_sql, $fields, $where_sql, $limit = NULL) {

			if ($fields === 1) {
				$fields_sql = '1';
			} else if ($fields == 'count') {
				$fields_sql = 'COUNT(*) AS "count"';
			} else if ($fields === NULL) {
				$fields_sql = '*';
			} else {
				$fields_sql = implode(', ', array_map(array($this, 'escape_field'), $fields));
			}

			$limit_sql = ($limit === NULL ? '' : ' LIMIT ' . intval($limit));

			return $this->query('SELECT ' . $fields_sql . ' FROM ' . $table_sql . ' WHERE ' . $where_sql . $limit_sql);

		}

		public function delete($table_sql, $where_sql) {

			return $this->query('DELETE FROM ' . $table_sql . ' WHERE ' . $where_sql);

		}

		public function link_get() {
			return $this->link;
		}

		private function connect() {

			if (!$this->link) {

				$this->link = config::array_get('db.link', $this->connection);

				if (!$this->link) {

					$name = config::get('db.name');
					$user = config::get('db.user');
					$pass = config::get('db.pass');
					$host = config::get('db.host');

					if (!function_exists('mysql_connect')) {
						$this->_error('PHP does not have MySQL support - http://www.php.net/mysql_connect', true);
					}

					$this->link = @mysql_connect($host, $user, $pass, true);
					if (!$this->link) {
						$this->_error('A connection could not be established with the database - ' . mysql_error(), true);
					}

					if (!@mysql_select_db($name, $this->link)) {
						$this->_error('Selecting the database failed (' . $name . ')', true);
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
							mysql_query('SET NAMES ' . $charset, $this->link);
						}
					}

					config::array_set('db.link', $this->connection, $this->link);

				}

			}

		}

		public function _error($query = 'N/A', $use_query_as_error = false) { // Public so debug script can call

			if ($this->link) {
				$extra = mysql_errno($this->link) . ': ' . mysql_error($this->link);
			} else if (function_exists('mysql_errno') && mysql_errno() != 0) {
				$extra = mysql_errno() . ': ' . mysql_error() . ' (no link)';
			} else {
				$extra = '';
			}

			if (function_exists('exit_with_error') && config::get('db.error_thrown') !== true) {
				config::set('db.error_thrown', true);
				exit_with_error('An error has occurred with the database.', $query . "\n\n" . $extra);
			} else {
				http_response_code(500);
				exit('<p>I have an error: <br />' . htmlentities($use_query_as_error ? $query : $extra) . '</p>');
			}

		}

	}

?>