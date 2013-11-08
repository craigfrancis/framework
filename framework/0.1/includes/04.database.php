<?php

	class db extends check {

		private $result;
		private $connection;
		private $link;
		private $structure_table_cache;
		private $structure_field_cache;

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

		public function escape_table($table) {
			return '`' . str_replace('`', '', $table) . '`';
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

		public function num_rows($sql = NULL) {
			if (is_string($sql)) {
				$result = $this->query($sql);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			return mysql_num_rows($result);
		}

		public function fetch($sql = NULL) { // Backwards compatability
			return $this->fetch_row($sql);
		}

		public function fetch_all($sql = NULL) {
			if (is_string($sql)) {
				$result = $this->query($sql);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			$data = array();
			while ($row = mysql_fetch_assoc($result)) {
				$data[] = $row;
			}
			return $data;
		}

		public function fetch_row($sql = NULL) {
			if (is_string($sql)) {
				$result = $this->query($sql);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			return mysql_fetch_assoc($result);
		}

		public function fetch_result($row = 0, $col = 0, $result = NULL) {
			if ($result === NULL) $result = $this->result;
			if (mysql_num_rows($result) > $row) {
				return mysql_result($result, $row, $col);
			} else {
				return NULL;
			}
		}

		public function fetch_fields($table_sql, $field = NULL) {

			if ($field) {
				if (isset($this->structure_table_cache[$table_sql][$field])) {
					return $this->structure_table_cache[$table_sql][$field];
				} else if (isset($this->structure_field_cache[$table_sql][$field])) {
					return $this->structure_field_cache[$table_sql][$field];
				}
			} else {
				if (isset($this->structure_table_cache[$table_sql])) {
					return $this->structure_table_cache[$table_sql];
				}
			}

			$sql = 'SHOW FULL COLUMNS FROM ' . $table_sql;

			if ($field !== NULL) {
				$sql .= ' LIKE "' . $this->escape_like($field) . '"';
			}

			$details = array();

			foreach ($this->fetch_all($sql) as $row) {

				$type = $row['Type'];

				if (($pos = strpos($type, '(')) !== false) {
					$info = substr($type, ($pos + 1), -1);
					$type = substr($type, 0, $pos);
				} else {
					$info = NULL;
				}

				$options = NULL;

				if ($type == 'int' || $type == 'tinyint' || $type == 'smallint' || $type == 'mediumint' || $type == 'bigint' || $type == 'char' || $type == 'binary' || $type == 'varchar' || $type == 'varbinary') {
					$length = $info;
				} else if ($type == 'decimal') {
					$pos = strpos($info, ',');
					$length = substr($info, 0, $pos); // e.g. decimal(10,2) = precision 10
				} else if ($type == 'tinytext' || $type == 'tinyblob') {
					$length = 255;
				} else if ($type == 'text' || $type == 'blob') {
					$length = 65535;
				} else if ($type == 'mediumtext' || $type == 'mediumblob') {
					$length = 16777215;
				} else if ($type == 'longtext' || $type == 'longblob') {
					$length = 4294967295;
				} else if ($type == 'date') {
					$length = 10;
				} else if ($type == 'datetime') {
					$length = 19;
				} else if ($type == 'time') {
					$length = 8;
				} else if ($type == 'year') {
					$length = 4;
				} else if ($type == 'bool') {
					$length = 1;
				} else if ($type == 'float' || $type == 'double' || $type == 'timestamp') {
					$length = NULL; // Not really aplicable
				} else if ($type == 'enum' || $type == 'set') {
					$options = str_getcsv($info, ',', "'");
					$length = count($options);
				} else {
					$this->_error('Unknown type "' . $row['Type'] . '" for field "' . $row['Field'] . '"', true);
				}

				$details[$row['Field']] = array(
						'type' => $type,
						'length' => $length,
						'collation' => $row['Collation'],
						'null' => ($row['Null'] == 'YES'),
						'default' => $row['Default'],
						'extra' => $row['Extra'],
						'options' => $options,
					);

			}

			if ($field) {

				if (!isset($details[$field])) {
					$this->_error('Cannot find field "' . $field . '" in table "' . $table_sql . '"', true);
				}

				$this->structure_field_cache[$table_sql][$field] = $details[$field];

				return $details[$field];

			} else {

				$this->structure_table_cache[$table_sql] = $details;

				return $details;

			}

		}

		public function insert_id() {
			return mysql_insert_id($this->link);
		}

		public function affected_rows() {
			return mysql_affected_rows($this->link);
		}

		public function enum_values($table_sql, $field) {
			$field_info = $this->fetch_fields($table_sql, $field);
			return $field_info['options'];
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

		public function select($table_sql, $fields, $where_sql, $options = array()) { // Table first, like all other methods

			if ($fields === 1) {
				$fields_sql = '1';
			} else if ($fields == 'count') {
				$fields_sql = 'COUNT(*) AS "count"';
			} else if ($fields === NULL) {
				$fields_sql = '*';
			} else {
				$fields_sql = implode(', ', array_map(array($this, 'escape_field'), $fields));
			}

			if (is_array($where_sql)) {
				if (count($where_sql) > 0) {
					$where_sql = '(' . implode(') AND (', $where_sql) . ')';
				} else {
					$where_sql = 'true';
				}
			}

			$sql = 'SELECT ' . $fields_sql . ' FROM ' . $table_sql . ' WHERE ' . $where_sql;

			if (isset($options['group_sql'])) $sql .= ' GROUP BY ' . $options['group_sql'];
			if (isset($options['order_sql'])) $sql .= ' ORDER BY ' . $options['order_sql'];
			if (isset($options['limit_sql'])) $sql .= ' LIMIT '    . $options['limit_sql'];

			return $this->query($sql);

		}

		public function delete($table_sql, $where_sql) {

			return $this->query('DELETE FROM ' . $table_sql . ' WHERE ' . $where_sql);

		}

		public function link_get() {
			return $this->link;
		}

		private function connect() {

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