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

		if (SERVER == 'stage') {
			$output_php .= '// Generated: http://craig.framework.emma.devcf.com/a/api/form-export/' . "\n";
			$output_php .= '// Tested:    http://cpoets.library.emma.devcf.com/form2/' . "\n";
			$output_php .= "\n";
		}

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
		$output_php .= '	function html_decode($html) {' . "\n";
		$output_php .= '		return htmlDecode($html);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function html_tag($tag, $attributes) {' . "\n";
		$output_php .= '		$html = \'<\' . html($tag);' . "\n";
		$output_php .= '		foreach ($attributes as $name => $value) {' . "\n";
		$output_php .= '			if ($value !== \'\' && $value !== NULL) { // Allow numerical value 0' . "\n";
		$output_php .= '				$html .= \' \' . html(is_int($name) ? $value : $name) . \'="\' . html($value) . \'"\';' . "\n";
		$output_php .= '			}' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		return $html . ($tag == \'input\' ? \' />\' : \'>\');' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function human_to_ref($text) {' . "\n";
		$output_php .= '		$text = strtolower($text);' . "\n";
		$output_php .= '		$text = preg_replace(\'/[^a-z0-9_]/i\', \'_\', $text);' . "\n";
		$output_php .= '		$text = preg_replace(\'/__+/\', \'_\', $text);' . "\n";
		$output_php .= '		$text = preg_replace(\'/_+$/\', \'\', $text);' . "\n";
		$output_php .= '		$text = preg_replace(\'/^_+/\', \'\', $text);' . "\n";
		$output_php .= '		return $text;' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function file_size_to_human($size) {' . "\n";
		$output_php .= '		return fileSize2human($size);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function file_size_to_bytes($size) {' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		$size = trim($size);' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		if (strtoupper(substr($size, -1)) == \'B\') {' . "\n";
		$output_php .= '			$size = substr($size, 0, -1); // Drop the B, as in 10B or 10KB' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		$units = array(' . "\n";
		$output_php .= '				\'P\' => 1125899906842624,' . "\n";
		$output_php .= '				\'T\' => 1099511627776,' . "\n";
		$output_php .= '				\'G\' => 1073741824,' . "\n";
		$output_php .= '				\'M\' => 1048576,' . "\n";
		$output_php .= '				\'K\' => 1024,' . "\n";
		$output_php .= '			);' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		$unit = strtoupper(substr($size, -1));' . "\n";
		$output_php .= '		if (isset($units[$unit])) {' . "\n";
		$output_php .= '			$size = (substr($size, 0, -1) * $units[$unit]);' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		return intval($size);' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function is_email($email) {' . "\n";
		$output_php .= '		return isemail($email);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function is_assoc($array) {' . "\n";
		$output_php .= '		return (count(array_filter(array_keys($array), \'is_string\')) > 0); // http://stackoverflow.com/questions/173400' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function format_british_postcode($postcode) {' . "\n";
		$output_php .= '		return formatBritishPostcode($postcode);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function format_currency($value, $currency_char = NULL, $decimal_places = 2, $zero_to_blank = false) {' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '		$value = (round($value, $decimal_places) == 0 ? 0 : $value); // Stop negative -£0' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '		if ($value == 0 && $zero_to_blank) {' . "\n";
		$output_php .= '			return \'\';' . "\n";
		$output_php .= '		} else if ($value < 0) {' . "\n";
		$output_php .= '			return \'-\' . $currency_char . number_format(floatval(0 - $value), $decimal_places);' . "\n";
		$output_php .= '		} else {' . "\n";
		$output_php .= '			return $currency_char . number_format(floatval($value), $decimal_places);' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";

	//--------------------------------------------------
	// Config

		$config_php = file_get_contents($path_config);

		$config_start = strpos($config_php, '// Config object');
		$config_end = strpos($config_php, '//--------------------------------------------------', ($config_start + 1));

		$config_php = substr($config_php, $config_start, ($config_end - $config_start));
		$config_php = '//--------------------------------------------------' . "\n" . $config_php;
		$config_php = str_replace(' extends check', '', $config_php);

		$output_php .= $config_php;

		$output_php .= '	config::set(\'output.charset\', $GLOBALS[\'pageCharset\']);' . "\n";
		$output_php .= '	config::set(\'request.uri\', $GLOBALS[\'tplPageUrl\']);' . "\n";
		$output_php .= '	config::set(\'request.url\', $GLOBALS[\'tplHttpsUrl\']);' . "\n";
		$output_php .= '	config::set(\'request.method\', (isset($_SERVER[\'REQUEST_METHOD\']) ? strtoupper($_SERVER[\'REQUEST_METHOD\']) : \'GET\'));';

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
			$file_source = str_replace(' extends check', '', $file_source);
			$file_source = preg_replace('/public function saved_values_available\(\) {/', "$0\n\n\t\t\t\treturn false;", $file_source);
			$file_source = rtrim($file_source);
			$output_php .= "\n" . $file_source;
		}

	//--------------------------------------------------
	// End

		$output_php .= "\n\n" . '?>';

//--------------------------------------------------
// Save

	config::set('debug.show', false);

	mime_set('text/plain');

	echo $output_php;

	if ($path_output != NULL) {
		// file_put_contents($path_output, $output_php);
	}

?>