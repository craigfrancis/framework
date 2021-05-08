<?php

//--------------------------------------------------
// Setup

	//--------------------------------------------------
	// Setup

		config::set_default('debug.report_values', []);
		config::set('debug.notes', []);

	//--------------------------------------------------
	// DB variables

		config::set('debug.time_query', 0);
		config::set('debug.time_check', 0);

//--------------------------------------------------
// Log file, and processing time

	function log_value($field, $value) {
		config::array_set('debug.log_values', $field, $value);
	}

	if (($log_file = config::get('debug.log_file')) !== NULL) {

		$time = microtime(true);
		$sec = floor($time);
		$usec = round(($time - $sec) * 1000000); // Only accurate to 6 decimal places.

		$sec -= strtotime('-' . date('w', $sec) . ' days, 00:00:00', $sec); // Time since Sunday 00:00, max value = 604800 (60*60*24*7)

		$response_ref = '';
		foreach([$sec, $usec, rand(100000, 999999)] as $ref_part) {
			$a = dechex($ref_part); // decbin returns a string
			$a = hex2bin(((strlen($a) % 2) == 0 ? '' : '0') . $a);
			$a = base64_encode_rfc4648($a);
			// $b = hexdec(bin2hex(base64_decode_rfc4648($a)));
			$response_ref .= str_pad($a, 4, '.', STR_PAD_LEFT); // 4 characters max = 999999 -> "0f423f" (hex) -> "D0I/" (base64)
		}

		log_value('ref', $response_ref); // A more compact uniqid (which uses hex encoding, and a full UNIX timestamp).

		config::set('response.ref', $response_ref);

		header('X-Response-Ref: ' . $response_ref); // So access_log can match up with log_file

	}

	unset($log_file, $time, $sec, $usec, $response_ref, $ref_part, $a);

	function log_shutdown() {
		if (!defined('FRAMEWORK_END')) { // Only run once, ref http_connection_close()

			define('FRAMEWORK_END', number_format(debug_time_elapsed(), 3, '.', ''));

			if (($log_file = config::get('debug.log_file')) !== NULL && is_writable($log_file)) {

				$response_code_value = http_response_code();

				$response_code_extra = config::get('debug.response_code_extra');
				if ($response_code_extra) {
					$response_code_value .= '-' . $response_code_extra;
				}

				log_value('time', FRAMEWORK_END);
				log_value('code', $response_code_value);

				if (($fp = fopen($log_file, 'a')) !== false) {
					fputcsv($fp, config::get('debug.log_values'));
					fclose($fp);
				}

			} else if (function_exists('apache_note')) {

				apache_note('TIME_INFO', FRAMEWORK_END);

			}

		}
	}

	register_shutdown_function('log_shutdown');

//--------------------------------------------------
// Error reporting

	function report_add($message, $type = 'notice', $send_email = true) {

		//--------------------------------------------------
		// Send an email to the admin, if necessary

			$error_email = config::get('email.error');

			if ($send_email && ($type == 'error' || $type == 'notice') && $error_email !== NULL) {

				$email_values = config::get('debug.report_values', []);
				$email_values = array_merge($email_values, array('Message' => $message));

				if ($type == 'error' && config::get('request.method') == 'POST') {
					$post_values = $_POST;
					foreach (['password', 'pass', 'csrf'] as $remove) {
						if (isset($post_values[$remove])) {
							$post_values[$remove] = '-';
						}
					}
					$email_values['Post'] = json_encode($post_values, JSON_PRETTY_PRINT);
				}

					// config::array_push('debug.report_files', ['path' => '/path/to/file.csv']);
					// config::array_push('debug.report_files', ['path' => '/path/to/file', 'name' => 'Example.csv', 'type' => 'text/plain']);
				$files = config::get('debug.report_files', []);
				foreach ($_FILES as $field => $file) {
					if (is_array($file['name'])) { // When input name is an array, e.g. <input type="file" name="example[]" />
						foreach ($file['name'] as $id => $name) {
							$field_full = $field . '[' . $id . ']';
							$row = ['name' => $name, 'type' => $file['type'][$id], 'error' => $file['error'][$id], 'size' => $file['size'][$id], 'field' => $field_full];
							$row['path'] = (config::array_get('request.file_paths', $field_full) ?? $file['tmp_name'][$id]); // If form_field_file has moved it to 'file_tmp_folder', then use 'path'.
							$files[] = $row;
						}
					} else {
						$file['field'] = $field;
						$file['path'] = (config::array_get('request.file_paths', $field) ?? $file['tmp_name']);
						$files[] = $file;
					}
				}
				$included_size = 0;
				foreach ($files as $id => $file) {
					if (is_file($file['path'] ?? NULL)) {
						if (!isset($file['name'])) $file['name'] = basename($file['path']);
						if (!isset($file['size'])) $file['size'] = filesize($file['path']);
					} else if (isset($file['data'])) {
						if (!isset($file['name'])) $file['name'] = 'N/A';
						if (!isset($file['size'])) $file['size'] = strlen($file['data']);
					} else {
						$file['include'] = false;
					}
					$value = (($file['field'] ?? '') != '' ? $file['field'] . ': ' : '') . $file['name'] . ' (' . $file['size'] . ' bytes' . (isset($file['type']) ? ', ' . $file['type'] : '') . (isset($file['error']) ? ', Error:' . $file['error'] : '') . ')';
					if (($file['include'] ?? true) === true) {
						$included_size += $file['size'];
						if ($included_size < 1048576) { // 1MB
							$data = ($file['data'] ?? file_get_contents($file['path']));
							$value .= "\n\n" . wordwrap(base64_encode($data), 75, "\n", true);
						}
					}
					$email_values['File ' . ($id + 1)] = $value;
				}

				$email = new email();
				$email->default_style_set(NULL);
				$email->subject_set('System ' . ucfirst($type) . ': ' . config::get('output.domain'));
				$email->request_table_add($email_values, 'Y-m-d H:i:s - D jS M');
				$email->send($error_email);

			}

		//--------------------------------------------------
		// Add report to the database

			if (config::get('db.host') !== NULL && config::get('db.error_connect') !== true) {

				$db = db_get();

				$db->insert(DB_PREFIX . 'system_report', array(
						'type'     => $type,
						'created'  => new timestamp(),
						'message'  => $message,
						'request'  => config::get('request.url'),
						'referrer' => config::get('request.referrer'),
						'ip'       => config::get('request.ip'),
					));

			}

	}

	function exit_with_error($message, $hidden_info = '', $type = 'error') {

		//--------------------------------------------------
		// Config

			if ($message instanceof error_exception) {
				$backtrace = $message->getBacktrace();
				$hidden_info = $message->getHiddenInfo();
				$message = $message->getMessage();
			} else {
				$backtrace = debug_backtrace();
			}

		//--------------------------------------------------
		// Called from

			array_pop($backtrace); // Remove "/public/index.php", which is pointless, and prevents v2 from running.

			foreach ([1, 2] as $try) { // v1 is to focus on project code, v2 for something that's just in the framework.
				$k = 0;
				foreach ($backtrace as $called_from) {
					if (isset($called_from['file'])) {

						if ($try == 1 && $k == 0 && str_starts_with($called_from['file'], FRAMEWORK_ROOT)) { // Where $k will remain 0 until something non-framework is found.
							continue;
						}

						$path = $called_from['file'];
						$path = prefix_replace(FRAMEWORK_ROOT, '/framework', $path);
						$path = prefix_replace(APP_ROOT, '', $path);

						if ($try == 1 && $path == '/framework/includes/09.process.php') {
							break; // No point reporting any more
						}

						$k++;

						if ($k == 1 && $hidden_info != '') {
							$hidden_info .= "\n\n";
						} else if ($k == 2) {
							$hidden_info .= "\n"; // Make the first line stand out
						}

						$hidden_info .= $path . ':' . $called_from['line'] . "\n";

					}
				}
				if ($k > 0) {
					break; // Got something, no need for $try 2
				}
			}

		//--------------------------------------------------
		// Clear output buffer

			$output = ob_get_clean_all();

			if ($output != '') {
				if ($hidden_info != '') {
					$hidden_info .= "\n\n";
				}
				$hidden_info .= $output;
			}

		//--------------------------------------------------
		// Config

			$contact_email = config::get('email.error_display'); // A different email address to show customers
			if (!$contact_email) {
				$contact_email = config::get('email.error');
			}
			if (is_array($contact_email)) {
				$contact_email = reset($contact_email);
			}

		//--------------------------------------------------
		// Email report

			$email_report = $message;

			if ($hidden_info != '') {
				$email_report .= "\n\n--------------------------------------------------\n\n";
				$email_report .= $hidden_info;
			}

		//--------------------------------------------------
		// Remove hidden info

			if ($contact_email != '' || config::get('debug.level') == 0) {
				$hidden_info = ''; // If there is an email address, don't show the hidden info (e.g. on live).
			}

		//--------------------------------------------------
		// Hidden HTML

			$hidden_html = nl2br(html($hidden_info));

			$hidden_html = preg_replace('/(You have an error in your SQL syntax; .+) near &apos;(.+?)&apos; at line [0-9]+(.*)\2/ims', '\1.\3<strong>\2</strong>', $hidden_html);

		//--------------------------------------------------
		// Record the error to avoid loops, be used with
		// the loading helper, and set the variables used
		// by the error-system.ctp template.

			$error = [
					'message' => $message,
					'hidden_info' => $hidden_info,
					'hidden_html' => $hidden_html,
					'contact_email' => $contact_email,
				];

			config::set('output.error', $error);

		//--------------------------------------------------
		// Send report.

				// This must be after output.error as it can
				// record in the database, and connection issues
				// with the database would create a second error.

			report_add($email_report, $type);

		//--------------------------------------------------
		// Tell the user

			if (config::get('output.sent', false) !== true) { // e.g. the loading helper has already sent the response.

				$cli = (php_sapi_name() == 'cli');

				if ($cli || config::get('output.mime') == 'text/plain') {

					if (!$cli) {
						http_response_code(500);
					}

					echo "\n" . '--------------------------------------------------' . "\n\n";
					echo 'System Error:' . "\n\n";
					echo $message . "\n\n";

					if ($hidden_info != '') {
						echo $hidden_info . "\n\n";
					}

					echo '--------------------------------------------------' . "\n\n";

				} else {

					if (!headers_sent()) {
						if ($type != 'notice') {
							http_response_code(500);
						}
						mime_set('text/html');
					}

					if (function_exists('response_get')) {
						$response = response_get();
					} else {
						$response = NULL;
					}

					if ($response && $response instanceof response_html && $response->error_get() === false && config::get('db.error_connect') !== true) { // Avoid looping, or using default template with db connection error.

						$response->set($error);
						$response->error_send('system');

					} else {

						echo '<!DOCTYPE html>
							<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
							<head>
								<meta charset="' . html(config::get('output.charset')) . '" />
								<title>System Error</title>
							</head>
							<body id="p_error">
								<h1>System Error</h1>
								<p>' . nl2br(html($message)) . '</p>';

						if ($hidden_info != '') {
							echo '
								<hr />
								<div>' . nl2br(html($hidden_info)) . '</div>'; // Don't use <pre>, SQL can have very long lines.
						}

						echo '
							</body>
							</html>';

					}

				}

			}

		//--------------------------------------------------
		// Exit script execution

			exit();

	}

//--------------------------------------------------
// Error exception

	class error_exception extends exception {

		protected $hidden_info;
		protected $backtrace;

		public function __construct($message, $hidden_info = '', $code = 0) {
			$this->message = $message;
			$this->hidden_info = $hidden_info;
			$this->backtrace = debug_backtrace();
		}

		public function getHiddenInfo() {
			return $this->hidden_info;
		}

		public function getBacktrace() {
			return $this->backtrace;
		}

	}

//--------------------------------------------------
// Error emails

	function send_error_emails() {

		$error_email = config::get('debug.error_send', true); // Could be set to false (to disable), or a different email address.
		if ($error_email === true) {
			$error_email = config::get('email.error');
		}

		$errors = config::get('debug.errors');

		if ($error_email && is_array($errors) && count($errors) > 0) {

			$email_values = config::get('debug.report_values', []);

			$email = new email();
			$email->default_style_set(NULL);
			$email->subject_set('System PHP Errors: ' . config::get('output.domain'));
			$email->request_table_add($email_values, 'Y-m-d H:i:s - D jS M');
			$email->body_add("\n" . implode("\n", $errors));

			$email->send($error_email);

		}

		config::set('debug.errors', []);

	}

//--------------------------------------------------
// Error handler

	function error_handler($err_no, $err_str, $err_file, $err_line, $err_context = NULL) {

		//--------------------------------------------------
		// If disabled

			if (error_reporting() == 0) { // (as much granularity as I want to check for)
				return;
			}

		//--------------------------------------------------
		// Using the html() function with multibyte issue

			foreach (debug_backtrace() as $called_from) {
				if (isset($called_from['file']) && !str_starts_with($called_from['file'], FRAMEWORK_ROOT)) {

					if (isset($called_from['function']) && $called_from['function'] == 'html') {

						// Show value for multibyte error in the html() function.
						//   ini_set('display_errors', false); - see http://insomanic.me.uk/post/191397106
						//   html('Testing: ' . chr(254));

						$err_line = $called_from['line'];
						$err_file = $called_from['file'];

						$err_str .= ' (' . (isset($called_from['args'][0]) ? $called_from['args'][0] : 'NULL') . ')';

					}

					break;

				}
			}

		//--------------------------------------------------
		// Error type

			switch ($err_no) { // From "Johan 'Josso' Jensen" on https://php.net/set_error_handler
				case E_NOTICE:
				case E_USER_NOTICE:
					$error_type = 'Notice';
				break;
				case E_WARNING:
				case E_USER_WARNING:
					$error_type = 'Warning';
				break;
				case E_ERROR:
				case E_USER_ERROR:
					$error_type = 'Fatal Error';
				break;
				default:
					$error_type = 'Unknown';
				break;
			}

		//--------------------------------------------------
		// Output

			if (ini_get('display_errors')) {
				echo "\n<br />\n<b>" . html($error_type) . '</b>: ' . html($err_str) . ' in <b>' . html($err_file) . '</b> on line <b>' . html($err_line) . '</b>';
				echo "<br /><br />\n";
			}

		//--------------------------------------------------
		// Log

			if (ini_get('log_errors')) {

				$error_message = sprintf('PHP %s: %s in %s on line %d', $error_type, $err_str, $err_file, $err_line);

				error_log($error_message);

				config::array_push('debug.errors', $error_message);

				if (config::get('debug.error_shutdown_registered') !== true) {
					config::set('debug.error_shutdown_registered', true);
					register_shutdown_function('send_error_emails');
				}

			}

		//--------------------------------------------------
		// Handled

			return true;

	}

	set_error_handler('error_handler');

//--------------------------------------------------
// Quick debug print of a variable

	function debug($variable = NULL) {

		if (!headers_sent() && config::get('output.mime') == 'application/xhtml+xml') {
			mime_set('text/html');
		}

		$called_from = debug_backtrace();
		$called_from_file = substr(str_replace(ROOT, '', $called_from[0]['file']), 1);
		$called_from_line = $called_from[0]['line'];

		//$output = print_r($variable, true);
		$output = debug_dump($variable); // Shows false (not an empty string), quotes strings (var_export does - but has problems with recursion), and shows a simple string representation for the url object.

		if (php_sapi_name() == 'cli' || config::get('output.mime') == 'text/plain') {
			echo "\n" . $called_from_file . ' (line ' . $called_from_line . ')' . "\n";
			echo $output . "\n";
		} else {
			echo "\n" . '<strong>' . html($called_from_file) . '</strong> (line <strong>' . html($called_from_line) . '</strong>)' . "\n";
			echo '<pre>' . html($output) . '</pre>';
		}

	}

//--------------------------------------------------
// Dump a variable

	function debug_dump($variable, $level = 0) {
		switch ($type = gettype($variable)) {
			case 'NULL':
				return 'NULL';
			case 'boolean':
				return ($variable ? 'true' : 'false');
			case 'integer':
			case 'double':
			case 'float':
				return $variable;
			case 'string':
				return '"' . $variable . '"';
			case 'array':
				$indent = str_repeat('        ', $level);
				$output = '(' . "\n";
				foreach ($variable as $key => $value) {
					$output .= $indent . '    [' . debug_dump($key) . '] => ' . debug_dump($value, ($level + 1)) . "\n";
				}
				return $output . $indent . ')';
			case 'object':
				if (method_exists($variable, '_debug_dump')) {
					return $variable->_debug_dump();
				}
			default:
				return trim(preg_replace('/^/m', str_repeat('        ', $level), print_r($variable, true)));
		}
	}

//--------------------------------------------------
// Debug exit

	function debug_exit($output = '') {

		while (ob_get_level() > 0) {
			$output = ob_get_clean() . $output;
		}

		if (function_exists('response_get')) {
			$response = response_get('html');
			$response->template_path_set(FRAMEWORK_ROOT . '/library/template/blank.ctp');
			$response->view_set_html($output);
			$response->send();
		} else {
			echo $output;
		}

		exit();

	}

//--------------------------------------------------
// Debug run time

	function debug_time_elapsed() {
		return round((microtime(true) - FRAMEWORK_START), 3);
	}

	function debug_time_format($time) {
		return ($time == 0 ? '0.000' : str_pad($time, 5, '0'));
	}

//--------------------------------------------------
// Config

	function debug_config_log($prefix = '') { // Used in CLI, so don't check on debug.level

		$encrypted_mask = '[Encrypted]';

		$config = config::get_all($prefix, $encrypted_mask);

		ksort($config);

		$log = [];

		foreach ($config as $key => $value) {
			if (!in_array($key, array('db.link', 'output.response', 'debug.time_init', 'debug.time_check', 'debug.time_query', 'debug.units'))) {
				if ($key == 'debug.notes' || str_ends_with($key, '.pass') || str_ends_with($key, '.password') || str_ends_with($key, '.key')) { // e.g. 'db.pass', although these should use the 'secrets' helper.
					if ($value != $encrypted_mask) {
						$value = '???';
					}
				} else if (is_object($value)) {
					$value = get_class($value) . '()';
				} else {
					$value = preg_replace('/\s+/', ' ', debug_dump($value, 1));
				}
				$log[] = [['strong', (($prefix == '' ? '' : $prefix . '.') . $key)], ['span', ': ' . $value]];
			}
		}

		return $log;

	}

	function debug_constants_log() {

		$constants = get_defined_constants(true);

		if (!isset($constants['user'])) {
			return;
		}

		ksort($constants['user']);

		$log = [];

		foreach ($constants['user'] as $key => $value) {
			$value = preg_replace('/\s+/', ' ', debug_dump($value, 1));
			$log[] = [['strong', $key], ['span', ': ' . $value]];
		}

		return $log;

	}

//--------------------------------------------------
// Debug mode

	if (config::get('debug.level') > 0) {

		//--------------------------------------------------
		// Debug progress

			function debug_progress($label) {

				$time = debug_time_format(debug_time_elapsed() - config::get('debug.time_check'));

				debug_note([
						'type' => 'L',
						'colour' => '#CCC',
						'file' => NULL,
						'time' => NULL,
						'text' => ($time . ' - ' . $label),
					]);

			}

		//--------------------------------------------------
		// Debug notes

			function debug_note($note) {

				//--------------------------------------------------
				// Array

					if (!is_array($note)) {
						$note = [
								'text' => (is_string($note) ? $note : debug_dump($note)),
							];
					}

				//--------------------------------------------------
				// Call data

					foreach (debug_backtrace() as $called_from) {
						if (isset($called_from['file']) && $called_from['file'] != __FILE__) {
							break;
						}
					}

					if (isset($called_from['file'])) {
						$system_call = str_starts_with($called_from['file'], FRAMEWORK_ROOT);
					} else {
						$system_call = true; // e.g. shutdown function
					}

					$default_file = NULL;
					$default_time = NULL;

					if (!$system_call) {

						$default_file = [
								'path' => str_replace(ROOT, '', $called_from['file']),
								'line' => $called_from['line'],
							];

						$default_time = debug_time_format(debug_time_elapsed() - config::get('debug.time_check'));

					}

				//--------------------------------------------------
				// Note

					$note = array_merge([
							'type'   => 'L',
							'colour' => ($system_call ? '#CCC' : '#FFC'),
							'file'   => $default_file,
							'time'   => $default_time,
						], $note);

					config::array_push('debug.notes', $note);

			}

		//--------------------------------------------------
		// Database debug

			function debug_require_db_table($table, $sql) {

				if ($table == '') {
					exit('Missing table name when using debug_require_db_table()');
				}

				$db = db_get();

				$db->query('SHOW TABLES LIKE "' . $db->escape($table) . '"', NULL, (db::SKIP_DEBUG | db::SKIP_LITERAL_CHECK)); // Parameters are not supported in "SHOW TABLES" (MySQL 5.7)
				if ($db->num_rows() == 0) {
					if (php_sapi_name() == 'cli' || config::get('output.mime') == 'text/plain') {
						exit('Missing table "' . $table . '":' . "\n\n" . str_replace('[TABLE]', $table, $sql) . "\n\n");
					} else {
						http_response_code(500);
						mime_set('text/html');
						exit('Missing table <strong>' . html($table) . '</strong>:<br /><br />' . nl2br(html(trim(str_replace('[TABLE]', $table, $sql)))));
					}
				}

			}

			function debug_database($db, $sql, $parameters, $skip_flags) {

				//--------------------------------------------------
				// Skip if disabled debugging

					if (config::get('debug.db') !== true) {
						return $db->query($sql, $parameters, (db::SKIP_DEBUG | $skip_flags));
					}

				//--------------------------------------------------
				// Full time

					$time_init = microtime(true);

				//--------------------------------------------------
				// Query tainted

					$query_tainted = (extension_loaded('taint') && is_tainted($sql)); // Before preg_match, due to https://bugs.php.net/bug.php?id=74066

				//--------------------------------------------------
				// Query type

					$select_query = preg_match('/^\W*SELECT.*FROM/is', $sql); // Check for "non-word" characters, as it may contain brackets, e.g. a UNION... And don't debug queries without a table, e.g. SELECT FOUND_ROWS();

					if ($select_query && strpos($sql, 'SQL_NO_CACHE') === false) {
						$sql = preg_replace('/^\W*SELECT/', '$0 SQL_NO_CACHE', $sql);
					}

				//--------------------------------------------------
				// Formatted query

					$indent = 0;
					$query_lines = [];
					$query_text = preg_replace('/\) (AND|OR) \(/', "\n$0\n", $sql); // Could be better, just breaking up the keyword searching sections.

					foreach (explode("\n", $query_text) as $line_text) {

						$line_text = trim($line_text);
						$line_indent = $indent;

						if ($line_text == '') {
							continue;
						}

						$open = strrpos($line_text, '('); // The LAST bracket is an OPEN bracket.
						$close = strrpos($line_text, ')');
						if ($open !== false && ($close === false || $open > $close)) {
							$indent += 2;
						}

						$open = strpos($line_text, '('); // The FIRST bracket is a CLOSE bracket.
						$close = strpos($line_text, ')');
						if ($close !== false && ($open === false || $open > $close)) {
							$indent -= 2;
							if ($close == 0) { // Not always an exact match, e.g. ending a subquery with ") AS s"
								$line_indent -= 2;
							}
						}

						if (!preg_match('/^[A-Z]+( |$)/', $line_text)) { // Keywords, such as SELECT/FROM/WHERE/etc (not functions)
							$line_indent += 1;
						}

						if ($line_indent < 0) {
							$line_indent = 0;
						}

						$query_lines[] = str_repeat('    ', $line_indent) . $line_text;

					}

					$query_plain = implode("\n", $query_lines) . ';';

				//--------------------------------------------------
				// Parameters

					if ($parameters) {
						$k = 0;
						$query_formatted = [];
						foreach (explode('?', $query_plain) as $section) {
							$query_formatted[] = ['span', $section];
							$value = ($parameters[$k] ?? NULL);
							if (is_array($value)) {
								list($type, $value) = $value;
							} else {
								$type = (is_int($value) ? 'i' : 's');
							}
							if ($value !== NULL) {
								$query_formatted[] = ['strong', ($type == 's' ? '"' . $value . '"' : $value), 'value'];
							} else {
								$query_formatted[] = ['strong', 'NULL', 'value'];
							}
							$k++;
						}
						array_pop($query_formatted); // Last entry isn't a parameter.
					} else {
						$query_formatted = [['span', $query_plain]];
					}

				//--------------------------------------------------
				// Called from

					foreach (debug_backtrace() as $called_from) {
						if (isset($called_from['file']) && !str_starts_with($called_from['file'], FRAMEWORK_ROOT)) {
							break;
						}
					}

				//--------------------------------------------------
				// Query tainted

					if ($query_tainted) {

						echo "\n";
						echo '<div>' . "\n";
						echo '	<h1>Error</h1>' . "\n";
						echo '	<p><strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')</p>' . "\n";
						echo '	<p>The following SQL has been tainted.</p>' . "\n";
						echo '	<hr />' . "\n";
						echo '	<p><pre>' . "\n\n" . html(implode('', array_column($query_formatted, 1))) . "\n\n" . '</pre></p>' . "\n";
						echo '</div>' . "\n";

						exit();

					}

				//--------------------------------------------------
				// Explain how the query is executed

					$explain = NULL;

					if ($select_query) {

						$result = $db->query('EXPLAIN ' . $sql, $parameters, (db::SKIP_DEBUG | db::SKIP_ERROR_HANDLER));

						if ($result) {
							$explain = $db->fetch_all($result);
						}

					}

				//--------------------------------------------------
				// Get all the table references, and if any of them
				// have a "deleted" column, make sure that it's
				// being used

					$result_list = [];

					if (preg_match('/^\W*(SELECT|UPDATE|DELETE)/i', ltrim($sql))) {

						$tables = [];

						// if (preg_match('/WHERE(.*)/ims', $sql, $matches)) {
						// 	$where_sql = $matches[1];
						// 	$where_sql = preg_replace('/ORDER BY.*/ms', '', $where_sql);
						// 	$where_sql = preg_replace('/LIMIT\W+[0-9].*/ms', '', $where_sql);
						// } else {
						// 	$where_sql = '';
						// }

						$where_sql = '';
						preg_match_all('/WHERE(.*?)(GROUP BY|ORDER BY|LIMIT\W+[0-9]|LEFT JOIN|$)/is', $sql, $matches_sql, PREG_SET_ORDER);
						foreach ($matches_sql as $match_sql) {
							$where_sql .= $match_sql[1];
						}

						if (DB_PREFIX != '') {

							preg_match_all('/\b(' . preg_quote(DB_PREFIX, '/') . '[a-z0-9_]+)`?( AS ([a-z0-9]+))?/', $sql, $matches, PREG_SET_ORDER);

						} else {

							$matches = [];

							preg_match_all('/(UPDATE|FROM)([^\(]*?)(WHERE|GROUP BY|HAVING|ORDER BY|LIMIT|$)/isD', $sql, $from_matches, PREG_SET_ORDER);

							foreach ($from_matches as $match) {
								foreach (preg_split('/(,|(NATURAL\s+)?(LEFT|RIGHT|INNER|CROSS)\s+(OUTER\s+)?JOIN)/', $match[2]) as $table) {

									if (preg_match('/([a-z0-9_]+)( AS ([a-z0-9]+))?/', $table, $ref)) {
										$matches[] = $ref;
									}

								}
							}

						}

						foreach ($matches as $table) {

							$found = [];

							foreach (config::get('debug.db_required_fields') as $required_field) {

								$result = $db->query('SHOW COLUMNS FROM ' . $table[1] . ' LIKE "' . $required_field . '"', NULL, (db::SKIP_DEBUG | db::SKIP_LITERAL_CHECK | db::SKIP_ERROR_HANDLER));

								if ($result && $row = $db->fetch_row($result)) {

									//--------------------------------------------------
									// Found

										$found[] = $required_field;

									//--------------------------------------------------
									// Table name

										$required_clause = (isset($table[3]) ? '`' . $table[3] . '`.' : '') . '`' . $required_field . '`';

									//--------------------------------------------------
									// Test

										$sql_conditions = array($where_sql);

										if (preg_match('/' . preg_quote($table[1], '/') . (isset($table[3]) ? ' +AS +' . preg_quote($table[3], '/') : '') . ' +ON(.*)/ms', $sql, $on_details)) {
											$sql_conditions[] = preg_replace('/(LEFT|RIGHT|INNER|CROSS|WHERE|GROUP BY|HAVING|ORDER BY|LIMIT).*/ms', '', $on_details[1]);
										}

										$valid = false;
										foreach ($sql_conditions as $sql_condition) {
											if (preg_match('/' . str_replace('`', '(`|\b)', preg_quote($required_clause, '/')) . ' +(IS NULL|IS NOT NULL|=|>|>=|<|<=|!=)/', $sql_condition)) {
												$valid = true;
												break;
											}
										}

									//--------------------------------------------------
									// If missing

										if (!$valid) {

											echo "\n";
											echo '<div>' . "\n";
											echo '	<h1>Error</h1>' . "\n";
											echo '	<p><strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')</p>' . "\n";
											echo '	<p>Missing reference to "' . html(str_replace('`', '', $required_clause)) . '" column on the table "' . html($table[1]) . '".</p>' . "\n";
											echo '	<hr />' . "\n";
											echo '	<p><pre>' . "\n\n" . html(implode('', array_column($query_formatted, 1))) . "\n\n" . '</pre></p>' . "\n";
											echo '</div>' . "\n";

											exit();

										}

								}

							}

							$tables[] = $table[1] . ': ' . (count($found) > 0 ? implode(', ', $found) : 'N/A');

						}

						if (count($tables) > 0) {

							foreach ($tables as $table) {
								if (($pos = strrpos($table, ': ')) !== false) {
									$result_list[] = [['span', substr($table, 0, ($pos + 2))], ['strong', substr($table, ($pos + 2))]];
								} else {
									$result_list[] = $table;
								}
							}

						}

					}

				//--------------------------------------------------
				// Run query

					$time_start = microtime(true);

					$result = $db->query($sql, $parameters, (db::SKIP_DEBUG | $skip_flags));

					$time_check = round(($time_start - $time_init), 3);
					$time_query = round((microtime(true) - $time_start), 3);

					if ($select_query && $result) {
						$result_rows = $db->num_rows($result);
					} else {
						$result_rows = NULL;
					}

					config::set('debug.time_query', (config::get('debug.time_query') + $time_query));
					config::set('debug.time_check', (config::get('debug.time_check') + $time_check));

				//--------------------------------------------------
				// Create debug output

					config::array_push('debug.notes', array(
							'type'   => 'L',
							'colour' => '#CCF',
							'class'  => 'debug_sql',
							'file'   => ['path' => str_replace(ROOT, '', $called_from['file']), 'line' => $called_from['line']],
							'text'   => $query_formatted,
							'time'   => debug_time_format($time_query),
							'rows'   => $result_rows,
							'table'  => $explain,
							'list'   => $result_list,
						));

				//--------------------------------------------------
				// Return

					return $result;

			}

		//--------------------------------------------------
		// End debug

			function debug_shutdown() {

				//--------------------------------------------------
				// Ignore

					if (!config::get('debug.show')) {
						return false;
					}

				//--------------------------------------------------
				// Time text

					$time_check = config::get('debug.time_check');
					$time_total = debug_time_elapsed();

					$time_text  = 'Setup time: ' . debug_time_format(config::get('debug.time_init')) . "\n";
					$time_text .= 'Query time: ' . debug_time_format(config::get('debug.time_query')) . "\n";
					$time_text .= 'Total time: ' . debug_time_format($time_total - $time_check) . ' (with checks ' . debug_time_format($time_total) . ')';

				//--------------------------------------------------
				// Send

					$output_ref = config::get('debug.output_ref');

					if ($output_ref) {

						//--------------------------------------------------
						// End time

							debug_note([
									'type' => 'L',
									'colour' => '#FFF',
									'file' => NULL,
									'time' => NULL,
									'text' => $time_text,
								]);

						//--------------------------------------------------
						// Store

							$debug_data = session::get('debug.output_data');

							$debug_data[$output_ref] = json_encode(['time' => debug_time_format($time_total - $time_check), 'notes' => config::get('debug.notes')]);

							session::set('debug.output_data', $debug_data);

					} else if (config::get('output.mime') == 'text/plain') {

						//--------------------------------------------------
						// Base notes

							$output_text = "\n\n\n\n\n\n\n\n\n\n";

							foreach (config::get('debug.notes') as $note) {

								$output_text .= '--------------------------------------------------' . "\n\n";

								if (isset($note['heading'])) {
									$output_text .= $note['heading'] . (isset($note['heading_extra']) ? ': ' . $note['heading_extra'] : '') . "\n\n";
								}

								if (isset($note['file'])) {
									$output_text .= $note['file']['path'] . ' (line ' . $note['file']['line'] . ')' . "\n\n";
								}

								if (isset($note['text'])) {
									if (is_array($note['text'])) {
										$output_text .= implode('', array_column($note['text'], 1)) . "\n\n";
									} else {
										$output_text .= $note['text'] . "\n\n";
									}
								}

								if (isset($note['lines'])) {
									foreach ($note['lines'] as $line) {
										if (is_array($line)) {
											$output_text .= implode('', array_column($line, 1)) . "\n";
										} else {
											$output_text .= $line . "\n";
										}
									}
									if (count($note['lines']) == 0) {
										$output_text .= (isset($note['lines_empty']) ? $note['lines_empty'] : 'none') . "\n";
									}
									$output_text .= "\n";
								}

								if (isset($note['time'])) {
									$output_text .= 'Time:  ' . $note['time'] . (isset($note['rows']) ? "\n" : "\n\n");
								}

								if (isset($note['rows'])) {
									$output_text .= 'Rows:  ' . $note['rows'] . "\n\n";
								}

								if (isset($note['table'])) {
									foreach ($note['table'] as $row) {
										$length = (max(array_map('strlen', array_keys($row))) + 1);
										foreach ($row as $field => $value) {
											$output_text .= str_pad($field, $length, ' ', STR_PAD_LEFT) . ': ' . $value . "\n";
										}
										$output_text .= "\n";
									}
								}

								if (isset($note['list']) && count($note['list']) > 0) {
									foreach ($note['list'] as $line) {
										if (is_array($line)) {
											$output_text .= '# ' .  implode('', array_column($line, 1)) . "\n";
										} else {
											$output_text .= '# ' .  $line . "\n";
										}
									}
									$output_text .= "\n";
								}

							}

						//--------------------------------------------------
						// End time

							$output_text .= '--------------------------------------------------' . "\n\n";
							$output_text .= $time_text . "\n\n";
							$output_text .= '--------------------------------------------------' . "\n\n";

						//--------------------------------------------------
						// Send

							echo $output_text;

					}

			}

			register_shutdown_function('debug_shutdown');

	}

?>