<?php

	// require_once(FRAMEWORK_ROOT . '/library/tests/class-html-template.php');

	class html_template_base extends check { // Not called 'template_html' as the naming convention (type at the end) would imply that it's a HTML String... whereas this is a Template Object.

		//--------------------------------------------------
		// Variables

			protected $template_html = [];
			protected $template_end = [];
			protected $parameters = [];

		//--------------------------------------------------
		// Setup

			public function __construct($template_html, $parameters = []) {

					// This does not intend to be a full/proper templating system.
					// The context of the placeholders is not checked - so '<a href="?">?</a>' with 'javascript:evil-js' is allowed.
					// It uses a RegExp, which is bad for general HTML, but this works with known-good HTML (in theory).
					// The HTML must be a safe literal (a trusted string, from the developer, defined in the PHP script).
					// The HTML should be valid XML (why be lazy/messy?).
					// The HTML must put parameters in a Quoted Attribute or it's own HTML Tag.
					// It only uses simple HTML Encoding - which is why attributes must be quoted, to avoid '<img src=? />' being used with 'x onerror=evil-js'

				$this->template_html = preg_split('/(?<=(>)|(\'|"))\?(?=(?(1)<|\2))/', $template_html);
				$this->template_end = (count($this->template_html) - 1);

					// Positive lookbehind assertion.
					//   For a '>' (1).
					//   Or a single/double quote (2).
					// The question mark for the parameter.
					// Positive lookahead assertion.
					//   When sub-pattern (1) matched, look for a '<'.
					//   Otherwise look for the same quote mark (2).

				$this->parameters = $parameters;

			}

		//--------------------------------------------------
		// Output

			public function html($parameters = NULL) {

				if ($parameters === NULL) {
					$parameters = $this->parameters;
				}

				$html = '';

				foreach ($this->template_html as $k => $template_html) {
					$html .= $template_html;
					if ($k < $this->template_end) {
						if (isset($parameters[$k])) {
							$html .= html($parameters[$k]);
						} else {
							exit_with_error('Missing parameter ' . ($k + 1), 'Template: ' . implode('?', $this->template_html));
						}
					} else {
						if (isset($parameters[$k])) {
							exit_with_error('Extra parameter ' . ($k + 1), 'Template: ' . implode('?', $this->template_html));
						}
					}
				}

				return $html;

			}

			public function __toString() {
				return $this->html();
			}

			public function _debug_dump() {
				return 'html_template("' . implode('?', $this->template_html) . '"' . ($this->parameters ? ', ' . debug_dump($this->parameters) : '') . ')';
			}

	}

?>