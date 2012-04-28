<?php

//--------------------------------------------------
// Config

	$path_source = '/Volumes/WebServer/Projects/craig.framework/framework/0.1/class/form';
	$path_output = '/Volumes/WebServer/Projects/cpoets.library/a/php/form2.php';

//--------------------------------------------------
// Files

	$files_found = array();
	foreach (glob($path_source . '/*') as $file) {
		$name = pathinfo($file, PATHINFO_FILENAME);
		$files_found[$name] = $file;
	}

	$files_ordered = array(
		'form',
		'form_field',
		'form_field_text',
		'form_field_text_area',
		'form_field_url',
		'form_field_email',
		'form_field_number',
		'form_field_password',
		'form_field_postcode',
		'form_field_currency',
		'form_field_date',
		'form_field_time',
		'form_field_check_box',
		'form_field_check_boxes',
		'form_field_radios',
		'form_field_select',
		'form_field_file',
		'form_field_image',
		'form_field_html',
		'form_field_info',
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
		$output_php .= '// Generated: http://craig.framework.emma.devcf.com/a/api/form-export/' . "\n";
		$output_php .= '// Tested:    http://cpoets.library.emma.devcf.com/form2/' . "\n";
		$output_php .= "\n";
		$output_php .= '//--------------------------------------------------' . "\n";
		$output_php .= '// Support functions' . "\n";
		$output_php .= "\n";
		$output_php .= '	function request($variable, $method = \'REQUEST\') {' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		//--------------------------------------------------' . "\n";
		$output_php .= '		// Get value' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			$value = NULL;' . "\n";
		$output_php .= '			$method = strtoupper($method);' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			if ($method == \'POST\') {' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '				if (isset($_POST[$variable])) {' . "\n";
		$output_php .= '					$value = $_POST[$variable];' . "\n";
		$output_php .= '				}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			} else if ($method == \'REQUEST\') {' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '				if (isset($_REQUEST[$variable])) {' . "\n";
		$output_php .= '					$value = $_REQUEST[$variable];' . "\n";
		$output_php .= '				}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			} else {' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '				if (isset($_GET[$variable])) {' . "\n";
		$output_php .= '					$value = $_GET[$variable];' . "\n";
		$output_php .= '				}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		//--------------------------------------------------' . "\n";
		$output_php .= '		// Strip slashes (IF NESS)' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			if ($value !== NULL && ini_get(\'magic_quotes_gpc\')) {' . "\n";
		$output_php .= '				$value = strip_slashes_deep($value);' . "\n";
		$output_php .= '			}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		//--------------------------------------------------' . "\n";
		$output_php .= '		// Return value' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '			return $value;' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function html_decode($html) {' . "\n";
		$output_php .= '		return htmlDecode($html);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function html_tag($tag, $attributes) {' . "\n";
		$output_php .= '		$html = \'<\' . html($tag);' . "\n";
		$output_php .= '		foreach ($attributes as $name => $value) {' . "\n";
		$output_php .= '			if ($value != \'\') {' . "\n";
		$output_php .= '				$html .= \' \' . html(is_int($name) ? $value : $name) . \'="\' . html($value) . \'"\';' . "\n";
		$output_php .= '			}' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '		return $html . ($tag == \'input\' ? \' />\' : \'>\');' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function human_to_ref($text) {' . "\n";
		$output_php .= '		return human2camel($text);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function file_size_to_human($size) {' . "\n";
		$output_php .= '		return fileSize2human($size);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function is_email($email) {' . "\n";
		$output_php .= '		return isemail($email);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function format_british_postcode($postcode) {' . "\n";
		$output_php .= '		return formatBritishPostcode($postcode);' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";
		$output_php .= '	function format_currency($value, $currency_char = NULL, $decimal_places = 2, $zero_to_blank = false) {' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		$value = (round($value, $decimal_places) == 0 ? 0 : $value); // Stop negative -£0' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '		if ($value == 0 && $zero_to_blank) {' . "\n";
		$output_php .= '			return \'\';' . "\n";
		$output_php .= '		} else if ($value < 0) {' . "\n";
		$output_php .= '			return \'-\' . $currency_char . number_format(floatval(0 - $value), $decimal_places);' . "\n";
		$output_php .= '		} else {' . "\n";
		$output_php .= '			return $currency_char . number_format(floatval($value), $decimal_places);' . "\n";
		$output_php .= '		}' . "\n";
		$output_php .= '	' . "\n";
		$output_php .= '	}' . "\n";
		$output_php .= "\n";

	//--------------------------------------------------
	// Config

		$config_path = '/Volumes/WebServer/Projects/craig.framework/framework/0.1/system/02.config.php';

		$config_php = file_get_contents($config_path);

		$config_start = strpos($config_php, '//--------------------------------------------------');
		$config_end = strpos($config_php, '//--------------------------------------------------', ($config_start + 1));

		$config_php = substr($config_php, $config_start, ($config_end - $config_start));

		$config_php = str_replace(' extends check', '', $config_php);

		$output_php .= $config_php;

		$output_php .= '	config::set(\'output.charset\', $GLOBALS[\'pageCharset\']);' . "\n";
		$output_php .= '	config::set(\'request.url\', $GLOBALS[\'tplPageUrl\']);' . "\n";
		$output_php .= '	config::set(\'request.url_https\', $GLOBALS[\'tplHttpsUrl\']);' . "\n";
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
		$output_php .= '		public function fetch_assoc($result = null) {' . "\n";
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

// 		$session_path = '/Volumes/WebServer/Projects/craig.framework/framework/0.1/class/session.php';
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

	mime_set('text/plain');
	echo $output_php;

	file_put_contents($path_output, $output_php);

?>