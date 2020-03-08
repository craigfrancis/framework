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
			return $this->_match_boolean_all($field, $search);
		}

		public function escape_field($field) {
			$field_sql = '`' . str_replace('`', '', $field) . '`'; // Back tick is an illegal character
			$field_sql = str_replace('.', '`.`', $field_sql); // Allow table definition
			return $field_sql;
		}

		public function escape_table($table) {
			return '`' . str_replace('`', '', $table) . '`';
		}

		public function parameter_like(&$parameters, $val, $count = 0) { // $db->parameter_like($parameters, $word, 2);
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace('_', '\_', $val);
			$val = str_replace('%', '\%', $val);
			$parameters = array_merge($parameters, array_fill(0, $count, array('s', '%' . $val . '%')));
		}

		public function parameter_match_boolean_all(&$parameters, $field, $search) {
			return $this->_match_boolean_all($field, $search, $parameters);
		}

		public function parameter_in(&$parameters, $type, $values) { // $in_sql = $db->parameter_in($parameters, 'i', $ids);
			$count = count($values);
			if ($count == 0) {
				exit_with_error('Do not run a query looking for multiple items, when there are no items to find');
			}
			if ($type == 'i') {
				$values = array_map('intval', $values);
			} else if ($type == 's') {
				$values = array_map('strval', $values);
			} else {
				exit_with_error('Unknown parameter type for parameter_in(), should be "i" or "s"');
			}
			foreach ($values as $value) {
				$parameters[] =  array($type, $value);
			}
			return substr(str_repeat('?,', $count), 0, -1);
		}

		public function query($sql, $parameters = NULL, $run_debug = true, $exit_on_error = true) {

			if ($parameters === false) {
				trigger_error('Second parameter in query() is for SQL parameters', E_USER_NOTICE);
				$parameters = NULL;
				$run_debug = false;
			}

			$this->connect();

			if ($run_debug && function_exists('debug_database')) {

				$this->result = debug_database($this, $sql, $parameters, $exit_on_error);

			} else {

				if (function_exists('debug_log_db')) {
					$time_start = microtime(true);
				} else {
					$time_start = NULL;
				}

				if (function_exists('mysqli_stmt_get_result')) { // When mysqlnd is installed - There is no way I'm using bind_result(), where the values from the database should stay in their array (ref fetch_assoc), and work around are messy.

					if ($this->statement) {
						$this->statement->close(); // Must be cleared before re-assigning... https://bugs.php.net/bug.php?id=78932
					}

					$this->statement = mysqli_prepare($this->link, $sql);

					if ($this->statement) {

						if ($parameters) {
							$ref_values = array(implode('', array_column($parameters, 0)));
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
							$this->statement = NULL;
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

				if ($time_start) {
					debug_log_db(round((microtime(true) - $time_start), 3), $sql); // This is higher than `debug.time_query`, because debug does not run for every query (e.g. SHOW COLUMNS and EXPLAIN)
				}

			}

			if (!$this->result && $exit_on_error) {
				$this->_error($sql, $parameters, true);
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
			$data = [];
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

				$details = [];

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
						$length = floor($result->length / 3); // "maximum of three bytes per character" https://dev.mysql.com/doc/refman/5.5/en/charset-unicode-utf8mb4.html

					} else if ($result->charsetnr == 45) {

						$collation = 'utf8mb4_unicode_ci';
						$length = floor($result->length / 4);

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
					$sql .= ' LIKE "' . $this->escape_like($field) . '"'; // Cannot use parameters in a SHOW query.
				}

				$details = [];

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
						$this->_error('Unknown type "' . $row['Type'] . '" for field "' . $row['Field'] . '"');
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
						$this->_error('Cannot find field "' . $field . '" in table "' . $table_sql . '"');
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

		public function insert($table_sql, $values, $on_duplicate = NULL) {
			$this->_insert($table_sql, $values, $on_duplicate);
		}

		public function insert_delayed($table_sql, $values, $on_duplicate = NULL) {
			trigger_error('The use of $db->insert_delayed() is deprecated, as INSERT DELAYED is not supported in MySQL 5.7', E_USER_NOTICE);
			$this->_insert($table_sql, $values, $on_duplicate);
		}

		private function _insert($table_sql, $values, $on_duplicate) {

			$parameters = [];

			if (!is_array($values)) {
				exit_with_error('An array of field values needs to be provided to the database.', 'Value: ' . debug_dump($values));
			}

			$fields_sql = implode(', ', array_map(array($this, 'escape_field'), array_keys($values)));

			$values_sql = [];
			foreach ($values as $value) {
				if ($value === NULL) {
					$values_sql[] = 'NULL';
				} else {
					$values_sql[] = '?';
					if (is_int($value)) {
						$parameters[] = ['i', $value];
					} else if (is_float($value)) {
						$parameters[] = ['d', $value];
					} else {
						$parameters[] = ['s', $value];
					}
				}
			}

			$insert_sql = 'INSERT INTO ' . $table_sql . ' (' . $fields_sql . ') VALUES (' . implode(', ', $values_sql) . ')';

			if ($on_duplicate !== NULL) {
				if (is_array($on_duplicate)) {

					$set_sql = [];
					foreach ($on_duplicate as $field_name => $field_value) {
						if ($field_value === NULL) {
							$set_sql[] = $this->escape_field($field_name) . ' = NULL';
						} else {
							$set_sql[] = $this->escape_field($field_name) . ' = ?';
							if (is_int($field_value)) {
								$parameters[] = ['i', $field_value];
							} else if (is_float($field_value)) {
								$parameters[] = ['d', $field_value];
							} else {
								$parameters[] = ['s', $field_value];
							}
						}
					}
					$insert_sql .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $set_sql);

				} else {

					$insert_sql .= ' ON DUPLICATE KEY UPDATE ' . $on_duplicate; // Dangerous but allows "count = (count + 1)"

				}
			}

			return $this->query($insert_sql, $parameters);

		}

		public function insert_many($table_sql, $records) {

			$parameters = [];

			$fields = array_keys(reset($records));

			$fields_sql = implode(', ', array_map(array($this, 'escape_field'), $fields));

			$records_sql = [];
			foreach ($records as $values) {
				$values_sql = [];
				foreach ($fields as $field) {
					if (!isset($values[$field]) || $values[$field] === NULL) {
						$values_sql[] = 'NULL';
					} else {
						$values_sql[] = '?';
						if (is_int($values[$field])) {
							$parameters[] = ['i', $values[$field]];
						} else if (is_float($values[$field])) {
							$parameters[] = ['d', $values[$field]];
						} else {
							$parameters[] = ['s', $values[$field]];
						}
					}
				}
				$records_sql[] = implode(', ', $values_sql);
			}

			if (count($records_sql) > 0) {
				return $this->query('INSERT INTO ' . $table_sql . ' (' . $fields_sql . ') VALUES (' . implode('), (', $records_sql) . ')', $parameters);
			}

		}

		public function update($table_sql, $values, $where_sql, $parameters = []) {

			if (!is_array($values)) {
				exit_with_error('An array of field values needs to be provided to the database.', 'Value: ' . debug_dump($values));
			}

			$set_sql = [];
			$set_parameters = [];
			foreach ($values as $field_name => $field_value) {
				$set_sql[] = $this->escape_field($field_name) . ' = ?';
				$set_parameters[] = ['s', $field_value];
			}

			$sql = 'UPDATE ' . $table_sql . ' SET ' . implode(', ', $set_sql) . ' WHERE ' . $where_sql;

			return $this->query($sql, array_merge($set_parameters, $parameters));

		}

		public function select($table_sql, $fields, $where_sql, $options = []) { // Table first, like all other methods

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

			return $this->query($sql, (isset($options['parameters']) ? $options['parameters'] : NULL));

		}

		public function delete($table_sql, $where_sql, $parameters = []) {

			$sql = 'DELETE FROM ' . $table_sql . ' WHERE ' . $where_sql;

			return $this->query($sql, $parameters);

		}

		private function _match_boolean_all($field, $search, &$parameters = NULL) {
			$search_query = [];
			if (!is_array($search)) {
				$search = split_words($search);
			}
			foreach ($search as $word) {
				$char = substr($word, 0, 1);
				if ($char == '-' || $char == '+') {
					$search_query[] = $word;
				} else {
					$search_query[] = '+' . $word; // Default to an AND in BOOLEAN MATCH, otherwise it's seen as an OR
				}
			}
			$search_query = implode(' ', $search_query);
			if ($search_query) {
				if (is_array($field)) {
					$fields_sql = implode(', ', array_map([$this, 'escape_field'], $field));
				} else {
					$fields_sql = $this->escape_field($field);
				}
				if ($parameters !== NULL) {
					$parameters[] = ['s', $search_query];
					return 'MATCH (' . $fields_sql . ') AGAINST (? IN BOOLEAN MODE)';
				} else {
					return 'MATCH (' . $fields_sql . ') AGAINST ("' . $this->escape($search_query) . '" IN BOOLEAN MODE)';
				}
			} else {
				return 'false';
			}
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

			//--------------------------------------------------
			// Connected

				if ($this->link) {
					return;
				}

			//--------------------------------------------------
			// Config

				$prefix = 'db.';
				if ($this->connection != 'default') {
					$prefix .= $this->connection . '.';
				}

				$name = config::get($prefix . 'name');
				$host = config::get($prefix . 'host');
				$user = config::get($prefix . 'user');

// TODO: /private/secrets/
				$pass = config::get_decrypted($prefix . 'pass');

				if ($pass === NULL) {
					$this->_error('Unknown database password (config "' . $prefix . 'pass")');
				}

				if ($host != 'localhost' && config::get($prefix . 'persistent', true) === true) {
					$host = 'p:' . $host;
				}

				if (!function_exists('mysqli_real_connect')) {
					$this->_error('PHP does not have MySQLi support');
				}

				$start = microtime(true);

			//--------------------------------------------------
			// Link

				$this->link = mysqli_init();

				$ca_file = config::get($prefix . 'ca_file');
				if ($ca_file) {
					mysqli_ssl_set($this->link, NULL, NULL, $ca_file, NULL, NULL);
				}

			//--------------------------------------------------
			// Connect

				$k = 0;
				$error_number = NULL;
				$error_messages = [];

				do {

					if ($k > 0) {
						usleep(500000); // Half a second
					}

					if ($ca_file) {
						$result = @mysqli_real_connect($this->link, $host, $user, $pass, $name, NULL, NULL, MYSQLI_CLIENT_SSL);
					} else {
						$result = @mysqli_real_connect($this->link, $host, $user, $pass, $name);
					}
					if (!$result) {
						$error_number = mysqli_connect_errno();
						$error_messages[] = mysqli_connect_error() . ' (' . $error_number . ')';
					}

				} while (!$result && ($error_number == 2002 || $error_number == 1045) && SERVER != 'stage' && (++$k < 3));

					// 2002 Connection error, e.g. "Temporary failure in name resolution" or "Can't connect to local MySQL server through socket"
					// 1045 Access denied for user, e.g. using persistent connections and the remote server restarts.

				if (!$result) {

					config::set('db.error_connect', true);
					$this->link = NULL;
					$this->_error('Database connection error:' . "\n\n" . implode("\n\n", $error_messages));

				} else if ($error_messages) {

					report_add('Temporary database connection error:' . "\n\n" . implode("\n\n", $error_messages));

				}

			//--------------------------------------------------
			// Slow

				$time = round((microtime(true) - $start), 5);
				if (function_exists('debug_log_time') && $time > 0.01) {
					debug_log_time('DBC', $time);
				}
				if (config::get('debug.level') >= 4) {
					debug_progress('Database Connect ' . $time . ($ca_file ? ' +TLS' : ''));
				}

			//--------------------------------------------------
			// Charset

				$collation = config::get('db.collation');
				if (($pos = strpos($collation, '_')) !== false) {
					$charset = substr($collation, 0, $pos);
				} else {
					$charset = NULL;
				}

				if ($charset === NULL) {
					if (config::get('output.charset') == 'UTF-8') {
						$charset = 'utf8mb4';
					} else if (config::get('output.charset') == 'ISO-8859-1') {
						$charset = 'latin1';
					}
				}

				if ($charset !== NULL) {
					if (!mysqli_set_charset($this->link, $charset)) {
						$this->_error('Database charset error, when loading ' . $charset);
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

		public function error_reset() {
			config::set('db.error_thrown', false);
		}

		private function _error($error = 'N/A', $parameters = NULL, $show_db_error = false) {

			$info = $this->error_get();

			if (class_exists('error_exception') && config::get('db.error_thrown') !== true) {

				config::set('db.error_thrown', true);

				$hidden_info = trim($info . "\n\n" . $error);
				if ($parameters) {
					$hidden_info .= "\n\n" . debug_dump(array_column($parameters, 1));
					$hidden_info .= "\n\n" . debug_dump(array_column($parameters, 0));
				}

				throw new error_exception('An error has occurred with the database.', $hidden_info);

			} else if (REQUEST_MODE == 'cli') {

				exit('I have a problem: ' . ($show_db_error ? $info : $error) . "\n");

			} else {

				http_response_code(500);
				exit('<p>I have a problem.<br />' . nl2br(htmlentities($show_db_error ? $info : $error)) . '</p>');

			}

		}

	}

?>