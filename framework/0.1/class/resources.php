<?php

	class resources extends check {

		private $head_html = '';
		private $js_files = array();
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

		public static function js_add($path) {
			$obj = resources::instance_get();
			$obj->js_files[] = array(
					'path' => $path,
				);
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

					if ($css_name != '' && isset($css_types[$css_name])) {

						cookie::set('style', $css_name); // TODO: Cannot be set after output sent

						$style_set = true;

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
				foreach (path_to_array(config::get('route.path')) as $f) {
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
							$note_html .= '&#xA0; ' . str_replace(' - found', ' - <strong>found</strong>', html($log)) . '<br />';
						}
					}

					debug_note_html($note_html);

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

			if ($version) {
				foreach ($files as $id => $file) {
					if (substr($file['path'], 0, 1) == '/' && is_file(PUBLIC_ROOT . $file['path'])) {
						$files[$id]['path'] = dirname($file['path']) . '/' . filemtime(PUBLIC_ROOT . $file['path']) . '-' . basename($file['path']);
					}
				}
			}

			return $files;

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