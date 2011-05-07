<?php

//--------------------------------------------------
// Setup

	//--------------------------------------------------
	// Notes

		config::set('debug.notes', array());

	//--------------------------------------------------
	// Start time

		$start_time = explode(' ', microtime());
		$start_time = ((float)$start_time[0] + (float)$start_time[1]);

		config::set('debug.start_time', $start_time);

		unset($start_time);

	//--------------------------------------------------
	// Query time

		config::set('debug.query_time', 0);

//--------------------------------------------------
// Quick debug print of a variable

	function debug($variable) {
		echo '<pre>';
		echo var_export($variable, true); // view:add_debug() if were not in a view.
		echo '</pre>';
	}

//--------------------------------------------------
// Debug notes

	function debug_note_add($note, $show_time = true) {
		debug_note_add_html(nl2br(str_replace(' ', '&nbsp;', html($note))), $show_time);
	}

	function debug_note_add_html($note_html, $show_time = true) {

		//--------------------------------------------------
		// Time position

			if ($show_time) {

				$time_end = explode(' ', microtime());
				$time_end = ((float)$time_end[0] + (float)$time_end[1]);

				$time = round(($time_end - config::get('debug.start_time')), 4);

			} else {

				$time = NULL;

			}

		//--------------------------------------------------
		// Note

			config::array_push('debug.notes', array(
					'html' => $note_html,
					'time' => $time,
				));

	}

//--------------------------------------------------
// Error reporting

	function exit_with_error($message, $hidden_info = NULL) {
		exit($message);
	}

	function add_report($message, $type = 'notice') {

		//--------------------------------------------------
		// Email

			$email = new email();
			$email->send(config::get('email.error'));

	}

//--------------------------------------------------
// Function to show configuration

	function debug_show_config($prefix = '') {

		$config = config::get_all($prefix);

		ksort($config);

		$config_html = array($prefix == '' ? 'Configuration:' : ucfirst($prefix) . ' configuration:');
		foreach ($config as $key => $value) {
			if (in_array($key, array('db.pass', 'debug.notes', 'view.variables'))) {
				$value_html = '???';
			} else {
				$value_html = html(var_export($value, true));
			}
			$config_html[] = '&nbsp; <strong>' . html(($prefix == '' ? '' : $prefix . '.') . $key) . '</strong>: ' . $value_html;
		}

		debug_note_add_html(implode($config_html, '<br />' . "\n"), false);

	}

//--------------------------------------------------
// Stage debugging

	if (config::get('debug.run')) {

		//--------------------------------------------------
		// Config

			debug_show_config();

	}

//--------------------------------------------------
// Debug shutdown

	function debug_shutdown($buffer) {

		//--------------------------------------------------
		// Suppression

			if (!config::get('debug.run') || !config::get('debug.show')) {
				return $buffer;
			}

		//--------------------------------------------------
		// Default CSS

			$css_text = 'font-size: 12px; font-family: verdana; font-weight: normal; text-align: left; text-decoration: none;';
			$css_block = 'margin: 5px 0; padding: 5px; background: #FFF; color: #000; border: 1px solid #000; clear: both;';
			$css_para = 'text-align: left; padding: 0; margin: 0; ' . $css_text;

		//--------------------------------------------------
		// Time taken

			$time_end = explode(' ', microtime());
			$time_end = ((float)$time_end[0] + (float)$time_end[1]);

			$time = round(($time_end - config::get('debug.start_time')), 4);

			$output_html = '
				<div style="' . html($css_block) . '">
					<p style="' . html($css_para) . '">Elapsed time: ' . html($time) . '</p>
					<p style="' . html($css_para) . '">Query time: ' . html(config::get('debug.query_time')) . '</p>
				</div>';

		//--------------------------------------------------
		// Notes

			$notes = config::get('debug.notes');

			foreach ($notes as $note) {
				$output_html .= '
					<div style="' . html($css_block) . '">
						<p style="' . html($css_para) . '">' . $note['html'] . '</p>';
				if ($note['time'] !== NULL) {
					$output_html .= '
						<p style="' . html($css_para) . '">Time Elapsed: ' . html($note['time']) . '</p>';
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
			if ($pos !== false) {

		 		return substr($buffer, 0, $pos) . $output_html . substr($buffer, $pos);

			} else {

				if (config::get('output.mime') == 'application/xhtml+xml') {
					config::set('output.mime', 'text/html');
				}

		 		return $buffer . $output_html;

			}

	}

?>