<?php

	class db extends check {

		private $result;
		private $link;

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

		public function query($query, $run_debug = true) {
			$this->connect();
			if ($run_debug && function_exists('debug_database')) {
				$this->result = debug_database($this, $query);
			} else {
				$this->result = mysql_query($query, $this->link);
			}
			if (!$this->result) {
				$this->_error($query);
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

		public function enum_values($table_sql, $field) {
			$this->query('SHOW COLUMNS FROM ' . $table_sql . ' LIKE "' . $this->escape($field) . '"');
			if ($row = $this->fetch_assoc()) {
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

				$this->result = $this->query('INSERT INTO ' . $table_sql . ' ('. $fields_sql . ') VALUES (' . $values_sql . ')');

			} else if (!is_array($on_duplicate)) {

				$this->result = $this->query('INSERT INTO ' . $table_sql . ' ('. $fields_sql . ') VALUES (' . $values_sql . ') ON DUPLICATE KEY UPDATE ' . $on_duplicate);

			} else {

				$set_sql = array();
				foreach ($on_duplicate as $field_name => $field_value) {
					$set_sql[] = $this->escape_field($field_name) . ' = ' . $this->escape_string($field_value);
				}
				$set_sql = implode(', ', $set_sql);

				$this->result = $this->query('INSERT INTO ' . $table_sql . ' ('. $fields_sql . ') VALUES (' . $values_sql . ') ON DUPLICATE KEY UPDATE ' . $set_sql);

			}

			return $this->result; // insert_id or affected_rows

		}

		public function update($table_sql, $values, $where_sql) {

			$set_sql = array();
			foreach ($values as $field_name => $field_value) {
				$set_sql[] = $this->escape_field($field_name) . ' = ' . ($field_value === NULL ? 'NULL' : $this->escape_string($field_value));
			}
			$set_sql = implode(', ', $set_sql);

			$this->result = $this->query('UPDATE ' . $table_sql . ' SET '. $set_sql . ' WHERE ' . $where_sql);
			return $this->result; // affected_rows

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

			$this->result = $this->query('SELECT ' . $fields_sql . ' FROM ' . $table_sql . ' WHERE ' . $where_sql . $limit_sql);
			return $this->result; // num_rows or fetch_assoc

		}

		public function delete($table_sql, $where_sql) {

			$this->result = $this->query('DELETE FROM ' . $table_sql . ' WHERE ' . $where_sql);
			return $this->result; // affected_rows

		}

		public function link_get() {
			return $this->link;
		}

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

					config::set_default('db.link', $this->link);

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