<?php

	class db extends check {

		private $result;
		private $statement;
		private $affected_rows;
		private $connection;
		private $link;
		private $structure_table_cache;
		private $structure_field_cache;

		public function __construct($connection = 'default') {
			$this->connection = $connection;
		}

		public function escape($val) {
			$this->connect();
			return mysqli_real_escape_string($this->link, $val);
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

		public function escape_match_boolean_all($field, $search) {
			$search_query = array();
			foreach (split_words($search) as $word) {
				$char = substr($word, 0, 1);
				if ($char == '-' || $char == '+') {
					$search_query[] = $word;
				} else {
					$search_query[] = '+' . $word; // Default to an AND in BOOLEAN MATCH, otherwise it's seen as an OR
				}
			}
			if (count($search_query) > 0) {
				return 'MATCH (' . $this->escape_field($field) . ') AGAINST ("' . $this->escape(implode(' ', $search_query)) . '" IN BOOLEAN MODE)';
			} else {
				return 'false';
			}
		}

		public function escape_field($field) {
			$field_sql = '`' . str_replace('`', '', $field) . '`'; // Back tick is an illegal character
			$field_sql = str_replace('.', '`.`', $field_sql); // Allow table definition
			return $field_sql;
		}

		public function escape_table($table) {
			return '`' . str_replace('`', '', $table) . '`';
		}

		public function parameter_like($val, $count = 0) {
			$val = str_replace('_', '\_', $val);
			$val = str_replace('%', '\%', $val);
			return array_fill(0, $count, array('s', '%' . $val . '%'));
		}

		// public function parameter_in($values) {
		// 	http://php.net/manual/en/mysqli-stmt.bind-param.php#103622
		// 	list($in_sql, $in_parameters) = $db->parameter_in(array_keys($items));
		// 	if ($values) {
		// 		$sql = implode(',', array_fill(0, count($values), '?'));
		// 		$sql = substr(str_repeat('?,', count($values)), 0, -1);
		// 		$parameters = array();
		// 		foreach ($values as $value) {
		// 			$parameters[] =  array('s', $value);
		// 		}
		// 	} else {
		// 		$sql = '?';
		// 		$parameters[] = array('s', '');
		// 	}
		// 	return array($sql, $parameters);
		// }

		public function query($sql, $parameters = NULL, $run_debug = true, $exit_on_error = true) {

			if ($parameters === false) {
				trigger_error('Second parameter in query() is for SQL parameters', E_USER_NOTICE);
				$parameters = NULL;
				$run_debug = false;
			}

			$this->connect();

			if ($run_debug && function_exists('debug_database')) {

				$this->result = debug_database($this, $sql, $parameters, $exit_on_error);

			} else if (function_exists('mysqli_stmt_get_result')) { // When mysqlnd is installed - There is no way I'm using bind_result(), where the values from the database should stay in their array (ref fetch_assoc), and work around are messy.

				$this->statement = mysqli_prepare($this->link, $sql);

				if ($this->statement) {

					if ($parameters) {
						$ref_values = array(implode(array_column($parameters, 0)));
						foreach ($parameters as $key => $value) {
							$ref_values[] = &$parameters[$key][1];
						}
						call_user_func_array(array($this->statement, 'bind_param'), $ref_values);
					}

					$this->result = $this->statement->execute();
					if ($this->result) {
						$this->affected_rows = $this->statement->affected_rows;
						$this->result = $this->statement->get_result();
						if ($this->result === false) {
							$this->result = true; // Didn't create any results, e.g. UPDATE, INSERT, DELETE
						}
						$this->statement->close(); // If this isn't successful, we need to get to the errno
					}

				} else {

					$this->result = false;

				}

			} else {

				if ($parameters) {
					$offset = 0;
					$k = 0;
					while (($pos = strpos($sql, '?', $offset)) !== false) {
						if (isset($parameters[$k])) {
							$sql_value = $this->escape_string($parameters[$k][1]);
							$sql = substr($sql, 0, $pos) . $sql_value . substr($sql, ($pos + 1));
							$offset = ($pos + strlen($sql_value));
							$k++;
						} else {
							exit_with_error('Missing parameter "' . $k . '" in SQL', $sql);
						}
					}
					if (isset($parameters[$k])) {
						exit_with_error('Unused parameter "' . $k . '" in SQL', $sql);
					}
				}

				$this->result = mysqli_query($this->link, $sql);

			}

			if (!$this->result && $exit_on_error) {
				$this->_error($sql);
			}

			return $this->result;

		}

		public function num_rows($sql = NULL, $parameters = NULL) {
			if (is_string($sql)) {
				$result = $this->query($sql, $parameters);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			return mysqli_num_rows($result);
		}

		public function fetch($sql = NULL, $parameters = NULL, $row = 0, $col = 0) {
			if ($parameters !== NULL && !is_array($parameters)) {
				trigger_error('Second parameter in fetch() is for SQL parameters', E_USER_NOTICE);
				$col = $row;
				$row = $parameters;
				$parameters = NULL;
			}
			if (is_string($sql)) {
				$result = $this->query($sql, $parameters);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			if ($row >= 0 && $row < mysqli_num_rows($result)) {
				mysqli_data_seek($result, $row);
				$data = mysqli_fetch_row($result);
				if (isset($data[$col])) {
					return $data[$col];
				}
			}
			return false; // match old mysql_result behaviour, also a field could be NULL
		}

		public function fetch_all($sql = NULL, $parameters = NULL) {
			if (is_string($sql)) {
				$result = $this->query($sql, $parameters);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			$data = array();
			if ($result !== true) {
				while ($row = mysqli_fetch_assoc($result)) {
					$data[] = $row;
				}
			}
			return $data;
		}

		public function fetch_row($sql = NULL, $parameters = NULL) {
			if (is_string($sql)) {
				$result = $this->query($sql, $parameters);
			} else if ($sql !== NULL) {
				$result = $sql;
			} else {
				$result = $this->result;
			}
			return mysqli_fetch_assoc($result);
		}

		public function fetch_fields($item = NULL, $field = NULL) {

			if ($item === NULL || !is_string($item)) { // Work with an existing query (do the best you can).

				if ($item !== NULL) {
					$result = $item;
				} else {
					$result = $this->result;
				}

				$details = array();

				if ($field !== NULL) {
					$results = array(mysqli_fetch_field_direct($result, $field)); // Index based offset
				} else {
					$results = mysqli_fetch_fields($result);
				}

				foreach ($results as $result) {

					if ($result->charsetnr == 63) { // See /usr/local/opt/mysql/share/mysql/charsets/Index.xml

						$collation = NULL; // binary
						$length = $result->length;

					} else if ($result->charsetnr == 33) {

						$collation = 'utf8_unicode_ci';
						$length = floor($result->length / 3);

					} else {

						exit_with_error('Unknown character-set (' . $result->charsetnr . ') found on the "' . $result->name . '" field.');

					}

					$type = NULL;
					$binary = ($result->flags & MYSQLI_BINARY_FLAG);

					if ($result->type == MYSQLI_TYPE_TINY) { // or MYSQLI_TYPE_CHAR, as both equal 1
						if ($length == 1) {
							$type = 'bool'; // not MYSQLI_TYPE_BIT - could still be a tinyint though
						} else if ($result->flags & MYSQLI_NUM_FLAG) {
							$type = 'tinyint';
						}
					} else if ($result->type == MYSQLI_TYPE_SHORT && $result->flags & MYSQLI_NUM_FLAG) {
						$type = 'smallint';
					} else if ($result->type == MYSQLI_TYPE_INT24 && $result->flags & MYSQLI_NUM_FLAG) {
						$type = 'mediumint';
					} else if ($result->type == MYSQLI_TYPE_LONG && $result->flags & MYSQLI_NUM_FLAG) {
						$type = 'int';
					} else if ($result->type == MYSQLI_TYPE_LONGLONG && $result->flags & MYSQLI_NUM_FLAG) {
						$type = 'bigint';
					} else if ($result->type == MYSQLI_TYPE_NEWDECIMAL) {
						$type = 'decimal';
						$length -= $result->decimals; // Ignore the decimals in length
					} else if ($result->type == MYSQLI_TYPE_FLOAT) {
						$type = 'float';
					} else if ($result->type == MYSQLI_TYPE_DOUBLE) {
						$type = 'double';
					} else if ($result->type == MYSQLI_TYPE_DATE) { // not MYSQLI_TYPE_NEWDATE
						$type = 'date';
					} else if ($result->type == MYSQLI_TYPE_TIME) {
						$type = 'time';
						$length = 8;
					} else if ($result->type == MYSQLI_TYPE_DATETIME) {
						$type = 'datetime';
					} else if ($result->type == MYSQLI_TYPE_YEAR) {
						$type = 'year';
					} else if ($result->type == MYSQLI_TYPE_TIMESTAMP) {
						$type = 'timestamp';
					} else if ($result->type == MYSQLI_TYPE_STRING) { // but not MYSQLI_TYPE_ENUM or MYSQLI_TYPE_SET, surprisingly
						if ($result->flags & MYSQLI_ENUM_FLAG) {
							$type = 'enum';
						} else if ($result->flags & MYSQLI_SET_FLAG) {
							$type = 'set';
						} else {
							$type = ($binary ? 'binary' : 'char');
						}
					} else if ($result->type == MYSQLI_TYPE_BLOB) {
						if ($length <= 255) {
							$type = ($binary ? 'tinyblob' : 'tinytext'); // not MYSQLI_TYPE_TINY_BLOB
						} else if ($length <= 65535) {
							$type = ($binary ? 'blob' : 'text'); // not MYSQLI_TYPE_TINY_BLOB
						} else if ($length <= 16777215) {
							$type = ($binary ? 'mediumblob' : 'mediumtext');
						} else {
							$type = ($binary ? 'longblob' : 'longtext'); // not MYSQLI_TYPE_TINY_BLOB
						}
					} else if ($result->type == MYSQLI_TYPE_VAR_STRING) {
						$type = ($binary ? 'varbinary' : 'varchar');
					}

					if ($type === NULL) {
						exit_with_error('Unknown type (' . $result->type . ') found on the "' . $result->name . '" field.');
					}

					$details[$result->name] = array(
							'type' => $type,
							'length' => $length,
							'collation' => $collation,
							'null' => !(MYSQLI_NOT_NULL_FLAG & $result->flags),
							// 'default' => $result->def, // Currently an empty string for everything
						);

					// Not available: extra, options (enum/set values), definition

				}

				if ($field !== NULL) {
					return array_pop($details);
				} else {
					return $details;
				}

			} else {

				$table_sql = $item;

				if ($field !== NULL) {
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

					$null = ($row['Null'] == 'YES');
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
					} else if ($type == 'timestamp') {
						$length = 19;
					} else if ($type == 'float' || $type == 'double') {
						$length = NULL; // Not really applicable
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
							'null' => $null,
							'default' => $row['Default'],
							'extra' => $row['Extra'],
							'options' => $options,
							'definition' => $row['Type'] . ($null ? ' NULL' : ' NOT NULL') . ($row['Default'] ? ' DEFAULT "' . $this->escape($row['Default']) . '"' : '') . ($row['Extra'] ? ' ' . $row['Extra'] : ''),
						);

				}

				if ($field !== NULL) {

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

		}

		public function insert_id() {
			return mysqli_insert_id($this->link); // Do not use $this->statement->insert_id as this DOES NOT get updated if running multiple INSERT's
		}

		public function affected_rows() {
			if ($this->affected_rows !== NULL) {
				return $this->affected_rows;
			} else {
				return mysqli_affected_rows($this->link);
			}
		}

		public function enum_values($table_sql, $field) {
			$field_info = $this->fetch_fields($table_sql, $field);
			return $field_info['options'];
		}

		public function insert($table_sql, $parameters, $on_duplicate = NULL) {
			$this->_insert($table_sql, $parameters, $on_duplicate, false);
		}

		public function insert_delayed($table_sql, $parameters, $on_duplicate = NULL) {
			$this->_insert($table_sql, $parameters, $on_duplicate, true);
		}

		private function _insert($table_sql, $values, $on_duplicate, $delayed) {

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

			if ($delayed) {
				$insert_sql = 'INSERT DELAYED';
			} else {
				$insert_sql = 'INSERT';
			}

			$insert_sql .= ' INTO ' . $table_sql . ' (' . $fields_sql . ') VALUES (' . $values_sql . ')';

			if ($on_duplicate !== NULL) {
				if (is_array($on_duplicate)) {

					$set_sql = array();
					foreach ($on_duplicate as $field_name => $field_value) {
						$set_sql[] = $this->escape_field($field_name) . ' = ' . $this->escape_string($field_value);
					}
					$insert_sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $set_sql);

				} else {

					$insert_sql .= ' ON DUPLICATE KEY UPDATE ' . $on_duplicate; // Dangerous but allows "count = (count + 1)"

				}
			}

			return $this->query($insert_sql);

		}

		public function insert_many($table_sql, $records) {

			$fields = array_keys(reset($records));

			$fields_sql = implode(', ', array_map(array($this, 'escape_field'), $fields));

			$records_sql = array();
			foreach ($records as $values) {
				$values_sql = array();
				foreach ($fields as $field) {
					if (!isset($values[$field]) || $values[$field] === NULL) {
						$values_sql[] = 'NULL';
					} else {
						$values_sql[] = $this->escape_string($values[$field]);
					}
				}
				$records_sql[] = implode(', ', $values_sql);
			}

			if (count($records_sql) > 0) {
				return $this->query('INSERT INTO ' . $table_sql . ' (' . $fields_sql . ') VALUES (' . implode('), (', $records_sql) . ')');
			}

		}

		public function update($table_sql, $values, $where_sql) {

			$set_sql = array();
			foreach ($values as $field_name => $field_value) {
				$set_sql[] = $this->escape_field($field_name) . ' = ' . ($field_value === NULL ? 'NULL' : $this->escape_string($field_value));
			}
			$set_sql = implode(', ', $set_sql);

			return $this->query('UPDATE ' . $table_sql . ' SET ' . $set_sql . ' WHERE ' . $where_sql);

		}

		public function select($table_sql, $fields, $where_sql, $options = array()) { // Table first, like all other methods

			if ($fields === 1) {
				$fields_sql = '1';
			} else if ($fields == 'count') {
				$fields_sql = 'COUNT(*) AS "count"';
			} else if ($fields === NULL) {
				$fields_sql = '*';
			} else {
				$fields_sql = implode(', ', array_map(array($this, 'escape_field'), array_unique($fields)));
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
			$this->connect();
			return $this->link;
		}

		public function version_get() {
			$this->connect();
			return $this->link->server_info;
		}

		private function connect() {

			if (!$this->link) {

				$prefix = 'db.';
				if ($this->connection != 'default') {
					$prefix .= $this->connection . '.';
				}

				$name = config::get($prefix . 'name');
				$user = config::get($prefix . 'user');
				$pass = config::get($prefix . 'pass');
				$host = config::get($prefix . 'host');

				if ($pass === NULL) {
					$password_path = (defined('UPLOAD_ROOT') ? UPLOAD_ROOT : ROOT) . '/private/passwords/database.txt'; // Could also go into `/private/config/server.ini`, but this will appear in debug output (although it should show the value '???').
					if (is_readable($password_path)) {
						$pass = trim(file_get_contents($password_path));
					} else {
						if (is_file($password_path)) {
							$this->_error('Cannot read database password file', true);
						} else {
							$this->_error('Unknown database password (config "' . $prefix . 'pass")', true);
						}
					}
				}

				if (!function_exists('mysqli_connect')) {
					$this->_error('PHP does not have MySQLi support - https://php.net/mysqli_connect', true);
				}

				$this->link = @mysqli_connect($host, $user, $pass, $name);
				if (!$this->link) {
					$this->_error('Database connection error: ' . mysqli_connect_error() . ' (' . mysqli_connect_errno() . ')', true);
				}

				if (config::get('output.charset') == 'UTF-8') {
					$charset = 'utf8';
				} else if (config::get('output.charset') == 'ISO-8859-1') {
					$charset = 'latin1';
				} else {
					$charset = NULL;
				}

				if ($charset !== NULL) {
					if (!mysqli_set_charset($this->link, $charset)) {
						$this->_error('Database charset error, when loading ' . $charset, true);
					}
				}

			}

		}

		public function error_get() {
			if ($this->statement && $this->statement->errno > 0) {
				return $this->statement->errno . ': ' . $this->statement->error;
			} else if ($this->link) {
				return mysqli_errno($this->link) . ': ' . mysqli_error($this->link);
			} else {
				return NULL;
			}
		}

		public function _error($query = 'N/A', $use_query_as_error = false) { // Public so debug script can call

			$extra = $this->error_get();

			if (function_exists('exit_with_error') && config::get('db.error_thrown') !== true) {
				config::set('db.error_thrown', true);
				exit_with_error('An error has occurred with the database.', $query . "\n\n" . $extra);
			} else if (REQUEST_MODE == 'cli') {
				exit('I have a problem: ' . ($use_query_as_error ? $query : $extra) . "\n");
			} else {
				http_response_code(500);
				exit('<p>I have a problem: <br />' . htmlentities($use_query_as_error ? $query : $extra) . '</p>');
			}

		}

	}

?>