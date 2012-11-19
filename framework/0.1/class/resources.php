<?php

	class resources_base extends check {

		private $head_html = '';
		private $js_files = array();
		private $js_code_ref = NULL;
		private $js_code_data = '';
		private $js_code_mode = NULL;
		private $js_code_saved = false;
		private $css_files_main = array();
		private $css_files_alternate = array();

		public static function head_add_html($html) {
			$obj = resources::instance_get();
			$obj->head_html .= $html;
		}

		public static function head_get_html() {
			$obj = resources::instance_get();
			return $obj->head_html;
		}

		public static function js_add($path, $attributes = array()) { // Could be resources::js_add('/path.js', 'defer');
			if (is_string($attributes)) {
				$attributes = array($attributes);
			}
			$obj = resources::instance_get();
			$obj->js_files[] = array(
					'path' => $path,
					'attributes' => $attributes,
				);
		}

		public static function js_code_add($code, $mode = 'inline') {

			$obj = resources::instance_get();

			if ($obj->js_code_ref === NULL) {

				$obj->js_code_ref = time() . '-' . mt_rand(1000000, 9999999);

				session::start();

			}

			if ($obj->js_code_saved) {
				resources::js_code_save($code);
			} else {
				$obj->js_code_data .= $code;
			}

			if ($mode == 'inline') {

				$obj->js_code_mode = $mode;

			} else if ($mode == 'defer') {

				if ($obj->js_code_mode === NULL || $obj->js_code_mode == 'async') {
					$obj->js_code_mode = $mode;
				}

			} else if ($mode == 'async') {

				if ($obj->js_code_mode === NULL) {
					$obj->js_code_mode = $mode;
				}

			} else {

				exit_with_error('Unrecognised js code mode (inline/defer/async)');

			}

		}

		public static function css_add($path, $media = 'all') {
			$obj = resources::instance_get();
			$obj->css_files_main[] = array(
					'path' => $path,
					'media' => $media,
				);
		}

		public static function css_alternate_add($path, $media, $title) {
			$obj = resources::instance_get();
			$obj->css_files_alternate[] = array(
					'path' => $path,
					'media' => $media,
					'title' => $title,
				);
		}

		public static function css_auto() {

			//--------------------------------------------------
			// Get config

				$css_name = config::get('output.css_name');
				$css_types = config::get('output.css_types');

			//--------------------------------------------------
			// CSS name

				$style_set = false;

				if ($css_name == '') {

					$css_name = request('style', 'GET');

					if (isset($css_types[$css_name])) {

						cookie::set('style', $css_name);

						$style_set = true;

					} else if ($css_name != '') {

						cookie::delete('style');

						$css_name = '';

					} else {

						$css_name = cookie::get('style');

					}

				}

				if (!isset($css_types[$css_name]) || (!$style_set && !$css_types[$css_name]['alt_sticky'])) {
					$css_name = '';
				}

			//--------------------------------------------------
			// Files

				foreach ($css_types as $css_type_name => $css_type_info) {

					$css_types[$css_type_name]['files'] = array();
					$css_types[$css_type_name]['log'] = array();

					$file = '/css/global/' . $css_type_name . '.css';

					if (is_file(ASSET_ROOT . $file)) {

						$css_types[$css_type_name]['files'][] = ASSET_URL . $file;
						$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - found';

					} else {

						$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - absent';

					}

				}

				$build_up_address = '/css/';

				$route_array = path_to_array(config::get('route.path'));
				if (count($route_array) == 0) {
					$route_array[] = 'home';
				}

				foreach ($route_array as $f) {
					if ($f != '') {

						$build_up_address .= $f . '/';

						foreach ($css_types as $css_type_name => $css_type_info) {
							$file = $build_up_address . $css_type_name . '.css';

							if (is_file(ASSET_ROOT . $file)) {

								$css_types[$css_type_name]['files'][] = ASSET_URL . $file;
								$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - found';

							} else {

								$css_types[$css_type_name]['log'][] = ASSET_ROOT . $file . ' - absent';

							}

						}

					}
				}

			//--------------------------------------------------
			// Debug

				if (config::get('debug.level') >= 3) {

					$note_html = '<strong>Styles</strong>:<br />';

					foreach ($css_types as $css_type_name => $css_type_info) {
						foreach ($css_type_info['log'] as $log) {
							$note_html .= "\n" . '&#xA0; ' . str_replace(' - found', ' - <strong>found</strong>', html($log)) . '<br />';
						}
					}

					debug_note_html(str_replace(ROOT, '', $note_html), 'H');

					unset($note_html, $log);

				}

			//--------------------------------------------------
			// Add to config

				foreach ($css_types as $css_type_name => $css_type_info) {

					if ($css_type_info['default'] == true || $css_name == $css_type_name) {
						foreach ($css_type_info['files'] as $path) {

							$media = ($css_name == $css_type_name ? $css_type_info['media_selected'] : $css_type_info['media_normal']);

							resources::css_add($path, $media);

						}
					}

					if ($css_type_info['alt_title'] != '' && $css_name != $css_type_name) {
						foreach ($css_type_info['files'] as $path) {

							resources::css_alternate_add($path, 'all', $css_type_info['alt_title']);

						}
					}

				}

		}

		public static function get($type) {

			$obj = resources::instance_get();

			if ($type == 'js') {
				$files = $obj->js_files;
			} else if ($type == 'css') {
				$files = $obj->css_files_main;
			} else if ($type == 'css_alternate') {
				$files = $obj->css_files_alternate;
			} else {
				exit_with_error('Unrecognised path type "' . $type . '"');
			}

			$version = config::get('output.version', true);

			if ($type == 'js' && config::get('output.js_combine')) {

				$grouped_files = array(); // Local files that can be grouped

				foreach ($files as $id => $file) {
					if (substr($file['path'], 0, 1) == '/' && substr($file['path'], -3) == '.js' && count($file['attributes']) == 0 && is_file(PUBLIC_ROOT . $file['path'])) {
						$grouped_files[$id] = $file['path'];
					}
				}

				if (count($grouped_files) > 0) {

					$prefix = reset($grouped_files);
					$length = strlen($prefix);

					foreach ($grouped_files as $path) { // @Gumbo - http://stackoverflow.com/questions/1336207/finding-common-prefix-of-array-of-strings
						while ($length && substr($path, 0, $length) !== $prefix) {
							$length--;
							$prefix = substr($prefix, 0, -1);
						}
						if (!$length) break;
					}

					if ($length > 0 && substr($prefix, -1) == '/') {

						if ($version) {
							$last_modified = 0;
							foreach ($grouped_files as $path) {
								$file_modified = filemtime(PUBLIC_ROOT . $path);
								if ($last_modified < $file_modified) {
									$last_modified = $file_modified;
								}
							}
							$last_modified .= '-';
						} else {
							$last_modified = '';
						}

						$paths = array();
						foreach ($grouped_files as $id => $path) {
							unset($files[$id]);
							$paths[] = substr($path, $length, -3);
						}

						$files[] = array(
								'path' => $prefix . $last_modified . '{' . implode(',', array_unique($paths)) . '}.js',
								'attributes' => array(),
							);

					}

				}

			}

			if ($version) {
				foreach ($files as $id => $file) {
					if (substr($file['path'], 0, 1) == '/' && is_file(PUBLIC_ROOT . $file['path'])) {
						$files[$id]['path'] = dirname($file['path']) . '/' . filemtime(PUBLIC_ROOT . $file['path']) . '-' . basename($file['path']);
					}
				}
			}

			if ($type == 'js' && $obj->js_code_data != '') {

				$obj->js_code_saved = true;

				resources::js_code_save($obj->js_code_data);

				$files[] = array(
						'path' => gateway_url('js-code', array('ref' => $obj->js_code_ref)),
						'attributes' => ($obj->js_code_mode == 'inline' ? array() : array($obj->js_code_mode)),
					);

			}

			return $files;

		}

		public static function js_code_save($code) { // Don't call directly, use js_code_add()

			$obj = resources::instance_get();

			$session_js = session::get('output.js_code');

			if (!isset($session_js[$obj->js_code_ref])) {
				$session_js[$obj->js_code_ref] = '';
			}

			$session_js[$obj->js_code_ref] .= $code;

			session::set('output.js_code', $session_js);

		}

		private static function instance_get() {
			static $instance = NULL;
			if (!$instance) {
				$instance = new resources();
			}
			return $instance;
		}

		final private function __construct() {
			// Being private prevents direct creation of object.
		}

		final private function __clone() {
			trigger_error('Clone of resources object is not allowed.', E_USER_ERROR);
		}

	}

?>