<?php

	class html_template_base extends check { // Not called 'template_html' as the naming convention (type at the end) would imply that it's a HTML String... whereas this is a Template Object.

		//--------------------------------------------------
		// Variables

			protected $source_html = NULL;
			protected $source_parameters = [];

			protected $template_split_html = NULL;
			protected $template_split_end = NULL;
			protected $template_contexts = [];
			protected $template_parameters = NULL;
			protected $template_parameter_types = [];
			protected $template_allowed = [ // Do not allow <script>, <style>, <link>, <object>, <embed> tags; or attributes that can include JS (e.g. style, onload, dynsrc)... although some can accept url(x) values

					'meta'       => ['name' => 'text', 'content' => 'text'], // Do not allow <meta http-equiv="">, e.g. Refresh, Set-Cookie
					'link'       => ['rel' => ['preload', 'prefetch', 'prerender', 'canonical', 'next'], 'as' => ['image', 'font', 'track', 'video', 'audio'], 'href' => 'url', 'fetchpriority' => ['high', 'low']],
					'div'        => ['id' => 'ref', 'class' => 'ref', 'role' => 'text', 'title' => 'text', 'tabindex' => 'int', 'inert' => ['inert']],
					'section'    => ['id' => 'ref', 'class' => 'ref', 'role' => 'text', 'title' => 'text', 'tabindex' => 'int', 'inert' => ['inert']],
					'span'       => ['id' => 'ref', 'class' => 'ref', 'role' => 'text', 'title' => 'text', 'tabindex' => 'int'],
					'header'     => ['id' => 'ref', 'class' => 'ref'],
					'nav'        => ['id' => 'ref', 'class' => 'ref'],
					'main'       => ['id' => 'ref', 'class' => 'ref'],
					'footer'     => ['id' => 'ref', 'class' => 'ref'],
					'h1'         => ['id' => 'ref', 'class' => 'ref'],
					'h2'         => ['id' => 'ref', 'class' => 'ref'],
					'h3'         => ['id' => 'ref', 'class' => 'ref'],
					'h4'         => ['id' => 'ref', 'class' => 'ref'],
					'h5'         => ['id' => 'ref', 'class' => 'ref'],
					'h6'         => ['id' => 'ref', 'class' => 'ref'],
					'p'          => ['id' => 'ref', 'class' => 'ref'],
					'ul'         => ['id' => 'ref', 'class' => 'ref'],
					'ol'         => ['id' => 'ref', 'class' => 'ref', 'start' => 'int'],
					'li'         => ['id' => 'ref', 'class' => 'ref', 'value' => 'int'],
					'dl'         => ['id' => 'ref', 'class' => 'ref'],
					'dt'         => ['id' => 'ref', 'class' => 'ref'],
					'dd'         => ['id' => 'ref', 'class' => 'ref'],
					'pre'        => ['id' => 'ref', 'class' => 'ref'],
					'fieldset'   => ['id' => 'ref', 'class' => 'ref'], // Not adding <form> by default.
					'legend'     => ['id' => 'ref', 'class' => 'ref'],
					'table'      => ['id' => 'ref', 'class' => 'ref'],
					'caption'    => ['id' => 'ref', 'class' => 'ref'],
					'thead'      => ['id' => 'ref', 'class' => 'ref'],
					'tbody'      => ['id' => 'ref', 'class' => 'ref'],
					'tfoot'      => ['id' => 'ref', 'class' => 'ref'],
					'tr'         => ['id' => 'ref', 'class' => 'ref'],
					'th'         => ['id' => 'ref', 'class' => 'ref', 'rowspan' => 'int', 'colspan' => 'int', 'scope' => 'text'],
					'td'         => ['id' => 'ref', 'class' => 'ref', 'rowspan' => 'int', 'colspan' => 'int'],
					'em'         => ['id' => 'ref', 'class' => 'ref', 'title' => 'text'],
					'strong'     => ['id' => 'ref', 'class' => 'ref', 'title' => 'text'],
					'hr'         => ['id' => 'ref', 'class' => 'ref'],
					'sub'        => ['id' => 'ref', 'class' => 'ref'],
					'sup'        => ['id' => 'ref', 'class' => 'ref'],
					'abbr'       => ['id' => 'ref', 'class' => 'ref', 'title' => 'text', 'aria-label' => 'text'],
					'cite'       => ['id' => 'ref', 'class' => 'ref'],
					'code'       => ['id' => 'ref', 'class' => 'ref'],
					'samp'       => ['id' => 'ref', 'class' => 'ref'],
					'mark'       => ['id' => 'ref', 'class' => 'ref'],
					'var'        => ['id' => 'ref', 'class' => 'ref'],
					'wbr'        => ['id' => 'ref', 'class' => 'ref'],
					'del'        => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'ins'        => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'blockquote' => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'q'          => ['id' => 'ref', 'class' => 'ref', 'cite' => 'url'],
					'a'          => ['id' => 'ref', 'class' => 'ref', 'href' => 'url', 'target' => ['_blank'], 'rel' => ['noopener', 'noreferrer', 'nofollow'], 'hreflang' => 'text'],
					'picture'    => ['id' => 'ref', 'class' => 'ref'],
					'source'     => ['id' => 'ref', 'class' => 'ref', 'src' => 'url-img', 'srcset' => 'text', 'sizes' => 'text', 'type' => 'text'],
					'img'        => ['id' => 'ref', 'class' => 'ref', 'src' => 'url-img', 'srcset' => 'text', 'sizes' => 'text', 'alt' => 'text', 'title' => 'text', 'width' => 'int', 'height' => 'int', 'fetchpriority' => ['high', 'low'], 'tabindex' => 'int'],
					'time'       => ['id' => 'ref', 'class' => 'ref', 'datetime' => 'datetime'],
					'data'       => ['id' => 'ref', 'class' => 'ref', 'value' => 'text'],
					'input'      => ['id' => 'ref', 'class' => 'ref', 'type' => ['submit', 'button'], 'name' => 'ref', 'value' => 'text'],
					'br'         => [],

				];

		//--------------------------------------------------
		// Setup

			public function __construct($html, $parameters = []) {
				$this->source_html = $html;
				$this->source_parameters = $parameters;
			}

			public function unsafe_allow_node($node, $attributes = []) { // Be VERY careful in what you allow.
				if (!array_key_exists($node, $this->template_allowed)) {
					$this->template_allowed[$node] = [];
				}
				$this->template_allowed[$node] = array_merge($this->template_allowed[$node], $attributes);
			}

		//--------------------------------------------------
		// Node walking

			private function node_walk($parent, $root = true) {
				foreach ($parent->childNodes as $node) {
					if ($node->nodeType === XML_TEXT_NODE) {
						if ($node->wholeText === '?') {
							$this->template_parameters[] = [$parent->nodeName, NULL];
						}
					} else if (!array_key_exists($node->nodeName, $this->template_allowed) && $root !== true) { // Skip for the root node
						throw new error_exception('HTML Templates cannot use <' . $node->nodeName . '>', debug_dump($this->source_html));
					} else {
						if ($node->hasAttributes()) {
							$allowed_attributes = $this->template_allowed[$node->nodeName];
							foreach ($node->attributes as $attr) {
								if (!array_key_exists($attr->nodeName, $allowed_attributes) && !str_starts_with($attr->nodeName, 'data-')) {
									throw new error_exception('HTML Templates cannot use the "' . $attr->nodeName . '" attribute in <' . $node->nodeName . '>', debug_dump($this->source_html));
								} else if ($node->nodeName === 'meta' && $attr->nodeName === 'name' && in_array($attr->nodeValue, ['?', 'referrer'])) {
									throw new error_exception('HTML Templates cannot allow the "name" attribute in <meta> to be set to "' . $attr->nodeValue . '"', debug_dump($this->source_html));
								} else if ($attr->nodeValue === '?') {
									$this->template_parameters[] = [$node->nodeName, $attr->nodeName];
								}
							}
						}
						if ($node->hasChildNodes()) {
							$this->node_walk($node, false);
						}
					}
				}
			}

		//--------------------------------------------------
		// Output

			public function html($parameters = NULL) {

				//--------------------------------------------------
				// Config

					if ($parameters === NULL) {
						$parameters = $this->source_parameters;
					}

				//--------------------------------------------------
				// Parsing

					if ($this->template_split_html === NULL) {

						//--------------------------------------------------
						// Simple Template HTML split

								// This does not intend to be a full/proper templating system.
								// The context of the placeholders is only roughly checked, when in debug mode, via XML parsing.
								// It uses a RegExp, which is bad for general HTML, but it's fast, and can work with known-good XHTML (in theory).
								// The HTML must be a safe literal (a trusted string, from the developer, defined in the PHP script).
								// The HTML must be valid XML (why be lazy/messy?).
								// The HTML must include parameters in a Quoted Attribute, or it's own HTML Tag.
								// It only uses simple HTML Encoding - which is why attributes must be quoted, to avoid '<img src=? />' being used with 'x onerror=evil-js'

							$this->template_split_html = preg_split('/(?<=(>)|(\'|"))\?(?=(?(1)<\/|\2))/', $this->source_html);
							$this->template_split_end = (count($this->template_split_html) - 1);

								// Positive lookbehind assertion.
								//   For a '>' (1).
								//   Or a single/double quote (2).
								// The question mark for the parameter.
								// Positive lookahead assertion.
								//   When sub-pattern (1) matched, look for a '<'.
								//   Otherwise look for the same quote mark (2).

						//--------------------------------------------------
						// Guessed context for parameters (i.e. use nl2br?)

								// Use the HTML afterwards, where it's easier to get the end tag,
								// as the start tag may be multiple parameters away.
								//   <em class="?">?</em>

							$this->template_contexts = [];

							for ($k = 1; $k <= $this->template_split_end; $k++) { // 1 to ignore the first section
								if (substr($this->template_split_html[$k], 0, 1) === '<') {
									if (preg_match('/^<\/([a-z0-9\-]+)>/i', $this->template_split_html[$k], $matches)) {
										$this->template_contexts[] = $matches[1];
									} else {
										throw new error_exception('Placeholder ' . $k . ' is not followed by a valid closing tag', debug_dump($this->source_html));
									}
								} else {
									$this->template_contexts[] = NULL; // It should be an attribute (single or double quotes).
								}
							}

						//--------------------------------------------------
						// Primitive tag and attribute checking

							if (config::get('debug.level') > 0) {

								if (SERVER == 'stage' && function_exists('is_literal') && !is_literal($this->source_html) && config::get('html_template.unsafe_disable_literal_check', false) !== true) {
									foreach (debug_backtrace() as $called_from) {
										if (isset($called_from['file']) && !str_starts_with($called_from['file'], FRAMEWORK_ROOT)) {
											break;
										}
									}
									echo "\n";
									echo '<div>' . "\n";
									echo '	<h1>Error</h1>' . "\n";
									echo '	<p><strong>' . str_replace(ROOT, '', $called_from['file']) . '</strong> (line ' . $called_from['line'] . ')</p>' . "\n";
									echo '	<p>The following HTML has been tainted.</p>' . "\n";
									echo '	<hr />' . "\n";
									echo '	<p><pre>' . "\n\n" . html($this->source_html) . "\n\n" . '</pre></p>' . "\n";
									echo '</div>' . "\n";
									exit();
								}

									// Your HTML should be valid XML,
									// as it ensures strict/valid nesting,
									// attributes are quoted (important!),
									// and attributes cannot be redefined.
									//
									// You can use:
									//   '<img />' for self closing tags
									//   '<tag attribute="attribute">' for boolean attributes.

								$old = libxml_use_internal_errors(true); // "Disabling will also clear any existing libxml errors"...

								libxml_clear_errors(); // ... Turns out it doesn't

								$html_prefix = '<?xml version="1.0" encoding="' . xml(config::get('output.charset')) . '"?><html>';
								$html_suffix = '</html>';

								$doc = new DomDocument();
								$doc->loadXML($html_prefix . $this->source_html . $html_suffix);

								foreach (libxml_get_errors() as $error) {
									libxml_clear_errors();
									throw new error_exception('HTML Templates must be valid XML', trim($error->message) . ' (line ' . $error->line . ':' . (intval($error->column) - strlen($html_prefix)) . ')' . "\n" . debug_dump($this->source_html));
								}

								libxml_use_internal_errors($old);

								$this->template_parameters = [];

								$this->node_walk($doc);

								foreach ($this->template_parameters as $k => $p) {
									$allowed_attributes = ($this->template_allowed[$p[0]] ?? NULL);
									if ($allowed_attributes === NULL) {
										throw new error_exception('Placeholder ' . ($k + 1) . ' is for unrecognised tag "' . $p[0] . '"', debug_dump($this->source_html));
									} else if ($p[1] === NULL) {
										// Content for a tag, so long as it's not an unsafe tag (e.g. <script>), it should be fine.
									} else if (($attribute_type = ($allowed_attributes[$p[1]] ?? NULL)) !== NULL) {
										$this->template_parameter_types[$k] = $attribute_type; // Generally fine, but check the type.
									} else if (str_starts_with($p[1], 'data-')) {
										// Can't tell, this is for JS/CSS to read and use.
									} else {
										throw new error_exception('Placeholder ' . ($k + 1) . ' is for unrecognised tag "' . $p[0] . '" and attribute "' . $p[1] . '"', debug_dump($this->source_html));
									}
								}

							}

					}

				//--------------------------------------------------
				// Check parameter values

					foreach ($this->template_parameter_types as $k => $type) {
						if (!isset($parameters[$k])) {
							// Ignore this missing parameter, should be picked up next.
						} else if (is_array($type)) {
							$valid = true;
							if (!in_array($parameters[$k], $type)) {
								foreach (preg_split('/ +/', $parameters[$k]) as $token) { // supporting "space-separated tokens"
									if (!in_array($token, $type)) {
										$valid = false;
										break;
									}
								}
							}
							if (!$valid) {
								throw new error_exception('Parameter ' . ($k + 1) . ' can only support the values "' . implode('", "', $type) . '".', debug_dump($parameters[$k]) . "\n" . debug_dump($this->source_html));
							}
						} else if ($type === 'text') {
							// Nothing to check
						} else if ($type === 'url-img' && ($parameters[$k] instanceof url_data) && substr($parameters[$k]->mime_get(), 0, 6) === 'image/') {
							// Images are allowed "data:" URLs with mime-types such as 'image/jpeg'
						} else if ($type === 'url' || $type === 'url-img') {
							if (!($parameters[$k] instanceof url) && !($parameters[$k] instanceof url_immutable) && $parameters[$k] !== '#') {
								throw new error_exception('Parameter ' . ($k + 1) . ' should be a URL object.', debug_dump($parameters[$k]) . "\n" . debug_dump($this->source_html));
							}
						} else if ($type === 'int') {
							if (!is_int($parameters[$k])) {
								throw new error_exception('Parameter ' . ($k + 1) . ' should be an integer.', debug_dump($parameters[$k]) . "\n" . debug_dump($this->source_html));
							}
						} else if ($type === 'ref') {
							foreach (explode(' ', $parameters[$k]) as $ref) {
								$ref = trim($ref);
								if (!preg_match('/^[a-z][a-z0-9\-\_]+$/i', $ref)) { // Simple strings aren't checked outside of debug mode, but it might catch something during development.
									throw new error_exception('Parameter ' . ($k + 1) . ' should be one or more valid references.', debug_dump($ref) . "\n" . debug_dump($this->source_html));
								}
							}
						} else if ($type === 'datetime') {
							if (!preg_match('/^[0-9TWZPHMS \:\-\.\+]+$/i', $parameters[$k])) { // Could be better, but not important, as simple strings aren't checked outside of debug mode, and shouldn't be executed as JS by the browser... T=Time, W=Week, Z=Zulu, and PTHMS for duration
								throw new error_exception('Parameter ' . ($k + 1) . ' should be a valid datetime.', debug_dump($parameters[$k]) . "\n" . debug_dump($this->source_html));
							}
						} else {
							throw new error_exception('Parameter ' . ($k + 1) . ' has an unknown type.', $type . "\n" . debug_dump($parameters[$k]) . "\n" . debug_dump($this->source_html));
						}
					}

				//--------------------------------------------------
				// Create HTML

					$html = '';

					foreach ($this->template_split_html as $k => $template_html) {
						$html .= $template_html;
						if ($k < $this->template_split_end) {
							if (array_key_exists($k, $parameters)) { // Could be NULL
								if (($this->template_contexts[$k] !== NULL) && ($parameters[$k] instanceof html_template || $parameters[$k] instanceof html_safe_value)) { // Not an attribute
									$html .= $parameters[$k];
								} else if (in_array($this->template_contexts[$k], [NULL, 'pre'])) { // Assumed context, with NULL for an attribute
									$html .= html($parameters[$k]);
								} else {
									$html .= nl2br(html($parameters[$k]));
								}
							} else {
								throw new error_exception('Missing parameter ' . ($k + 1), debug_dump($this->source_html));
							}
						} else if (isset($parameters[$k])) {
							throw new error_exception('Extra parameter ' . ($k + 1), debug_dump($this->source_html));
						}
					}

					return new html_safe_value($html);

			}

			public function __toString() {
				return strval($this->html());
			}

			public function _debug_dump() {
				return 'html_template("' . $this->source_html . '"' . ($this->source_parameters ? ', ' . debug_dump($this->source_parameters) : '') . ')';
			}

	}

	// require_once(FRAMEWORK_ROOT . '/library/tests/class-html-template.php');

?>