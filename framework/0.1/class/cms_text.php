<?php

/***************************************************

	//--------------------------------------------------
	// License

		This source code is released under the BSD licence,
		see the end of this script for the full details.
		It was originally created by Craig Francis in 2006.

		http://www.craigfrancis.co.uk/features/code/phpCmsText/

	//--------------------------------------------------
	// Example setup

		//--------------------------------------------------
		// Initialise

			$cms_text = new cms_text();

			--- or ---

			$cms_text = new cms_text(array(
					'allow_html_code' => false,
					'allow_popup_links' => false,
					'allow_mail_links' => false,
					'allow_img_tags' => false,
					'allow_para_align' => false,
					'allow_list_tags' => false,
					'allow_table_tags' => false,
					'allow_heading_tags' => false,
					'no_follow_links' => false,
					'hide_cms_comments' => false,
					'heading_level' => 3,
				));

		//--------------------------------------------------
		// Print out the HTML, either using process_inline_html()
		// for <p> tags (etc), or as process_block_html() to
		// be printed straight into a <div>.

			echo $cms_text->process_inline_html($text);
			echo $cms_text->process_block_html($text);

			echo $cms_text->process_text($text); // Could be used in an email

***************************************************/

class cms_text_base extends check {

	var $preserved_inline_tags;
	var $indent_level;
	var $config;

	public function __construct($config = NULL) {
		$this->preserved_inline_tags = array();
		$this->indent_level = 1;
		$this->config_set($config);
	}

	public function config_set($config) {

		//--------------------------------------------------
		// If this class was not initialised with a config
		// array parameter.

			if ($config === NULL) {
				$config = array();
			}

		//--------------------------------------------------
		// The config setup is an array

			$this->config = array();

		//--------------------------------------------------
		// Boolean (permission) values - default to false

			$this->config['allow_html_code']       = (isset($config['allow_html_code'])       && $config['allow_html_code']       === true);
			$this->config['allow_popup_links']     = (isset($config['allow_popup_links'])     && $config['allow_popup_links']     === true);
			$this->config['allow_mail_links']      = (isset($config['allow_mail_links'])      && $config['allow_mail_links']      === true);
			$this->config['allow_img_tags']        = (isset($config['allow_img_tags'])        && $config['allow_img_tags']        === true);
			$this->config['allow_para_align']      = (isset($config['allow_para_align'])      && $config['allow_para_align']      === true);
			$this->config['allow_list_tags']       = (isset($config['allow_list_tags'])       && $config['allow_list_tags']       === true);
			$this->config['allow_table_tags']      = (isset($config['allow_table_tags'])      && $config['allow_table_tags']      === true);
			$this->config['allow_heading_tags']    = (isset($config['allow_heading_tags'])    && $config['allow_heading_tags']    === true);
			$this->config['no_follow_links']       = (isset($config['no_follow_links'])       && $config['no_follow_links']       === true);
			$this->config['hide_cms_comments']     = (isset($config['hide_cms_comments'])     && $config['hide_cms_comments']     === true);

			$this->config['plain_text_mail_links'] = (!isset($config['plain_text_mail_links']) || $config['plain_text_mail_links'] == true); // Default to be plain text

		//--------------------------------------------------
		// General config

			if (isset($config['heading_level'])) {
				$this->config['heading_level'] = $config['heading_level']; // Valid number checked later
			} else {
				$this->config['heading_level'] = NULL;
			}

	}

	public function change_config($key, $value) {

		if ($key == 'heading_level') {

			$this->config['heading_level'] = $value;

		} else if (isset($this->config[$key])) {

			$this->config[$key] = ($value === true);

		}

	}

	public function process_text($string) {

		$html = $this->process_block_html($string);
		$html = str_replace("<p>\n", '', $html);
		$html = preg_replace('/(\n\t*)?<\/p>/', "\n", $html);
		$html = str_replace('&#xA0;', " ", $html);
		$html = preg_replace('/^\t+/m', '', $html);
		$html = str_replace("\n<ul>", '', $html);
		$html = str_replace('<li>', '# ', $html);

		preg_match_all('/<a href="(mailto:)?(.*?)"[^>]*>(.*?)<\/a>/', $html, $matches, PREG_SET_ORDER);
		foreach ($matches as $cMatch) {
			$html = str_replace($cMatch[0], $cMatch[3] . ' [' . $cMatch[2] . ']', $html);
		}

		return trim(strip_tags($html));

	}

	public function process_inline_html($string, $preserve_open_tags = false, $child_of_tag = NULL) { // Do not set 'child_of_tag', it's considered PRIVATE

		//--------------------------------------------------
		// Make the string HTML safe and remove whitespace
		// at the end.

 				// NOTE: If this is a child of a tag,
				// then the content would have already
				// been html encoded.

			if ($child_of_tag === NULL) {
				$string = rtrim(html($string));
			}

		//--------------------------------------------------
		// Defaults

			$in_tag_text = NULL;
			$in_tag_attribute = NULL;
			$escaping_stack = 0;
			$tag_stack = array();
			$output_html = '';

			$inline_tags = array();
			$inline_tags['b']['open'] = '<strong>';
			$inline_tags['b']['close'] = '</strong>';
			$inline_tags['i']['open'] = '<em>';
			$inline_tags['i']['close'] = '</em>';

		//--------------------------------------------------
		// If any tags were preserved from the last run,
		// then re-open them

			if ($preserve_open_tags === true) {
				while ($tag = array_pop($this->preserved_inline_tags)) {
					$tag_stack[] = $tag;
					$output_html .= $inline_tags[$tag]['open'];
				}
			}

		//--------------------------------------------------
		// Loop though each of the characters in the string

			$string_length = strlen($string);
			for ($i = 0; $i < $string_length; $i++) {

				$char = $string{$i};

				if ($char == '[' && $in_tag_attribute === NULL) {

					//--------------------------------------------------
					// If we are already collecting data for a tag, which
					// has not finished yet, then then its not really a
					// tag... the user has just used "["

						if ($in_tag_text !== NULL) {

							$tmp = str_repeat('\\', $escaping_stack) . '[' . $in_tag_text;

							$result = preg_match('/^(.*?)(\\\\*)$/', $tmp, $matches);
							if ($result) {
								$output_html .= $matches[1];
								$escaping_stack = strlen($matches[2]); // Slashes before the new "["
							} else {
								$output_html .= $tmp;
								$escaping_stack = 0;
							}

						}

					//--------------------------------------------------
					// Remember we are now in tag mode (not NULL)

						$in_tag_text = '';

				} else if ($in_tag_text !== NULL) {

					if ($char == ']' && $in_tag_attribute === NULL) {

						//--------------------------------------------------
						// The tag text has been collected, so now extract
						// the tag parts

							$result = preg_match('/^(\/?)([^ ]*)( +.*)?$/', $in_tag_text, $matches);
							if ($result) {
								$tag_type_opener = ($matches[1] == '');
								$tag_name_lower = strtolower($matches[2]);
								$tag_attributes = (isset($matches[3]) ? $matches[3] : '');
							} else {
								$tag_type_opener = false;
								$tag_name_lower = '';
								$tag_attributes = '';
							}

						//--------------------------------------------------
						// Process the tag (if ness) by setting the
						// $tag_output to something other than NULL

							$tag_output = NULL;
							$tag_escaped = (($escaping_stack % 2) != 0);
							$tag_valid = isset($inline_tags[$tag_name_lower]);

							if ($tag_valid && $tag_attributes != '') {
								$tag_valid = false; // Inline tags arn't allowed attributes
							}

							if ($tag_valid && $tag_escaped != true) {
								if ($tag_type_opener) {

									//--------------------------------------------------
									// This this is an opening tag, if it is not already
									// open AND this is not at the end of the string,
									// then open it now... otherwise we cannot open it
									// again, so silently ignore it.

										if (!in_array($tag_name_lower, $tag_stack) && (($i + 1) < $string_length)) {
											$tag_output = $inline_tags[$tag_name_lower]['open'];
											$tag_stack[] = $tag_name_lower;
										} else {
											$tag_output = '';
										}

								} else if (!in_array($tag_name_lower, $tag_stack)) {

									//--------------------------------------------------
									// This is a CLOSING tag that is NOT open, so
									// silently ignore it.

										$tag_output = '';

								} else {

									//--------------------------------------------------
									// This is a CLOSING tag that is already open

										$tag_output = '';
										$temp_stack = array();

									//--------------------------------------------------
									// Close all of the tags which are currently open,
									// until we find the one the user has requested that
									// we close (XML formatting).

										$processing = true;
										while ($processing) {
											$tag = array_pop($tag_stack);
											$tag_output .= $inline_tags[$tag]['close'];
											if ($tag == $tag_name_lower) {
												$processing = false;
											} else {
												$temp_stack[] = $tag;
											}
										}

									//--------------------------------------------------
									// Reopen any of the tags which were closed in order
									// to meet the users requested tag close.

										while ($tag = array_pop($temp_stack)) {
											$tag_stack[] = $tag;
											$tag_output .= $inline_tags[$tag]['open'];
										}

								}
							} else {

								//--------------------------------------------------
								// This might be an inline element, so extract
								// the attributes

									$tag_attrib1 = '';
									$tag_attrib2 = '';
									$tag_attrib3 = '';

									$found = preg_match('/^ +(&quot;|&#039;)(.*?)\1(?: +\1(.*?)\1)?(?: +\1(.*?)\1)?$/', $tag_attributes, $matches);
									if ($found) {

										//--------------------------------------------------
										// Attribute 1

											$tag_attrib1 = $matches[2];

										//--------------------------------------------------
										// Attribute 2

												// NOTE: Due to in_tag_attribute, a child tag might
												// appear within this attribute. However, we cannot
												// preserve_open_tags, as we would need to re-open
												// them blindly, potentially creating XML errors.

											if (isset($matches[3])) {

												$tag_attrib2 = $matches[3];

												if ($tag_name_lower == 'link' || $tag_name_lower == 'open' || $tag_name_lower == 'mail') {
													$tag_attrib2 = $this->process_inline_html($tag_attrib2, false, true);
												}

											}

										//--------------------------------------------------
										// Attribute 3

											if (isset($matches[4])) {
												$tag_attrib3 = $matches[4];
											}

									}

								//--------------------------------------------------
								// If this is a recognised inline element, replace
								// it with the relevant HTML

										// NOTE: If we are processing some text which is
										// a child of a tag, then it should be a child
										// of a link... and as such, we don't to allow
										// and of the following link tags.

									if ($tag_attrib1 != '') {
										if ($tag_name_lower == 'link' && $child_of_tag === NULL) {

											$tag_valid = true;
											if ($tag_escaped != true) {
												if ($tag_attrib2 == '') {
													$tag_output = '<a href="' . $tag_attrib1 . '"' . ($this->config['no_follow_links'] ? ' rel="nofollow"' : '') . '>' . $tag_attrib1 . '</a>';
												} else {
													$tag_output = '<a href="' . $tag_attrib1 . '"' . ($this->config['no_follow_links'] ? ' rel="nofollow"' : '') . '>' . $tag_attrib2 . '</a>';
												}
											}

										} else if ($tag_name_lower == 'open' && $this->config['allow_popup_links'] && $child_of_tag === NULL) {

											$tag_valid = true;
											if ($tag_escaped != true) {
												if ($tag_attrib2 == '') {
													$tag_output = '<a href="' . $tag_attrib1 . '" onclick="window.open(this.href); return false;"' . ($this->config['no_follow_links'] ? ' rel="nofollow"' : '') . '>' . $tag_attrib1 . '</a>';
												} else {
													$tag_output = '<a href="' . $tag_attrib1 . '" onclick="window.open(this.href); return false;"' . ($this->config['no_follow_links'] ? ' rel="nofollow"' : '') . '>' . $tag_attrib2 . '</a>';
												}
											}

										} else if ($tag_name_lower == 'mail' && $this->config['allow_mail_links'] && $child_of_tag === NULL) {

											$tag_valid = true;
											if ($tag_escaped != true) {
												if ($this->config['plain_text_mail_links']) {
													if ($tag_attrib2 == '') {
														$tag_output = '<a href="mailto:' . $tag_attrib1 . '">' . $tag_attrib1 . '</a>';
													} else {
														$tag_output = '<a href="mailto:' . $tag_attrib1 . '">' . $tag_attrib2 . '</a>';
													}
												} else {
													if ($tag_attrib2 == '') {
														$tag_output = '<a href="mailto:' . str_replace('@', ' [at] ', $tag_attrib1) . '" onclick="this.href=this.href.replace(/(%20| )\\[at\\](%20| )/g, \'@\');">' . $tag_attrib1 . '</a>';
													} else {
														$tag_output = '<a href="mailto:' . str_replace('@', ' [at] ', $tag_attrib1) . '" onclick="this.href=this.href.replace(/(%20| )\\[at\\](%20| )/g, \'@\');">' . $tag_attrib2 . '</a>';
													}
												}
											}

										} else if ($tag_name_lower == 'img' && $this->config['allow_img_tags']) {

											$tag_valid = true;
											if ($tag_escaped != true) {
												if ($tag_attrib2 == '' && $tag_attrib3 == '') {
													$tag_output = '<img src="' . $tag_attrib1 . '" alt="" />';
												} else if ($tag_attrib3 == '') {
													$tag_output = '<img src="' . $tag_attrib1 . '" alt="' . $tag_attrib2 . '" />';
												} else {
													$tag_output = '<img src="' . $tag_attrib1 . '" alt="' . $tag_attrib2 . '" class="' . $tag_attrib3 . '" />';
												}
											}

										}
									}

							}

						//--------------------------------------------------
						// If the tag is invalid, or was escaped, return
						// its text value to the $output_html

							if ($tag_valid) {
								$escaping_stack = floor($escaping_stack / 2);
							}

							if ($tag_output === NULL) {
								$tag_output = '[' . $in_tag_text . ']'; // Invalid tag, or escaped tag
							}

							$output_html .= str_repeat('\\', $escaping_stack) . $tag_output;

						//--------------------------------------------------
						// Reset the tag tracking variables

							$in_tag_text = NULL;
							$escaping_stack = 0;

					} else {

						//--------------------------------------------------
						// The tag has not finished yet, so keep stacking
						// its contents up.

							$in_tag_text .= $char;

						//--------------------------------------------------
						// Detect if we are in an attribute

							$marker = substr($in_tag_text, -6);
							if ($marker == '&quot;') $marker = '"';
							if ($marker == '&#039;') $marker = "'";

							if ($in_tag_attribute === NULL) {
								if ($marker == '"' || $marker == "'") {
									$in_tag_attribute = $marker;
								}
							} else {
								if ($in_tag_attribute == $marker) {
									$in_tag_attribute = NULL;
								}
							}

					}

				} else if ($char == '\\') {

					//--------------------------------------------------
					// Add to the escaping string stack... these should
					// be added to the $output_html later

						$escaping_stack++;

				} else {

					//--------------------------------------------------
					// We are looking at an ordinary character which
					// is not part of a tag, so add the relevant number
					// of escaping characters which have been "ignored"
					// just incase we could hit a tag.

						if ($escaping_stack > 0) {
							$output_html .= str_repeat('\\', $escaping_stack);
							$escaping_stack = 0;
						}

					//--------------------------------------------------
					// Store the character into the output buffer,
					// although if a new line is being generated, use
					// a simple <br />... if its a double newline that
					// should have been handled by block_level_tag_close()

						if ($char == "\n") {
							if ($i > 0 && ($i + 1) < $string_length) { // Don't process the first and last "\n"
								$output_html .= "<br />\n";
							}
						} else {
							$output_html .= $char;
						}

				}

			}

		//--------------------------------------------------
		// If requested, remember the tags which were left
		// open (about to be closed) so they can be reopened
		// next time this function is called.

			$this->preserved_inline_tags = $tag_stack;

		//--------------------------------------------------
		// If there is any text remaining in the tag buffer,
		// dump it into $output_html

			if ($escaping_stack > 0) {
				$output_html .= str_repeat('\\', $escaping_stack);
			}

			if ($in_tag_text !== NULL) {
				$output_html .= '[' . $in_tag_text;
			}

			while ($tag = array_pop($tag_stack)) {
				$output_html .= $inline_tags[$tag]['close'];
			}

		//--------------------------------------------------
		// Post process - remove any tags which have no
		// effect on the output (surround white-space). Not
		// ness, but it keeps the output cleaner and stops
		// upsetting a few code checkers

			$re_run = 0;
			$re_run_max = count($inline_tags);
			while ($re_run++ < $re_run_max) {
				foreach ($inline_tags as $tag_info) {

					$reg_exp_open = preg_quote($tag_info['open'], '/');
					$reg_exp_close = preg_quote($tag_info['close'], '/');

					$output_html = preg_replace('/' . $reg_exp_open . '(\s*)' . $reg_exp_close . '/i', '$1', $output_html, -1);
					$output_html = preg_replace('/' . $reg_exp_close . '(\s*)' . $reg_exp_open . '/i', '$1', $output_html, -1);

				}
			}

		//--------------------------------------------------
		// Encode the whitespace characters so browsers
		// will render them

			$output_html = str_replace('  ', '&#xA0; ', $output_html); // 2 spaces
			$output_html = str_replace(chr(9), '&#xA0;&#xA0;&#xA0; ', $output_html);

		//--------------------------------------------------
		// Set the indent level - again, for code checkers

			$output_html = str_replace("\n", "\n" . str_repeat("\t", $this->indent_level), $output_html);

		//--------------------------------------------------
		// Return the output

			return $output_html;

	}

	public function process_block_html($string, $preserve_open_tags = false) {

		//--------------------------------------------------
		// Ensure were using use the proper UNIX "\n"

			$string = str_replace("\r\n", "\n", $string);

		//--------------------------------------------------
		// A new block, so we should not care about the
		// previous indent level

			$this->indent_level = 1;

		//--------------------------------------------------
		// Defaults

			$in_tag_text = NULL;
			$escaping_stack = 0;
			$current_block_tag = 'p';
			$output_buffer = '';
			$output_html = '';

			$block_level_tags = array();
			$block_level_tags[] = 'p';

			if ($this->config['allow_para_align']) {
				$block_level_tags[] = 'left';
				$block_level_tags[] = 'center';
				$block_level_tags[] = 'right';
			}

			if ($this->config['allow_heading_tags']) {
				$block_level_tags[] = 'h';
				$block_level_tags[] = 'h1';
				$block_level_tags[] = 'h2';
				$block_level_tags[] = 'h3';
				$block_level_tags[] = 'h4';
				$block_level_tags[] = 'h5';
				$block_level_tags[] = 'h6';
			}

			if ($this->config['allow_list_tags']) {
				$block_level_tags[] = 'list';
			}

			if ($this->config['allow_table_tags']) {
				$block_level_tags[] = 'table';
			}

			if ($this->config['allow_html_code']) {
				$block_level_tags[] = 'html';
			}

		//--------------------------------------------------
		// Loop though each of the characters in the string

			$string_length = strlen($string);
			for ($i = 0; $i < $string_length; $i++) {

				$char = $string{$i};

				if ($char == '[') {

					//--------------------------------------------------
					// If we are already collecting data for a tag, which
					// has not finished yet, then then its not really a
					// tag... the user has just used "["

						if ($in_tag_text !== NULL) {

							$tmp = str_repeat('\\', $escaping_stack) . '[' . $in_tag_text;

							$result = preg_match('/^(.*?)(\\\\*)$/', $tmp, $matches);
							if ($result) {
								$output_buffer .= $matches[1];
								$escaping_stack = strlen($matches[2]); // Slashes before the new "["
							} else {
								$output_buffer .= $tmp;
								$escaping_stack = 0;
							}

						}

					//--------------------------------------------------
					// Remember we are now in tag mode (not NULL)

						$in_tag_text = '';

				} else if ($in_tag_text !== NULL) {

					if ($char == ']') {

						//--------------------------------------------------
						// Extract the tag parts

							$result = preg_match('/^(\/?)([^ ]*)$/', $in_tag_text, $matches);
							if ($result) {
								$tag_type_opener = ($matches[1] == '');
								$tag_name_lower = strtolower($matches[2]);
							} else {
								$tag_type_opener = false;
								$tag_name_lower = '';
							}

						//--------------------------------------------------
						// Default processing variables

							$tag_output = NULL;
							$tag_ignored = false;

							if (in_array($tag_name_lower, $block_level_tags)) { // If this is a valid tag
								if (($escaping_stack % 2) == 0) { // Tag not escaped, with an odd number of slashes
									if ($tag_type_opener == true) {

										if ($tag_name_lower != $current_block_tag) {
											$tag_output .= $this->block_level_tag_close($current_block_tag, $output_buffer, true);
											$current_block_tag = $tag_name_lower;
										} else {
											$tag_ignored = true; // Already open
										}

									} else {

										if ($tag_name_lower == $current_block_tag) {
											$tag_output = $this->block_level_tag_close($current_block_tag, $output_buffer, true);
											$current_block_tag = 'p'; // Return to default
										} else {
											$tag_ignored = true; // Already closed
										}

									}
								}
								$escaping_stack = floor($escaping_stack / 2);
							}

						//--------------------------------------------------
						// If tag output was generated add it to output_html,
						// otherwise dump the (invalid) tag back in to the
						// output_buffer

							if ($tag_output === NULL) {
								$output_buffer .= str_repeat('\\', $escaping_stack) . ($tag_ignored ? '' : '[' . $in_tag_text . ']'); // Invalid tag, or escaped tag
							} else {
								$output_html .= str_repeat('\\', $escaping_stack) . $tag_output;
								$output_buffer = '';
							}

						//--------------------------------------------------
						// Reset the tag tracking variables

							$in_tag_text = NULL;
							$escaping_stack = 0;

					} else {

						//--------------------------------------------------
						// The tag has not finished yet, so keep stacking
						// its contents up.

							$in_tag_text .= $char;

					}

				} else if ($char == '\\') {

					//--------------------------------------------------
					// Add to the escaping string stack... these should
					// be added to the $output_buffer later

						$escaping_stack++;

				} else {

					//--------------------------------------------------
					// We are looking at an ordinary character which
					// is not part of a tag, so add the relevant number
					// of escaping characters which have been "ignored"
					// just incase we could hit a tag.

						if ($escaping_stack > 0) {
							$output_buffer .= str_repeat('\\', $escaping_stack);
							$escaping_stack = 0;
						}

					//--------------------------------------------------
					// Store the character into the output buffer.

						$output_buffer .= $char;

				}

			}

		//--------------------------------------------------
		// If there is any text remaining in the tag buffer,
		// dump it into $output_html

			if ($escaping_stack > 0) {
				$output_buffer .= str_repeat('\\', $escaping_stack);
			}

			if ($in_tag_text !== NULL) {
				$output_buffer .= '[' . $in_tag_text;
			}

			$output_html .= $this->block_level_tag_close($current_block_tag, $output_buffer, $preserve_open_tags); // By default, tags are not preserved

		//--------------------------------------------------
		// Return the output

			if ($this->config['hide_cms_comments']) {
				return $output_html;
			} else {
				return "\n<!-- CMS TEXT START -->" . $output_html . "\n<!-- CMS TEXT END -->\n";
			}

	}

	private function block_level_tag_close($current_block_tag, $content, $preserve_open_tags = false) {

		//--------------------------------------------------
		// If the tag is empty, there is no point generating
		// any html output

			if (trim($content) == '') {
				return '';
			}

		//--------------------------------------------------
		// Process the relevant tags

			if ($current_block_tag == 'p' || $current_block_tag == 'left' || $current_block_tag == 'center' || $current_block_tag == 'right') {

				//--------------------------------------------------
				// Determine text alignment (none by default)

					if ($current_block_tag == 'p') {
						$align = NULL;
					} else {
						$align = $current_block_tag;
					}

				//--------------------------------------------------
				// If double newlines are used, split into
				// multiple paragraphs

					$html_output = '';
					$sub_paras = explode("\n\n", $content);
					$sub_paras_length = count($sub_paras);

					$k = 1;
					foreach ($sub_paras as $sub_para) {
						if (trim($sub_para) != '') {
							$html_output .= "\n" . $this->process_paragraph($sub_para, ($k == 1 ? $preserve_open_tags : true), $align);
						}
						$k++;
					}

				//--------------------------------------------------
				// Return the output

					return $html_output;

			} else if (preg_match('/^h([1-6])?$/', $current_block_tag, $matches)) {

				$level = intval($this->config['heading_level']);

				if (isset($matches[1])) {
					$level += (intval($matches[1]) - 1);
				}

				if ($level < 1) $level = 1;
				if ($level > 6) $level = 6;

				return "\n" . $this->process_heading($content, $preserve_open_tags, $level);

			} else if ($current_block_tag == 'list') {

				return "\n" . $this->process_list($content, $preserve_open_tags);

			} else if ($current_block_tag == 'table') {

				return "\n" . $this->process_table($content, $preserve_open_tags);

			} else if ($current_block_tag == 'html') {

				if ($this->config['hide_cms_comments']) {
					return $content;
				} else {
					return "\n<!-- CMS TEXT END -->\n" . $content . "\n<!-- CMS TEXT START -->";
				}

			}

	}

	private function process_paragraph($content, $preserve_open_tags, $align = NULL) {

		//--------------------------------------------------
		// Process the content, but have it correctly
		// indented if its multi-lined

			$item_html = $this->process_inline_html($content, $preserve_open_tags);
			if (strpos($item_html, "\n") !== false) {
				$item_html = "\n\t" . $item_html . "\n";
			}

		//--------------------------------------------------
		// Return the output in its wrapper

			return '<p' . ($align === NULL ? '' : ' style="text-align: ' . html($align) . ';"') . '>' . $item_html . '</p>';

	}

	private function process_heading($content, $preserve_open_tags, $level) {

		//--------------------------------------------------
		// Process the content, but have it correctly
		// indented if its multi-lined

			$item_html = $this->process_inline_html($content, $preserve_open_tags);
			if (strpos($item_html, "\n") !== false) {
				$item_html = "\n\t" . $item_html . "\n";
			}

		//--------------------------------------------------
		// Return the output in its wrapper

			return '<h' . $level . '>' . $item_html . '</h' . $level . '>';

	}

	private function process_list($content, $preserve_open_tags) {

		//--------------------------------------------------
		// Increase the indent level

			$this->indent_level++;

		//--------------------------------------------------
		// Process each item in the list

			$output_html = '';
			$items = preg_split('/\n(\*|#)/', $content);

			foreach ($items as $item) {

				$item = trim($item);
				if ($item != '') {
					$item_html = $this->process_inline_html($item, $preserve_open_tags);
					if (strpos($item_html, "\n") !== false) {
						$item_html = "\n\t\t" . $item_html . "\n\t";
					}
					$output_html .= "\n\t" . '<li>' . $item_html . '</li>';
				}

			}

		//--------------------------------------------------
		// Restore the indent level

			$this->indent_level--;

		//--------------------------------------------------
		// Return the output in its wrapper

			if (preg_match('/^\s*#/', $content)) {
				return '<ol>' . $output_html . "\n" . '</ol>';
			} else {
				return '<ul>' . $output_html . "\n" . '</ul>';
			}

	}

	private function process_table($content, $preserve_open_tags) {

		//--------------------------------------------------
		// Split the data into a multi-dimensional array

			$table_columns = 1;
			$table_data = array();
			$table_row_cells = array();
			$k = 0;

			$rows = explode("\n", $content);
			foreach ($rows as $row) {
				if ($row != '') {

					$cells = explode('|', $row);
					$table_data[$k] = $cells;

					$table_row_cells[$k] = count($cells);
					if ($table_row_cells[$k] > $table_columns) {
						$table_columns = $table_row_cells[$k];
					}

					$k++;

				}
			}

		//--------------------------------------------------
		// Build the html output

			$k = 0;
			$in_head = true;
			$output_head_html = '';
			$output_body_html = '';

			foreach ($table_data as $cells) {

				$row_html = "\n\t\t<tr>";

				$i = 0;
				foreach ($cells as $cell_data) {

					$i++;

					if ($i == 1 && $in_head) {
						if (substr($cell_data, 0, 1) == '#') {
							$cell_data = substr($cell_data, 1);
						} else {
							$in_head = false;
						}
					}

					$col_span = (($table_columns + 1) - $table_row_cells[$k]);

					$row_html .= "\n\t\t\t" . '<' . ($in_head ? 'th' : 'td') . ($col_span > 1 && $i == $table_row_cells[$k] ? ' colspan="' . intval($col_span) . '"' : '') . '>' . $this->process_inline_html($cell_data, $preserve_open_tags) . ($in_head ? '</th>' : '</td>');

				}

				$row_html .= "\n\t\t</tr>";

				if ($in_head) {
					$output_head_html .= $row_html;
				} else {
					$output_body_html .= $row_html;
				}

				$k++;

			}

		//--------------------------------------------------
		// Return the output in its wrapper

			return "<table border=\"1\" cellpadding=\"0\" cellspacing=\"0\">\n" . ($output_head_html == '' ? '' : "\t<thead>" . $output_head_html . "\n\t</thead>\n") . ($output_body_html == '' ? '' : "\t<tbody>" . $output_body_html . "\n\t</tbody>\n") . "</table>";

	}

}

//--------------------------------------------------
// Copyright (c) 2006, Craig Francis All rights
// reserved.
//
// Redistribution and use in source and binary forms,
// with or without modification, are permitted provided
// that the following conditions are met:
//
//  * Redistributions of source code must retain the
//    above copyright notice, this list of
//    conditions and the following disclaimer.
//  * Redistributions in binary form must reproduce
//    the above copyright notice, this list of
//    conditions and the following disclaimer in the
//    documentation and/or other materials provided
//    with the distribution.
//  * Neither the name of the author nor the names
//    of its contributors may be used to endorse or
//    promote products derived from this software
//    without specific prior written permission.
//
// This software is provided by the copyright holders
// and contributors "as is" and any express or implied
// warranties, including, but not limited to, the
// implied warranties of merchantability and fitness
// for a particular purpose are disclaimed. In no event
// shall the copyright owner or contributors be liable
// for any direct, indirect, incidental, special,
// exemplary, or consequential damages (including, but
// not limited to, procurement of substitute goods or
// services; loss of use, data, or profits; or business
// interruption) however caused and on any theory of
// liability, whether in contract, strict liability, or
// tort (including negligence or otherwise) arising in
// any way out of the use of this software, even if
// advised of the possibility of such damage.
//--------------------------------------------------

?>