<?php

//--------------------------------------------------
// Setup

	//--------------------------------------------------
	// Notes

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

				$email = new email();
				$email->subject_set('System ' . ucfirst($type) . ': ' . config::get('request.domain'));
				$email->request_table_add(array(
						'Message' => $message,
					));

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

			$called_from_stack = debug_backtrace();

			foreach ($called_from_stack as $called_from) {
				if (substr($called_from['file'],0, strlen(FRAMEWORK_ROOT)) != FRAMEWORK_ROOT) {

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

			if (php_sapi_name() == 'cli') {

				echo "\n" . '--------------------------------------------------' . "\n\n";
				echo 'Error:' . "\n\n";
				echo $message . "\n\n";

				if ($hidden_info !== NULL && $contact_email == '') {
					echo $hidden_info . "\n\n";
				}

				echo '--------------------------------------------------' . "\n\n";

			} else {

				if (!headers_sent()) {
					header('HTTP/1.0 500 Internal Server Error');
					mime_set('text/html');
				}

				if (class_exists('view') && class_exists('layout')) {

					config::array_set('view.variables', 'message', $message);
					config::array_set('view.variables', 'hidden_info', $hidden_info);
					config::array_set('view.variables', 'contact_email', $contact_email);

					config::set('output.error', true);

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
							<p>' . html($message) . '</p>
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

	function debug($variable) {

		$called_from = debug_backtrace();
		echo '<strong>' . substr(str_replace(ROOT, '', $called_from[0]['file']), 1) . '</strong>';
		echo ' (line <strong>' . $called_from[0]['line'] . '</strong>)';

		echo '<pre>';
		echo html(print_r($variable, true));
		echo '</pre>' . "\n";

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

	function debug_note($note) {
		debug_note_html(nl2br(str_replace(' ', '&#xA0;', html(trim(print_r($note, true))))));
	}

	function debug_note_html($note_html) {

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
				$note_html = str_replace(ROOT, '/', $note_html);

				$time = NULL;

			}

		//--------------------------------------------------
		// Note

			config::array_push('debug.notes', array(
					'colour' => ($system_call ? '#CCC' : '#CFC'),
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
					if (in_array($key, array('db.pass', 'debug.notes', 'view.variables', 'output.html', 'output.css_types', 'output.head_html'))) {
						$value_html = '???';
					} else {
						$value_html = html(print_r($value, true));
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
					$variables_html[] = '&#xA0; <strong>' . html($key) . '</strong>: ' . html(print_r($value, true));
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

			$query_html = nl2br(trim(preg_replace('/^[ \t]*(?! |\t|SELECT|UPDATE|DELETE|INSERT|SHOW|FROM|LEFT|SET|WHERE|GROUP|ORDER|LIMIT)/m', '&#xA0; &#xA0; \0', html($query))));
			if (strpos($query_html, "\n") !== false) {
				$query_html .= '<br /><br />';
			}

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
								<td style="border-width: 1px 0 0 1px; border-style: solid; border-color: #000; padding: 0.2em;">' . ($key == 'type' ? '<a href="http://dev.mysql.com/doc/refman/5.0/en/explain.html#id2772158" style="color: #000; background: #CCF; text-decoration: none;">' : '') . ($value == '' ? '&#xA0;' : html($value)) . ($key == 'type' ? '</a>' : '') . '</td>';
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
									if (preg_match('/' . str_replace('`', '`?', preg_quote($required_clause, '/')) . ' (IS NULL|=|>|>=|<|<=|!=)/', $sql_condition)) {
										$valid = true;
										break;
									}
								}

							//--------------------------------------------------
							// If missing

								if (!$valid) {

									config::set('debug.show', false);

									echo '
										<div>
											<h1>Error</h1>
											<p><strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')</p>
											<p>Missing reference to "' . html(str_replace('`', '', $required_clause)) . '" column on the table "' . html($table[1]) . '".</p>
											<p style="padding: 0 0 0 1em;">' . $query_html . '</p>
										</div>';

									exit();

								}

						}

					}

					$tables[] = $table[1] . ': ' . (count($found) > 0 ? implode(', ', $found) : 'N/A');

				}

				if (count($tables) > 0) {

					$text_html .= '
						<ul style="margin: 0; padding: 0; margin: 1em 0 1em 2em; background: #CCF; color: #000;">';

					foreach ($tables as $table) {
						$text_html .= '
							<li style="padding: 0; margin: 0; background: #CCF; color: #000; text-align: left;">' . preg_replace('/: (.*)/', ': <strong>$1</strong>', html($table)) . '</li>';
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

			$html = '<strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')<br />' . (strpos($query_html, "\n") === false ? '' : '<br />') . $query_html;

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
			$css_block = 'margin: 5px 0; padding: 5px; color: #000; border: 1px solid #000; clear: both;';
			$css_para = 'text-align: left; padding: 0; margin: 0; ' . $css_text;

		//--------------------------------------------------
		// Time taken

			$time = debug_run_time();

			$output_html = '
				<div style="' . html($css_block) . ' background: #FFF;">
					<p style="' . html($css_para) . '">Elapsed time: ' . html($time) . '</p>
					<p style="' . html($css_para) . '">Query time: ' . html(config::get('debug.db_time')) . '</p>
				</div>';

		//--------------------------------------------------
		// Notes

			$notes = config::get('debug.notes');

			foreach ($notes as $note) {
				$output_html .= '
					<div style="' . html($css_block) . ' background: ' . html($note['colour']) . '">
						<p style="' . html($css_para) . '">' . $note['html'] . '</p>';
				if ($note['time'] !== NULL) {
					$output_html .= '
						<p style="' . html($css_para) . '">Time Elapsed: ' . html($note['time']) . '</p>';
				}
				if (isset($note['extra_html'])) {
					$output_html .= $note['extra_html'];
				}
				$output_html .= '
					</div>';
			}

		//--------------------------------------------------
		// Wrapper

			$output_html = "\n\n<!-- START OF DEBUG -->\n\n" . '
				<div style="margin: 10px 5px 0 5px; padding: 0; clear: both;">
					<p style="' . html($css_para) . '"><a href="#" style="color: #AAA; ' . html($css_text) . '" onclick="document.getElementById(\'debug_output\').style.display = (document.getElementById(\'debug_output\').style.display == \'block\' ? \'none\' : \'block\'); return false;">+</a></p>
					<div style="display: block;" id="debug_output">
						' . $output_html . '
					</div>
				</div>' . "\n\n<!-- END OF DEBUG -->\n\n";

		//--------------------------------------------------
		// Add

			$pos = strpos(strtolower($buffer), '</body>');
			if ($pos !== false && config::get('output.mime') != 'text/plain') {

		 		return substr($buffer, 0, $pos) . $output_html . substr($buffer, $pos);

			} else {

				if (config::get('output.mime') == 'application/xhtml+xml') {
					mime_set('text/html');
				}

		 		return $buffer . $output_html;

			}

	}

	if (config::get('debug.level') > 0) {
		ob_start('debug_shutdown');
	}

?>