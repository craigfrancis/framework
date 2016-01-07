<?php

//--------------------------------------------------
// Config

	if (SERVER == 'stage') {
		$path_config = '/Volumes/WebServer/Projects/craig.framework/framework/0.1/includes/02.config.php';
		$path_source = '/Volumes/WebServer/Projects/craig.framework/framework/0.1/library/class/form';
		$path_output = '/Volumes/WebServer/Projects/cpoets.library/a/php/form2.php';
	} else {
		$path_config = ROOT . '/framework/0.1/includes/02.config.php';
		$path_source = ROOT . '/framework/0.1/library/class/form';
		$path_output = NULL;
	}

//--------------------------------------------------
// Function code

	function function_code_get($functions, $indent = 1) {

		if (!is_array($functions)) {
			$functions = array($functions);
		}

		$output = '';

		$source = file_get_contents(ROOT . '/framework/0.1/includes/01.function.php');

		foreach ($functions as $function) {

			//--------------------------------------------------
			// Starting point

				$start_pos = strpos($source, 'function ' . $function);

				if ($start_pos === false) {
					exit_with_error('Cannot find function "' . $function . '"');
				}

			//--------------------------------------------------
			// End brace

				$k = 0;
				$stack = 0;
				$end_pos = $start_pos;

				do {

					$k++;

					$end_next_open  = strpos($source, '{', $end_pos);
					$end_next_close = strpos($source, '}', $end_pos);

					if ($end_next_close === false) {

						exit_with_error('Cannot find the end to function "' . $function . '"');

					} else if ($end_next_open !== false && $end_next_open < $end_next_close) {

						$stack++;
						$end_pos = ($end_next_open + 1);

					} else {

						$stack--;
						$end_pos = ($end_next_close + 1);

					}

				} while ($stack > 0 && $k < 20);

			//--------------------------------------------------
			// Function code

				$function_code = substr($source, $start_pos, ($end_pos - $start_pos));
				$function_code = explode("\n", $function_code);

			//--------------------------------------------------
			// Remove common prefix (tabs), which has already been
			// removed on the first line.

				$output .= array_shift($function_code) . "\n";

				$length = 0;
				while ($length == 0) {
					$prefix = reset($function_code);
					$length = strlen($prefix);
					if ($length == 0) {
						$output .= "\n";
						array_shift($function_code);
					}
				}

				foreach ($function_code as $line) {
					if ($line != '') {
						while ($length && substr($line, 0, $length) !== $prefix) {
							$length--;
							$prefix = substr($prefix, 0, -1);
						}
						if (!$length) break;
					}
				}

				$prefix_length = strlen($prefix);

				foreach ($function_code as $line) {
					$output .= substr($line, $prefix_length) . "\n";
				}

				$output .= "\n";

		}

		return preg_replace('/^/m', str_repeat("\t", $indent), $output);

	}

//--------------------------------------------------
// Files

	$files_found = array();
	foreach (glob($path_source . '/*') as $file) {
		$name = basename($file, '.php');
		$files_found[$name] = $file;
	}

	$files_ordered = array(
		'form',
		'form-field',
		'form-field-text',
		'form-field-textarea',
		'form-field-url',
		'form-field-email',
		'form-field-number',
		'form-field-password',
		'form-field-postcode',
		'form-field-telephone',
		'form-field-currency',
		'form-field-checkbox',
		'form-field-checkboxes',
		'form-field-radios',
		'form-field-select',
		'form-field-file',
		'form-field-image',
		'form-field-fields',
		'form-field-date',
		'form-field-time',
		'form-field-html',
		'form-field-info',
	);

	$files = array();
	foreach ($files_ordered as $file) {
		if (isset($files_found[$file])) {
			$files[] = $files_found[$file];
			unset($files_found[$file]);
		} else {
			exit_with_error('Cannot find the file "' . $file . '"');
		}
	}

	foreach ($files_found as $file => $path) {
		exit_with_error('Have not used the file "' . $file . '"');
	}

//--------------------------------------------------
// Main script

	//--------------------------------------------------
	// Start

		$output_php = '<?php';

	//--------------------------------------------------
	// Request function

		$output_php .= "\n\n";
		$output_php .= '//--------------------------------------------------' . "\n";
		$output_php .= '// Support functions' . "\n";
		$output_php .= "\n";
		$output_php .= '	function request($variable, $method = \'REQUEST\') {' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '		//--------------------------------------------------' . "\n";
		$output_php .= '		// Get value' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			$value = NULL;' . "\n";
		$output_php .= '			$method = strtoupper($method);' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			if ($method == \'POST\') {' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '				if (isset($_POST[$variable])) {' . "\n";
		$output_php .= '					$value = $_POST[$variable];' . "\n";
		$output_php .= '				}' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			} else if ($method == \'REQUEST\') {' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '				if (isset($_REQUEST[$variable])) {' . "\n";
		$output_php .= '					$value = $_REQUEST[$variable];' . "\n";
		$output_php .= '				}' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			} else {' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '				if (isset($_GET[$variable])) {' . "\n";
		$output_php .= '					$value = $_GET[$variable];' . "\n";
		$output_php .= '				}' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			}' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '		//--------------------------------------------------' . "\n";
		$output_php .= '		// Strip slashes (IF NESS)' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			if ($value !== NULL && ini_get(\'magic_quotes_gpc\')) {' . "\n";
		$output_php .= '				$value = strip_slashes_deep($value);' . "\n";
		$output_php .= '			}' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '		//--------------------------------------------------' . "\n";
		$output_php .= '		// Return value' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '			return $value;' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function exit_with_error($message, $hidden_info = NULL) {' . "\n";
		$output_php .= '		exitWithError($message, $hidden_info);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function tmp_folder($name) {' . "\n";
		$output_php .= '		$tmp_folder = sys_get_temp_dir() . \'/php-upload/\';' . "\n";
		$output_php .= '		if (!is_dir($tmp_folder)) {' . "\n";
		$output_php .= '			mkdir($tmp_folder, 0777, true);' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		return $tmp_folder;' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	// function html($text) {' . "\n";
		$output_php .= '	// 	return htmlspecialchars($text, ENT_QUOTES, config::get(\'output.charset\')); // htmlentities does not work for HTML5+XML' . "\n";
		$output_php .= '	// }' . "\n";
		$output_php .= "\n";

		$output_php .= function_code_get(array(
				'html_decode',
				'html_tag',
				'human_to_ref',
				'file_size_to_human',
				'file_size_to_bytes',
				'is_email',
				'is_assoc',
				'format_postcode',
				'format_currency',
				'parse_number',
				'prefix_match',
			));

	//--------------------------------------------------
	// Config

		$config_php = file_get_contents($path_config);

		$config_start = strpos($config_php, '// Config object');
		$config_end = strpos($config_php, "\n//--------------------------------------------------", ($config_start + 1));

		$config_php = substr($config_php, $config_start, ($config_end - $config_start));
		$config_php = '//--------------------------------------------------' . "\n" . $config_php;
		$config_php = str_replace(' extends check', '', $config_php);

		$output_php .= $config_php;

		$output_php .= '	config::set(\'output.charset\', $GLOBALS[\'pageCharset\']);' . "\n";
		$output_php .= '	config::set(\'request.uri\', $GLOBALS[\'tplPageUrl\']);' . "\n";
		$output_php .= '	config::set(\'request.url\', $GLOBALS[\'tplHttpsUrl\']);' . "\n";
		$output_php .= '	config::set(\'request.method\', (isset($_SERVER[\'REQUEST_METHOD\']) ? strtoupper($_SERVER[\'REQUEST_METHOD\']) : \'GET\'));' . "\n";
		$output_php .= '	config::set(\'request.referrer\', str_replace($GLOBALS[\'webDomainSSL\'], \'\', (isset($_SERVER[\'HTTP_REFERER\']) ? $_SERVER[\'HTTP_REFERER\'] : \'\')));';

	//--------------------------------------------------
	// Database

		$output_php .= "\n\n";
		$output_php .= '//--------------------------------------------------' . "\n";
		$output_php .= '// Database object' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '	class db extends database {' . "\n";
		$output_php .= '		public function __construct() {' . "\n";
		$output_php .= '			$this->link = $GLOBALS[\'db\']->link;' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		public function fetch_row($result = null) {' . "\n";
		$output_php .= '			return $this->fetchAssoc($result);' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		public function insert_id() {' . "\n";
		$output_php .= '			return $this->insertId();' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		public function affected_rows() {' . "\n";
		$output_php .= '			return $this->affectedRows();' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		public function enum_values($table_sql, $field) {' . "\n";
		$output_php .= '			return $this->enumValues($table_sql, $field);' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= '' . "\n";

	//--------------------------------------------------
	// Session

// 		$session_path = '/Volumes/WebServer/Projects/craig.framework/framework/0.1/library/class/session.php';
//
// 		$session_php = file_get_contents($session_path);
//
// 		$session_php = preg_replace('/^<\?php\n/', '', $session_php);
// 		$session_php = preg_replace('/\?' . '>$/', '', $session_php);
// 		$session_php = str_replace('class session_base extends check', 'class session', $session_php);
//
// 		$output_php .= "\n\n";
// 		$output_php .= '//--------------------------------------------------' . "\n";
// 		$output_php .= '// Session object' . "\n";
// 		$output_php .= $session_php;

	//--------------------------------------------------
	// Form code

		$output_php .= "//--------------------------------------------------\n";
		$output_php .= "// Form objects";

		foreach ($files as $file) {
			$file_source = file_get_contents($file);
			$file_source = trim($file_source);
			$file_source = preg_replace('/^<\?php\n/', '', $file_source);
			$file_source = preg_replace('/\?>$/', '', $file_source);
			$file_source = str_replace('_base extends', ' extends', $file_source);
			$file_source = str_replace(' extends unit', '', $file_source);
			$file_source = str_replace(' extends check', '', $file_source);
			$file_source = preg_replace('/public function saved_values_available\(\) {/', "$0\n\n\t\t\t\treturn false;", $file_source);
			$file_source = rtrim($file_source);
			$output_php .= "\n" . $file_source;
		}

	//--------------------------------------------------
	// End

		$output_php .= "\n\n" . '?>';
		$output_php = preg_replace('/^\s+$/m', '', $output_php);

//--------------------------------------------------
// Save

	config::set('debug.show', false);

	mime_set('text/plain');

	echo $output_php;

	if ($path_output != NULL) {
		// file_put_contents($path_output, $output_php);
	}

?>