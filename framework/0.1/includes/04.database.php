<?php

	class db extends check {

		const SKIP_DEBUG = 1;
		const SKIP_LITERAL_CHECK = 2;
		const SKIP_ERROR_HANDLER = 4;
		const SKIP_CACHE = 8;

		private $result = NULL;
		private $statement = NULL;
		private $affected_rows = NULL;
		private $connection = NULL;
		private $link = NULL;
		private $structure_table_cache = [];
		private $structure_field_cache = [];
		private $match_min_len_cache = NULL;

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
			if (!is_array($field)) { // Use an array for the table alias
				if (function_exists('is_literal')) {
					$field = [$field];
				} else {
					$field = explode('.', $field); // Legacy
				}
			}
			$field_sql = '';
			foreach ($field as $name) {
				if (function_exists('is_literal') && is_literal($name) !== true) {
					exit_with_error('The field name "' . $name . '" must be a literal');
				}
				if (strpos($name, '`') !== false) {
					exit_with_error('The field name "' . $name . '" cannot contain a backtick character');
				}
				if ($field_sql !== '') {
					$field_sql .= '.';
				}
				$field_sql .= '`' . $name . '`';
			}
			return $field_sql;
		}

		private function _escape_field_non_literal($field) {
			if (strpos($field, '`') !== false) {
				exit_with_error('The field name "' . $field . '" cannot contain a backtick character');
			}
			return '`' . $field . '`';
		}

		public function escape_table($table) {
			if (function_exists('is_literal') && is_literal($table) !== true) {
				exit_with_error('The table name "' . $table . '" must be a literal');
			}
			if (strpos($table, '`') !== false) {
				exit_with_error('The table name "' . $table . '" cannot contain a backtick character');
			}
			return '`' . $table . '`';
		}

		public function sql_implode($sql_parts, $sql_type, $default = 'true') {
			$sql = '';
			foreach ($sql_parts as $sql_part) {
				if ($sql !== '') {
// Concat to keep is_literal() happy...
					$sql = $sql . ') ' . $sql_type . ' (';
				}
				$sql .= $sql_part;
			}
			if ($sql !== '') {
				return '(' . $sql . ')';
			} else {
				return $default;
			}
		}

		public function parameter_like(&$parameters, $val, $count = 0) { // $db->parameter_like($parameters, $word, 2);
			$val = str_replace('\\', '\\\\', $val);
			$val = str_replace('_', '\_', $val);
			$val = str_replace('%', '\%', $val);
			$parameters = array_merge($parameters, array_fill(0, $count, '%' . $val . '%'));
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
				$parameters[] = [$type, $value];
			}
			$sql = '?';
			for ($k = 1; $k < $count; $k++) { // Must use concat for is_literal() check
				$sql .= ',?';
			}
			return $sql;
		}

		public function query($sql, $parameters = NULL, $skip_flags = NULL) {

			if (SERVER == 'stage' && !($skip_flags & self::SKIP_LITERAL_CHECK) && function_exists('is_literal') && !is_literal($sql)) {
				foreach (debug_backtrace() as $called_from) {
					if (isset($called_from['file']) && !str_starts_with($called_from['file'], FRAMEWORK_ROOT)) {
						break;
					}
				}
				echo "\n";
				echo '<div>' . "\n";
				echo '	<h1>Error</h1>' . "\n";
				echo '	<p><strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')</p>' . "\n";
				echo '	<p>The following SQL has been tainted.</p>' . "\n";
				echo '	<hr />' . "\n";
				echo '	<p><pre>' . "\n\n" . html($sql) . "\n\n" . '</pre></p>' . "\n";
				echo '</div>' . "\n";
				exit();
			}

			if (!($skip_flags & self::SKIP_DEBUG) && function_exists('debug_database')) {
				$this->result = debug_database($this, $sql, $parameters, $skip_flags);
				return $this->result;
			}

			if ($skip_flags & self::SKIP_CACHE) {
				$sql = preg_replace('/^\W*SELECT/', '$0 SQL_NO_CACHE', $sql);
			}

			$error = NULL;

			$this->connect();

			try {

				$time_start = microtime(true);

				if (function_exists('mysqli_execute_query')) {

					if ($parameters) { // Remove specified types, e.g. $parameters[] = ['i', $var]; ... no longer used
						foreach ($parameters as $key => $value) {
							if (is_array($value)) {
								$parameters[$key] = $value[1];
							}
						}
					}

					$this->result = mysqli_execute_query($this->link, $sql, $parameters);

				} else if (function_exists('mysqli_stmt_get_result')) { // When mysqlnd is installed - There is no way I'm using bind_result(), where the values from the database should stay in their array (ref fetch_assoc), and work around are messy.

					if ($this->statement) {
						$this->statement->close(); // Must be cleared before re-assigning... https://bugs.php.net/bug.php?id=78932
					}

					$this->statement = mysqli_prepare($this->link, $sql);

					if ($this->statement) {

						if ($parameters) {
							$ref_types = '';
							$ref_values = [];
							foreach ($parameters as $key => $value) {
								if (is_array($value)) { // Type specified, e.g. $parameters[] = ['i', $var];
									$ref_types .= $value[0];
									$ref_values[] = &$parameters[$key][1]; // Must be a reference
								} else {
									$ref_types .= (is_int($value) ? 'i' : 's'); // 'd' for double, or 'b' for blob.
									$ref_values[] = &$parameters[$key];
								}
							}
							if (count($ref_values) != $this->statement->param_count) {
								throw new mysqli_sql_exception('Invalid parameter count', 2034);
							} else {
								array_unshift($ref_values, $ref_types);
								call_user_func_array(array($this->statement, 'bind_param'), $ref_values);
							}
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
								if (is_array($parameters[$k])) { // Type specified, e.g. $parameters[] = ['i', $var];
									$sql_type = $parameters[$k][0];
									$sql_value = $parameters[$k][1];
								} else {
									$sql_type = (is_int($parameters[$k]) ? 'i' : 's');
									$sql_value = $parameters[$k];
								}
								if ($sql_type === 'i') {
									$sql_value = intval($sql_value);
								} else {
									$sql_value = $this->escape_string($sql_value);
								}
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

				$query_time = round((microtime(true) - $time_start), 3);

				config::set('db.query_time', (config::get('db.query_time', 0) + $query_time));

			} catch (exception $e) {

				$this->statement = NULL;
				$this->result = false;

				$error = $e;

			}

			if (!$this->result && !($skip_flags & self::SKIP_ERROR_HANDLER)) {
				$this->_error($error, $sql, $parameters);
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
				$parameters = [];

				if ($field !== NULL) {
					if (function_exists('is_literal') && is_literal($field) !== true) {
						exit_with_error('The field name "' . $field . '" must be a literal');
					}
					$sql .= ' LIKE "' . $this->escape_like($field) . '"'; // Cannot use parameters in a SHOW query.
				}

				$details = [];

				$result = $this->query($sql, $parameters, (db::SKIP_LITERAL_CHECK));

				foreach ($this->fetch_all($result) as $row) {

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

		public function insert($table_sql, $values, $on_duplicate = NULL, $on_duplicate_parameters = []) {
			$this->_insert($table_sql, $values, $on_duplicate, $on_duplicate_parameters);
		}

		public function insert_delayed($table_sql, $values, $on_duplicate = NULL, $on_duplicate_parameters = []) {
			trigger_error('The use of $db->insert_delayed() is deprecated, as INSERT DELAYED is not supported in MySQL 5.7', E_USER_NOTICE);
			$this->_insert($table_sql, $values, $on_duplicate, $on_duplicate_parameters);
		}

		private function _insert($table_sql, $values, $on_duplicate = NULL, $on_duplicate_parameters = []) {

			$parameters = [];

			if (!is_array($values)) {
				exit_with_error('An array of field values needs to be provided to the database.', 'Value: ' . debug_dump($values));
			}

			$fields_sql = '';
			foreach ($values as $field => $value) {
				if ($fields_sql !== '') {
					$fields_sql .= ', ';
				}
				$fields_sql .= $this->_escape_field_non_literal($field);
			}

			$values_sql = '';
			foreach ($values as $value) {
				if ($values_sql !== '') {
					$values_sql .= ', ';
				}
				if ($value === NULL) {
					$values_sql .= 'NULL';
				} else {
					$values_sql .= '?';
					$parameters[] = $value;
				}
			}

			$sql = 'INSERT INTO ' . $table_sql . ' (' . $fields_sql . ') VALUES (' . $values_sql . ')';

			if ($on_duplicate !== NULL) {
				if (is_array($on_duplicate)) {

					$set_sql = '';
					foreach ($on_duplicate as $field_name => $field_value) {
						if ($set_sql !== '') {
							$set_sql .= ', ';
						}
						if ($field_value === NULL) {
							$set_sql .= $this->_escape_field_non_literal($field_name) . ' = NULL';
						} else {
							$set_sql .= $this->_escape_field_non_literal($field_name) . ' = ?';
							$parameters[] = $field_value;
						}
					}
					$sql .= ' ON DUPLICATE KEY UPDATE ' . $set_sql;

				} else {

					if (function_exists('is_literal') && is_literal($on_duplicate) !== true) {
						exit_with_error('The on_duplicate string "' . $on_duplicate . '" must be a literal');
					}

					$sql .= ' ON DUPLICATE KEY UPDATE ' . $on_duplicate; // Dangerous but allows "count = (count + 1)"

					$parameters = array_merge($parameters, $on_duplicate_parameters);

				}
			}

			return $this->query($sql, $parameters, self::SKIP_LITERAL_CHECK); // Accept non-literals to support 'SELECT * FROM table', modify some values, and then db->insert($copy)... otherwise use $db->query($sql, $parameters, (db::SKIP_LITERAL_CHECK));

		}

		public function insert_many($table_sql, $records) {

			$parameters = [];

			reset($records);
			$first = key($records);
			$fields_sql = '';
			$fields = [];
			foreach ($records[$first] as $field => $value) {
				if ($fields_sql !== '') {
					$fields_sql .= ', ';
				}
				$fields_sql .= $this->escape_field($field);
				$fields[] = $field;
			}

			$records_sql = '';
			foreach ($records as $values) {
				$values_sql = '';
				foreach ($fields as $field) {
					if ($values_sql !== '') {
						$values_sql .= ', ';
					}
					if (!isset($values[$field]) || $values[$field] === NULL) {
						$values_sql .= 'NULL';
					} else {
						$values_sql .= '?';
						$parameters[] = $values[$field];
					}
				}
				if ($records_sql !== '') {
					$records_sql .= '), (';
				}
				$records_sql .= $values_sql;
			}
			if ($records_sql !== '') {

				$sql = 'INSERT INTO ' . $table_sql . ' (' . $fields_sql . ') VALUES (' . $records_sql . ')';

				return $this->query($sql, $parameters);

			}

		}

		public function update($table_sql, $values, $where_sql, $parameters = []) {

			if (!is_array($values)) {
				exit_with_error('An array of field values needs to be provided to the database.', 'Value: ' . debug_dump($values));
			}

			$set_sql = '';
			$set_parameters = [];
			foreach ($values as $field_name => $field_value) {
				if ($set_sql !== '') {
					$set_sql .= ',';
				}
				$set_sql .= $this->escape_field($field_name) . ' = ?';
				$set_parameters[] = $field_value;
			}
			$parameters = array_merge($set_parameters, $parameters);

			$sql = 'UPDATE ' . $table_sql . ' SET ' . $set_sql . ' WHERE ' . $where_sql;

			return $this->query($sql, $parameters);

		}

		public function select($table_sql, $fields, $where_sql, $options = []) { // Table first, like all other methods

			if ($fields === 1) {
				$fields_sql = '1';
			} else if ($fields == 'count') {
				$fields_sql = 'COUNT(*) AS "count"';
			} else if ($fields === NULL) {
				$fields_sql = '*';
			} else {
				$fields_sql = '';
				foreach ($fields as $field) {
					if ($fields_sql !== '') {
						$fields_sql .= ', ';
					}
					$fields_sql .= $this->escape_field($field);
				}
			}

			if (is_array($where_sql)) {
				$where_sql = $db->sql_implode($where_sql, 'AND');
			}

			$sql = 'SELECT ' . $fields_sql . ' FROM ' . $table_sql . ' WHERE ' . $where_sql;

			if (isset($options['group_sql'])) $sql .= ' GROUP BY ' . $options['group_sql'];
			if (isset($options['order_sql'])) $sql .= ' ORDER BY ' . $options['order_sql'];
			if (isset($options['limit_sql'])) $sql .= ' LIMIT '    . $options['limit_sql'];

			$parameters = (isset($options['parameters']) ? $options['parameters'] : NULL);

			return $this->query($sql, $parameters);

		}

		public function delete($table_sql, $where_sql, $parameters = []) {

			$sql = 'DELETE FROM ' . $table_sql . ' WHERE ' . $where_sql;

			return $this->query($sql, $parameters);

		}

		public function match_boolean_words($search) {

				// To Test this logic, set a field that's at least 35 characters long to:
				//
				//    ABC_DEF GHI+JKL-MNO~PQR>STU<VWX@YZA
				//    ABC_DEF GHI'JKL"MNO.PQR,STU(VWX)YZA
				//
				// Setup a script to use:
				//
				//    $db->parameter_match_boolean_all($parameters, 'field', $search);
				//
				// Check that "ABC_DEF" works, and "ABC DEF" does not (because DEF is not the beginning of a word).
				//
				// Check each set of 7 characters will search with 2 words, and a result is returned, e.g.
				//
				//    GHI+JKL ... MATCH (`field`) AGAINST ("+GHI +JKL" IN BOOLEAN MODE)
				//    JKL-MNO
				//    MNO~PQR

			$search = str_replace([
					'+', // AND Operator, cannot be part of the word (isn't in the FULLTEXT INDEX).
					'-', // NOT Operator
					'~', // Negation Operator
					'>', // Increase contribution.
					'<', // Decrease contribution.
					'@', // InnoDB uses as a @distance proximity search operator (MyISAM ignores)
					"'", // Apostrophes are seen as word separators (not good for "O'Brien"); Bug report https://jira.mariadb.org/browse/MDEV-20797
					'"', // Double quotes are used to type the phrase literally, as it was typed.
					'.', // Treated as a word separator. When using BOOLEAN MODE with the AND operator, "a.name@example.com" would be seen as "+a" and "name@example.com" (also note min length requirements)
					',', // Treated as a word separator
					'*', // Wildcard operator
					'(', // Brackets are used for grouping and subexpressions; and they can also cause a syntax error, e.g. "aaa(bbb".
					')',
				], ' ', strval($search));

			$words = split_words($search);

			if ($this->match_min_len_cache === NULL) {
				foreach ($this->fetch_all('SHOW VARIABLES LIKE "ft_min_word_len"') as $row) { // Hopefully the same as "innodb_ft_min_token_size"
					$this->match_min_len_cache = intval($row['Value']);
				}
			}
			if ($this->match_min_len_cache > 0) {
				$valid_words = [];
				foreach ($words as $word) {
					if (strlen($word) >= $this->match_min_len_cache) { // Any words marked as required (with a '+') and are too short, will simply return no results, so we should ignore them in the same way.
						$valid_words[] = $word;
					}
				}
				$words = $valid_words;
			}

			return $words;

		}

		private function _match_boolean_all($field, $search, &$parameters = NULL) {
			$search_query = [];
			if (!is_array($search)) {
				$search = $this->match_boolean_words($search);
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
					$fields_sql = '';
					foreach ($field as $name) {
						if ($fields_sql !== '') {
							$fields_sql .= ', ';
						}
						$fields_sql .= $this->escape_field($name);
					}
				} else {
					$fields_sql = $this->escape_field($field);
				}
				if ($parameters !== NULL) {
					$parameters[] = $search_query;
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

				$config = [
						'name'       => config::get('db.name'),
						'host'       => config::get('db.host'),
						'user'       => config::get('db.user'),
						'pass'       => secrets::get('db.pass'),
						'persistent' => config::get('db.persistent'),
						'ca_file'    => config::get('db.ca_file'),
					];

				if ($config['pass'] === NULL) {
					$config['pass'] = config::get_decrypted('db.pass'); // TODO [secrets-cleanup], where this also effectively does config::get()
				}

				if ($this->connection != 'default') {
					$config = array_merge($config, config::get_all('db.' . $this->connection));
					if ($config['pass'] === NULL) {
						$config['pass'] = secrets::get('db.' . $this->connection . '.pass');
					}
					if ($config['pass'] === NULL) {
						$config['pass'] = config::get_decrypted('db.' . $this->connection . '.pass'); // TODO [secrets-cleanup], where this also effectively does config::get()
					}
				}

				if ($config['pass'] === NULL) {
					$this->_error('Unknown database password (config "db.pass")');
				}

				if (!function_exists('mysqli_real_connect')) {
					$this->_error('PHP does not have MySQLi support');
				}

			//--------------------------------------------------
			// Report mode

				mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

			//--------------------------------------------------
			// Link

				$this->link = mysqli_init();

				if ($config['ca_file']) {
					mysqli_ssl_set($this->link, NULL, NULL, $config['ca_file'], NULL, NULL);
				}

			//--------------------------------------------------
			// Connect

				$k = 0;
				$error_number = NULL;
				$error_messages = [];
				$error_reporting = error_reporting(0); // With PHP 8.1 and/or MariaDB 10.6; with persistent (SSL?) connections, to a remote server, there is a fair amount of "PHP Warning: mysqli_real_connect(): SSL: Connection reset by peer"

				$host = NULL;
				$host_log = [];
				$start = microtime(true);

				do {

					if ($k > 0) {
						usleep(500000); // Half a second
					}

					$host = $config['host'];
					if ($host !== 'localhost') {
						if ($k === 0) {
							// AWS RDS uses DNS with a 5 second TTL, and a ~60ms lookup time (not good when you have a processing budget of 100ms).
							$dns_cache_dir = '/etc/dns-cache/';
							$dns_cache_path = realpath($dns_cache_dir . $host);
							if ($dns_cache_path !== false && str_starts_with($dns_cache_path, $dns_cache_dir)) { // Can't see how this would happen, but ensure we are still within the dir (e.g. host does not start "../")
								$host = trim(file_get_contents($dns_cache_path));
								if ($host === '') {
									$host = $config['host'];
								}
							}
						}
						if (($config['persistent'] ?? true) === true) {
							$host = 'p:' . $host;
						}
					}
					$host_log[] = $host;

					try {
						if ($config['ca_file']) {
							$result = mysqli_real_connect($this->link, $host, $config['user'], $config['pass'], $config['name'], NULL, NULL, MYSQLI_CLIENT_SSL);
						} else {
							$result = mysqli_real_connect($this->link, $host, $config['user'], $config['pass'], $config['name']);
						}
					} catch (mysqli_sql_exception $e) {
						$result = false;
						$error_number = $e->getCode();
						$error_messages[] = $error_number . ': ' . $e->getMessage();
						$host_log[] = $error_number;
					}

				} while (!$result && ($error_number == 2002 || $error_number == 1045) && SERVER != 'stage' && (++$k < 3));

					// 2002 Connection error, e.g. "Temporary failure in name resolution" or "Can't connect to local MySQL server through socket"
					// 1045 Access denied for user, e.g. using persistent connections and the remote server restarts.

				if (!$result) {

					config::set('db.error_connect', true);
					$this->link = NULL;
					$this->_error('Database connection error:' . "\n - " . implode("\n - ", $error_messages));

				} else if ($error_messages) {

					report_add('Temporary database connection error:' . "\n\n" . implode("\n\n", $error_messages));

				}

				error_reporting($error_reporting);

			//--------------------------------------------------
			// Slow

				$time = round((microtime(true) - $start), 5);
				if (function_exists('debug_log_time') && $time > 0.01) {
					debug_log_time('DBC', $time);
				}
				if (config::get('debug.level') >= 4) {
					debug_progress('Database Connect ' . $time . ($config['ca_file'] ? ' +TLS' : '') . ' = ' . implode(', ', $host_log));
				}

			//--------------------------------------------------
			// Charset

				$collation = config::get('db.collation');
				if (($pos = strpos(strval($collation), '_')) !== false) {
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

		public function error_reset() {
			config::set('db.error_thrown', false);
		}

		private function _error($error, $sql = NULL, $parameters = NULL) {

			$error_code = NULL;

			if ($error instanceof exception) {
				if ($error instanceof mysqli_sql_exception) {
					$error_code = $error->getCode();
				}
				$error = $error->getCode() . ': ' . $error->getMessage();
			}

			$first_error = config::get('output.error');
			if (is_array($first_error)) {
				$error = $first_error['message'] . (config::get('debug.level') > 0 ? "\n\n-----\n\n" . $first_error['hidden_info'] : '') . "\n\n-----\n\n" . $error;
			}

			$hidden_info = '';
			if ($sql) {
				$hidden_info .= "\n\n" . debug_dump($sql);
			}
			if ($parameters) {
				$hidden_info .= "\n\n" . debug_dump($parameters);
			}

			if (class_exists('error_exception') && config::get('db.error_thrown') !== true && $first_error === NULL) {

				config::set('db.error_thrown', true);

				if (config::get('debug.level') > 0) {

					debug_note([
							'type' => 'L',
							'colour' => '#FCC',
							'class'  => 'debug_sql',
							'heading' => $error,
							'text' => $sql . ($parameters ? "\n\n" . debug_dump($parameters) : ''),
						]);

				}

				if ($error_code == 1062) {
					throw new db_duplicate_entry_exception('An error has occurred with the database.', $error . $hidden_info);
				}

				throw new error_exception('An error has occurred with the database.', $error . $hidden_info);

			} else if (REQUEST_MODE == 'cli') {

				exit('I have a problem.' . "\n\n" . $error . $hidden_info . "\n\n");

			} else {

				if (config::get('debug.level') > 0) {
					$error .= $hidden_info;
				}

				http_response_code(500);
				exit('<p>I have a problem.<br /><br />' . nl2br(htmlspecialchars($error, (ENT_QUOTES | ENT_SUBSTITUTE))) . '</p>');

			}

		}

	}

	class db_duplicate_entry_exception extends error_exception {
	}

?>