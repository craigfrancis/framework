<?php

//--------------------------------------------------
// Get controller

	$route_folders = route_folders(config::get('route.path'));

	list($folders, $controller, $method, $arguments) = controller_get($route_folders);

//--------------------------------------------------
// Action

	if ($method !== NULL) {

		if ($method != 'action_index') {
			array_push($folders, array_shift($arguments));
		}

		if (config::get('debug.level') >= 3) {

			$controller_name = get_class($controller);
			if ($controller_name != 'controller_index') {
				$log = [];
				$log[] = [['span', $controller_name . '->'], ['strong', 'before'], ['span', '();']];
				$log[] = [['span', $controller_name . '->'], ['strong', $method], ['span', '(' . implode(', ', $arguments) . ');']];
				$log[] = [['span', $controller_name . '->'], ['strong', 'after'], ['span', '();']];
			} else {
				$log = NULL;
			}

			debug_note([
					'type' => 'H',
					'heading' => 'Action',
					'heading_extra' => str_replace(ROOT, '', $controller->path),
					'lines' => $log,
				]);

			unset($controller_name, $log);

		}

		config::set('output.folders', $folders);

		$controller->before();

		call_user_func_array(array($controller, $method), $arguments);

		$controller->after();

	} else {

		if (config::get('debug.level') >= 3) {

			debug_note([
					'type' => 'H',
					'heading' => 'Action',
					'heading_extra' => 'Missing',
				]);

		}

		config::set('output.folders', $route_folders);

	}

	if (config::get('debug.level') >= 3) {

		$log = [];
		$log[] = [['span', '$response = response_get();']];
		$log[] = [['span', '$response->'], ['strong', 'template_set'], ['span', '(\'default\');']];
		$log[] = [['span', '$response->'], ['strong', 'view_path_set'], ['span', '(VIEW_ROOT . \'/file.ctp\');']];
		$log[] = [['span', '$response->'], ['strong', 'page_id_set'], ['span', '(\'example_ref\');']];
		$log[] = [['span', '$response->'], ['strong', 'title_set'], ['span', '(\'Custom page title.\');']];
		$log[] = [['span', '$response->'], ['strong', 'title_full_set'], ['span', '(\'Custom page title.\');']];

		foreach (config::get('output.title_folders') as $id => $value) {
			$log[] = [['span', '$response->'], ['strong', 'title_folder_set'], ['span', '(' . $id . ', \'new_value\');'], ['span', ' // ' . $value, 'comment']];
		}

		$log[] = [['span', '$response->'], ['strong', 'description_set'],   ['span', '(\'Page description.\');']];
		$log[] = [['span', '$response->'], ['strong', 'meta_set'],          ['span', '(\'name\', \'content\');']];
		$log[] = [['span', '$response->'], ['strong', 'link_set'],          ['span', '(\'rel\', \'href\');']];
		$log[] = [['span', '$response->'], ['strong', 'csp_source_add'],    ['span', '(\'frame-src\', \'https://www.example.com\');']];
		$log[] = [['span', '$response->'], ['strong', 'js_add'],            ['span', '(\'/path/to/file.js\');']];
		$log[] = [['span', '$response->'], ['strong', 'css_auto'],          ['span', '();']];
		$log[] = [['span', '$response->'], ['strong', 'css_add'],           ['span', '(\'/path/to/file.css\');']];
		$log[] = [['span', '$response->'], ['strong', 'css_alternate_add'], ['span', '(\'/path/to/file.css\', \'print\');']];
		$log[] = [['span', '$response->'], ['strong', 'css_alternate_add'], ['span', '(\'/path/to/file.css\', \'all\', \'Title\');']];
		$log[] = [['span', '$response->'], ['strong', 'head_add_html'],     ['span', '(\'<html>\');']];
		$log[] = [['span', '$response->'], ['strong', 'mime_set'],          ['span', '(\'text/plain\');']];

		$log[] = [['strong', 'error_send'], ['span', '(\'page-not-found\');']];
		$log[] = [['strong', 'message_set'], ['span', '(\'The item has been updated.\');']];

		$request_folders = config::get('request.folders');
		foreach ($request_folders as $id => $value) {
			$log[] = [['strong', 'request_folder_get'], ['span', '(' . $id . ');'], ['span', ' // ' . $value, 'comment']];
		}
		if (count($request_folders) == 0) {
			$log[] = [['strong', 'request_folder_get'], ['span', '(0);'], ['span', ' // NULL', 'comment']];
		}

		debug_note([
				'type' => 'H',
				'heading' => 'Methods',
				'lines' => $log,
			]);

		unset($log, $id, $value, $request_folders);

	}

	unset($controller, $method, $folders, $arguments, $route_folders, $include_path);

?>