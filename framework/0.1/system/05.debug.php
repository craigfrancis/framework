<?php

//--------------------------------------------------
// Setup

	//--------------------------------------------------
	// Setup

		config::set_default('debug.values', array());
		config::set('debug.notes', array());

	//--------------------------------------------------
	// Start time

		function debug_start_time_set($start_time = NULL) {

			if ($start_time === NULL) {
				$start_time = explode(' ', microtime());
				$start_time = ((float)$start_time[0] + (float)$start_time[1]);
			}

			config::set('debug.start_time', $start_time);

		}

		debug_start_time_set();

	//--------------------------------------------------
	// DB variables

		config::set_default('debug.db', true);
		config::set_default('debug.db_required_fields', array('deleted'));

		config::set('debug.db_time', 0);

//--------------------------------------------------
// Error reporting

	if (SERVER == 'stage') {

		debug_require_db_table('report', '
				CREATE TABLE [TABLE] (
					id int(11) NOT NULL auto_increment,
					type tinytext NOT NULL,
					created datetime NOT NULL,
					message text NOT NULL,
					request tinytext NOT NULL,
					referrer tinytext NOT NULL,
					ip tinytext NOT NULL,
					PRIMARY KEY  (id)
				);');

	}

	function report_add($message, $type = 'notice') {

		//--------------------------------------------------
		// Send an email to the admin, if necessary

			$error_email = config::get('email.error');

			if (($type == 'error' || $type == 'notice') && $error_email !== NULL) {

				$email_values = config::get('debug.values', array());
				$email_values = array_merge($email_values, array('Message' => $message));

				$email = new email();
				$email->subject_set('System ' . ucfirst($type) . ': ' . config::get('request.domain'));
				$email->request_table_add($email_values);

				$email->send($error_email);

			}

		//--------------------------------------------------
		// Add report to the database

			$db = new db();

			$db->query('INSERT INTO ' . DB_PREFIX . 'report (
							id,
							type,
							created,
							message,
							request,
							referrer,
							ip
						) VALUES (
							"",
							"' . $db->escape($type) . '",
							"' . $db->escape(date('Y-m-d H:i:s')) . '",
							"' . $db->escape($message) . '",
							"' . $db->escape(config::get('request.url')) . '",
							"' . $db->escape(config::get('request.referrer')) . '",
							"' . $db->escape(config::get('request.ip')) . '"
						)');

	}

	function exit_with_error($message, $hidden_info = NULL) {

		//--------------------------------------------------
		// Called from

			foreach (debug_backtrace() as $called_from) {
				if (isset($called_from['file']) && substr($called_from['file'], 0, strlen(FRAMEWORK_ROOT)) != FRAMEWORK_ROOT) {

					if ($hidden_info === NULL) {
						$hidden_info = '';
					} else {
						$hidden_info .= "\n\n";
					}

					$hidden_info .= $called_from['file'] . ' (line ' . $called_from['line'] . ')';

					break;

				}
			}

		//--------------------------------------------------
		// Report the error

			$error_report = $message;

			if ($hidden_info !== NULL) {
				$error_report .= "\n\n--------------------------------------------------\n\n";
				$error_report .= $hidden_info;
			}

			report_add($error_report, 'error');

		//--------------------------------------------------
		// Return the primary contacts email address.

			$contact_email = config::get('email.error');

			if (is_array($contact_email)) {
				$contact_email = reset($contact_email);
			}

		//--------------------------------------------------
		// Tell the user

			if ($contact_email != '') {
				$hidden_info = NULL; // If there is an email address, don't show the hidden info (e.g. on live).
			}

			if (php_sapi_name() == 'cli' || config::get('output.mime') == 'text/plain') {

				echo "\n" . '--------------------------------------------------' . "\n\n";
				echo 'System Error:' . "\n\n";
				echo $message . "\n\n";

				if ($hidden_info !== NULL) {
					echo $hidden_info . "\n\n";
				}

				echo '--------------------------------------------------' . "\n\n";

			} else {

				if (!headers_sent()) {
					http_response_code(500);
					mime_set('text/html');
				}

				if (class_exists('view') && class_exists('layout')) {

					config::array_set('view.variables', 'message', $message);
					config::array_set('view.variables', 'hidden_info', $hidden_info);
					config::array_set('view.variables', 'contact_email', $contact_email);

					$view = new view();
					$view->render_error('system');

					$layout = new layout();
					$layout->render();

				} else {

					echo '<!DOCTYPE html>
						<html lang="' . html(config::get('output.lang')) . '" xml:lang="' . html(config::get('output.lang')) . '" xmlns="http://www.w3.org/1999/xhtml">
						<head>
							<meta charset="' . html(config::get('output.charset')) . '" />
							<title>' . html(config::get('output.title')) . '</title>
						</head>
						<body id="p_error">
							<h1>System Error</h1>
							<p>' . html($message) . '</p>';

					if ($hidden_info !== NULL) {
						echo '
							<p style="border: 1px solid #000; padding: 1em; margin: 0 0 1em 0;">' . nl2br(html($hidden_info)) . '</p>';
					}

					echo '
						</body>
						</html>';

				}

			}

		//--------------------------------------------------
		// Exit script execution

			exit();

	}

//--------------------------------------------------
// Quick debug print of a variable

	function debug($variable = NULL) {

		$called_from = debug_backtrace();
		$called_from_path = substr(str_replace(ROOT, '', $called_from[0]['file']), 1);
		$called_from_line = $called_from[0]['line'];

		//$output = print_r($variable, true);
		$output = debug_dump($variable); // Shows false (not an empty string), quotes strings (var_export does - but has problems with recursion), and shows a simple string representation for the url object.

		if (php_sapi_name() == 'cli' || config::get('output.mime') == 'text/plain') {
			echo "\n" . $called_from_path . ' (line ' . html($called_from_line) . ')' . "\n";
			echo $output . "\n";
		} else {
			echo "\n" . '<strong>' . html($called_from_path) . '</strong> (line <strong>' . html($called_from_line) . '</strong>)' . "\n";
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
				return print_r($variable, true);
		}
	}

//--------------------------------------------------
// Debug run time

	function debug_run_time() {

		$time_end = explode(' ', microtime());
		$time_end = ((float)$time_end[0] + (float)$time_end[1]);

		$time = round(($time_end - config::get('debug.start_time')), 4);
		$time = str_pad($time, 6, '0');

		return $time;

	}

//--------------------------------------------------
// Debug progress

	function debug_progress($label, $indent = 0) {
		$debug_run_time = debug_run_time();
		$debug_diff = round(($debug_run_time - config::get('debug.progress')), 4);
		debug_note_html(str_repeat('&#xA0; &#xA0; ', $indent) . $debug_run_time . ' / ' . str_pad($debug_diff, 6, '0') . ' - ' . ($debug_diff > 0.0005 ? '<strong style="color: #F00;">' : '') . $label . ($debug_diff > 0.0005 ? '</strong>' : ''));
		config::set('debug.progress', $debug_run_time);
	}

//--------------------------------------------------
// Debug notes

	function debug_note($note, $colour = NULL) {
		debug_note_html('<pre>' . html(is_string($note) ? $note : debug_dump($note)) . '</pre>', $colour);
	}

	function debug_note_html($note_html, $colour = NULL) {

		//--------------------------------------------------
		// Suppression

			if (config::get('debug.level') == 0) {
				return;
			}

		//--------------------------------------------------
		// Called from

			$called_from = debug_backtrace();

			if ($called_from[0]['file'] == __FILE__) {
				$called_from_id = 1;
			} else {
				$called_from_id = 0;
			}

			$call_from_file = $called_from[$called_from_id]['file'];
			$call_from_line = $called_from[$called_from_id]['line'];

			$system_call = (substr($call_from_file, 0, strlen(FRAMEWORK_ROOT)) == FRAMEWORK_ROOT);

		//--------------------------------------------------
		// Time position

			if (!$system_call) {

				$note_html = '&#xA0; ' . str_replace("\n", "\n&#xA0; ", $note_html);
				$note_html = '<strong>' . str_replace(ROOT, '', $call_from_file) . '</strong> (line ' . $call_from_line . '):<br />' . $note_html;

				$time = debug_run_time();

			} else {

				$note_html = str_replace(APP_ROOT, '/app', $note_html);
				$note_html = str_replace(FRAMEWORK_ROOT, '/framework', $note_html);
				$note_html = str_replace(ROOT, '', $note_html);

				$time = NULL;

			}

		//--------------------------------------------------
		// Note

			if ($colour !== NULL) {
				if (substr($colour, 0, 1) != '#') {
					$colour = '#' . $colour;
				}
			} else {
				$colour = ($system_call ? '#CCC' : '#CFC');
			}

			config::array_push('debug.notes', array(
					'colour' => $colour,
					'html' => $note_html,
					'time' => $time,
				));

	}

//--------------------------------------------------
// Show configuration

	function debug_show_config($prefix = '') {

		//--------------------------------------------------
		// Suppression

			if (config::get('debug.level') == 0) {
				return;
			}

		//--------------------------------------------------
		// Config

			$config = config::get_all($prefix);

			ksort($config);

			$config_html = array($prefix == '' ? 'Configuration:' : ucfirst($prefix) . ' configuration:');
			foreach ($config as $key => $value) {
				if (!in_array($key, array('db.link'))) {
					if (in_array($key, array('db.pass', 'debug.notes', 'view.variables', 'output.html'))) {
						$value_html = '???';
					} else {
						$value_html = html(debug_dump($value, 1));
					}
					$config_html[] = '&#xA0; <strong>' . html(($prefix == '' ? '' : $prefix . '.') . $key) . '</strong>: ' . $value_html;
				}
			}

		//--------------------------------------------------
		// Add note

			debug_note_html(implode($config_html, '<br />' . "\n"));

	}

//--------------------------------------------------
// Print variables

	function debug_show_array($array, $label = 'Array') {

		//--------------------------------------------------
		// Suppression

			if (config::get('debug.level') == 0) {
				return;
			}

		//--------------------------------------------------
		// HTML

			$variables_html = array(html($label . ':'));
			foreach ($array as $key => $value) {
				if (substr($key, 0, 1) != '_' && substr($key, 0, 5) != 'HTTP_' && !in_array($key, array('GLOBALS'))) {
					$variables_html[] = '&#xA0; <strong>' . html($key) . '</strong>: ' . html(debug_dump($value));
				}
			}

		//--------------------------------------------------
		// Add note

			debug_note_html(implode($variables_html, '<br />' . "\n"));

	}

//--------------------------------------------------
// Database debug

	function debug_require_db_table($table, $sql) {

		$db = new db();

		$db->query('SHOW TABLES LIKE "' . $db->escape(DB_PREFIX . $table) . '"');
		if ($db->num_rows() == 0) {
			exit('Missing table <strong>' . html(DB_PREFIX . $table) . '</strong>:<br /><br />' . nl2br(html(trim(str_replace('[TABLE]', DB_PREFIX . $table, $sql)))));
		}

	}

	function debug_database($db, $query) {

		//--------------------------------------------------
		// Skip if disabled debugging

			if (config::get('debug.db') !== true) {
				return mysql_query($query, $db->link_get()) or $db->error($query);
			}

		//--------------------------------------------------
		// HTML Format for the query

			$query_html = html($query);
			if (strpos($query_html, "\n") !== false) {

				$query_prefix_string = '';
				$query_prefix_length = 0;
				foreach (explode("\n", $query_html) as $line) {
					if (preg_match('/^\t+/', $line, $matches)) {
						$prefix_length = strlen($matches[0]);
						if ($query_prefix_length == 0 || $prefix_length < $query_prefix_length) {
							$query_prefix_length = $prefix_length;
							$query_prefix_string = $matches[0];
						}
					}
				}

				if ($query_prefix_length > 0) {
					$query_html = preg_replace('/^'. preg_quote($query_prefix_string, '/') . '/m', '', $query_html);
				}

				$query_html .= '<br /><br />';

			}

			$query_html = nl2br(trim(preg_replace('/^[ \t]*(?! |\t|SELECT|UPDATE|DELETE|INSERT|SHOW|FROM|LEFT|SET|WHERE|GROUP|ORDER|LIMIT)/m', '&#xA0; &#xA0; \0', $query_html)));

		//--------------------------------------------------
		// Called from

			$called_from = debug_backtrace();

			if (substr($called_from[1]['file'], -23) == '/system/04.database.php') {
				$called_from = $called_from[2];
			} else {
				$called_from = $called_from[1];
			}

		//--------------------------------------------------
		// Explain how the query is executed

			$explain_html = '';

			if (preg_match('/^\W*\(?\W*SELECT/i', $query)) {

				$explain_html .= '
					<table style="border-spacing: 0; border-width: 0 1px 1px 0; border-style: solid; border-color: #000; margin: 1em 0 0 0;">';

				$headers_printed = false;

				$rst = @mysql_query('EXPLAIN ' . $query, $db->link_get());
				if ($rst) {
					while ($row = mysql_fetch_assoc($rst)) {

						if ($headers_printed == false) {
							$headers_printed = true;
							$explain_html .= '
								<tr>';
							foreach ($row as $key => $value) {
								$explain_html .= '
									<th style="border-width: 1px 0 0 1px; border-style: solid; border-color: #000; padding: 0.2em;">' . html($key) . '</th>';
							}
							$explain_html .= '
								</tr>';
						}

						$explain_html .= '
							<tr>';
						foreach ($row as $key => $value) {
							$explain_html .= '
								<td style="border-width: 1px 0 0 1px; border-style: solid; border-color: #000; padding: 0.2em;">' . ($key == 'type' ? '<a href="http://dev.mysql.com/doc/refman/5.0/en/explain.html#id2772158" style="color: #000; font-size: 12px; background: #CCF; text-decoration: none;">' : '') . ($value == '' ? '&#xA0;' : html($value)) . ($key == 'type' ? '</a>' : '') . '</td>';
						}
						$explain_html .= '
							</tr>';

					}
				}

				$explain_html .= '
					</table>';

			}

		//--------------------------------------------------
		// Get all the table references, and if any of them
		// have a "deleted" column, make sure that it's
		// being used

			$text_html = '';

			if (preg_match('/^(SELECT|UPDATE|DELETE)/i', ltrim($query))) {

				$tables = array();

				if (preg_match('/WHERE(.*)/ims', $query, $matches)) {
					$where_sql = $matches[1];
					$where_sql = preg_replace('/ORDER BY.*/ms', '', $where_sql);
					$where_sql = preg_replace('/LIMIT\W+[0-9].*/ms', '', $where_sql);
				} else {
					$where_sql = '';
				}

				if (DB_PREFIX != '') {

					preg_match_all('/\b(' . preg_quote(DB_PREFIX, '/') .'[a-z0-9_]+)( AS ([a-z0-9]+))?/', $query, $matches, PREG_SET_ORDER);

				} else {

					$matches = array();

					preg_match_all('/(UPDATE|FROM)([^\(]*?)(WHERE|GROUP BY|HAVING|ORDER BY|LIMIT|$)/isD', $query, $from_matches, PREG_SET_ORDER);

					foreach ($from_matches as $match) {
						foreach (preg_split('/(,|(NATURAL\s+)?(LEFT|RIGHT|INNER|CROSS)\s+(OUTER\s+)?JOIN)/', $match[2]) as $table) {

							if (preg_match('/([a-z0-9_]+)( AS ([a-z0-9]+))?/', $table, $ref)) {
								$matches[] = $ref;
							}

						}
					}

				}

				foreach ($matches as $table) {

					$found = array();

					foreach (config::get('debug.db_required_fields') as $required_field) {

						$rst = @mysql_query('SHOW COLUMNS FROM ' . $table[1] . ' LIKE "' . $required_field . '"', $db->link_get());
						if ($rst && $row = mysql_fetch_assoc($rst)) {

							//--------------------------------------------------
							// Found

								$found[] = $required_field;

							//--------------------------------------------------
							// Table name

								$required_clause = (isset($table[3]) ? '`' . $table[3] . '`.' : '') . '`' . $required_field . '`';

							//--------------------------------------------------
							// Test

								$sql_conditions = array($where_sql);

								if (preg_match('/' . preg_quote($table[1], '/') . (isset($table[3]) ? ' +AS +' . preg_quote($table[3], '/') . '' : '') . ' +ON +(.*?)(LEFT|RIGHT|INNER|CROSS|WHERE|GROUP BY|HAVING|ORDER BY|LIMIT)/ms', $query, $on_details)) {
									$sql_conditions[] = $on_details[1];
								}

								$valid = false;
								foreach ($sql_conditions as $sql_condition) {
									if (preg_match('/' . str_replace('`', '(`|\b)', preg_quote($required_clause, '/')) . ' (IS NULL|=|>|>=|<|<=|!=)/', $sql_condition)) {
										$valid = true;
										break;
									}
								}

							//--------------------------------------------------
							// If missing

								if (!$valid) {

									config::set('debug.show', false);

									echo "\n";
									echo '<div>' . "\n";
									echo '	<h1>Error</h1>' . "\n";
									echo '	<p><strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')</p>' . "\n";
									echo '	<p>Missing reference to "' . html(str_replace('`', '', $required_clause)) . '" column on the table "' . html($table[1]) . '".</p>' . "\n";
									echo '	<p style="padding: 0 0 0 1em;">' . "\n";
									echo "\n\n";
									echo $query_html . "\n";
									echo "\n\n";
									echo '	</p>' . "\n";
									echo '</div>' . "\n";

									exit();

								}

						}

					}

					$tables[] = $table[1] . ': ' . (count($found) > 0 ? implode(', ', $found) : 'N/A');

				}

				if (count($tables) > 0) {

					$text_html .= '
						<ul style="margin: 0; padding: 0; margin: 1em 0 1em 2em; background: #CCF; color: #000; font-size: 12px;">';

					foreach ($tables as $table) {
						$text_html .= '
							<li style="padding: 0; margin: 0; background: #CCF; color: #000; font-size: 12px; text-align: left;">' . preg_replace('/: (.*)/', ': <strong>$1</strong>', html($table)) . '</li>';
					}

					$text_html .= '
						</ul>';

				}

			}

		//--------------------------------------------------
		// Time start

			$time_start = explode(' ', microtime());
			$time_start = ((float)$time_start[0] + (float)$time_start[1]);

		//--------------------------------------------------
		// Run query

			$result = mysql_query($query, $db->link_get()) or $db->_error($query);

		//--------------------------------------------------
		// Time end

			$time_end = explode(' ', microtime());
			$time_end = ((float)$time_end[0] + (float)$time_end[1]);

			$time_total = round(($time_end - $time_start), 3);

			config::set('debug.db_time', (config::get('debug.db_time') + $time_total));

		//--------------------------------------------------
		// Create debug output

			$html = '<strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')<br />' . "\n" . (strpos($query_html, "\n") === false ? '' : '<br />' . "\n") . $query_html;

			config::array_push('debug.notes', array(
					'colour' => '#CCF',
					'html' => $html,
					'time' => $time_total,
					'extra_html' => $explain_html . $text_html,
				));

		//--------------------------------------------------
		// Return the result

			return $result;

	}

//--------------------------------------------------
// Show config

	if (config::get('debug.level') >= 3) {

		//--------------------------------------------------
		// Config

			debug_show_config();

	}

//--------------------------------------------------
// Error handler

	function error_handler($err_no, $err_str, $err_file, $err_line, $err_context) {

		foreach (debug_backtrace() as $called_from) {
			if (isset($called_from['function']) && $called_from['function'] == 'html') { // Replace line and file if it's the multibyte error in the html() function.
				$err_line = $called_from['line'];
				$err_file = $called_from['file'];
				$err_str .= ' (' . $called_from['args'][0] . ')';
				break;
			}
		}

		switch ($err_no) { // From "Johan 'Josso' Jensen" on http://www.php.net/set_error_handler
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

		if (ini_get('display_errors')) printf("<br />\n<b>%s</b>: %s in <b>%s</b> on line <b>%d</b><br /><br />\n", $error_type, $err_str, $err_file, $err_line);
		if (ini_get('log_errors')) error_log(sprintf('PHP %s:  %s in %s on line %d', $error_type, $err_str, $err_file, $err_line));

		return true;

	}

	set_error_handler('error_handler');

//--------------------------------------------------
// Debug shutdown

	function debug_shutdown($buffer) {

		//--------------------------------------------------
		// Suppression

			if (config::get('debug.level') == 0 || !config::get('debug.show')) {
				return $buffer;
			}

		//--------------------------------------------------
		// Default CSS

			$css_text = 'font-size: 12px; font-family: verdana; font-weight: normal; text-align: left; text-decoration: none;';
			$css_block = 'margin: 5px 0; padding: 5px; color: #000; font-size: 12px; border: 1px solid #000; clear: both;';
			$css_para = 'text-align: left; padding: 0; margin: 0; ' . $css_text;

		//--------------------------------------------------
		// Time taken

			$time = debug_run_time();

			$output_html  = '		<div style="' . html($css_block) . ' background: #FFF;">' . "\n";
			$output_html .= '			<p style="' . html($css_para) . '">Elapsed time: ' . html($time) . '</p>' . "\n";
			$output_html .= '			<p style="' . html($css_para) . '">Query time: ' . html(config::get('debug.db_time')) . '</p>' . "\n";
			$output_html .= '		</div>' . "\n";

			$output_text  = "\n\n\n\n\n\n\n\n\n\n";
			$output_text .= '--------------------------------------------------' . "\n\n";
			$output_text .= 'Elapsed time: ' . html($time) . "\n";
			$output_text .= 'Query time: ' . html(config::get('debug.db_time')) . "\n\n";

		//--------------------------------------------------
		// Notes

			$notes = config::get('debug.notes');

			foreach ($notes as $note) {

				$output_html .= '		<div style="' . html($css_block) . ' background: ' . html($note['colour']) . '">' . "\n";
				$output_html .= '			<p style="' . html($css_para) . '">' . $note['html'] . '</p>' . "\n";

				$output_text .= '--------------------------------------------------' . "\n\n";
				$output_text .= html_decode(strip_tags($note['html'])) . "\n\n";

				if ($note['time'] !== NULL) {
					$output_html .= '			<p style="' . html($css_para) . '">Time Elapsed: ' . html($note['time']) . '</p>' . "\n";
					$output_text .= 'Time Elapsed: ' . $note['time'] . "\n\n";
				}

				if (isset($note['extra_html']) && $note['extra_html'] != '') {
					$output_html .= $note['extra_html'];
					$output_text .= $note['extra_html'] . "\n\n";
				}

				$output_html .= '		</div>' . "\n";

			}

		//--------------------------------------------------
		// Wrapper

			$output_wrapper_html  = "\n\n<!-- START OF DEBUG -->\n\n";
			$output_wrapper_html .= '<div style="margin: 10px 5px 0 5px; padding: 0; clear: both;">' . "\n";
			$output_wrapper_html .= '	<p style="' . html($css_para) . '"><a href="#" style="margin: -10px -10px 0 0; padding: 10px 10px 0 0; color: #AAA; ' . html($css_text) . '" onclick="document.getElementById(\'debug_output\').style.display = (document.getElementById(\'debug_output\').style.display == \'block\' ? \'none\' : \'block\'); return false;">+</a></p>' . "\n";
			$output_wrapper_html .= '	<div style="display: ' . html(config::get('debug.default_show') === true ? 'block' : 'none') . ';" id="debug_output">' . "\n";
			$output_wrapper_html .= $output_html . "\n";
			$output_wrapper_html .= '	</div>' . "\n";
			$output_wrapper_html .= '</div>' . "\n\n<!-- END OF DEBUG -->\n\n";

			$output_html = $output_wrapper_html;

		//--------------------------------------------------
		// Add

			$pos = strpos(strtolower($buffer), '</body>');
			if ($pos !== false && config::get('output.mime') != 'text/plain') {

		 		return substr($buffer, 0, $pos) . $output_html . substr($buffer, $pos);

			} else {

				if (config::get('output.mime') == 'application/xhtml+xml') {
					mime_set('text/html');
				}

				if (config::get('output.mime') == 'text/plain') {
					$output_text = str_replace('&#xA0;', ' ', $output_text);
					$output_text = str_replace('<br />', '', $output_text);
					$output_text = str_replace('<strong>', '', $output_text);
					$output_text = str_replace('</strong>', '', $output_text);
		 			return $buffer . $output_text;
				} else {
		 			return $buffer . $output_html;
				}

			}

	}

	if (config::get('debug.level') > 0) {
		ob_start('debug_shutdown');
	}

?>