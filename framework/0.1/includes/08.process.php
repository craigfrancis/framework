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

			$note_html  = '<strong>Action</strong>: ' . html(str_replace(ROOT, '', $controller->path)) . '<br />' . "\n";

			$controller_name = get_class($controller);
			if ($controller_name != 'controller_index') {
				$note_html .= '&#xA0; ' . html($controller_name) . '-&gt;<strong>before</strong>();<br />' . "\n";
				$note_html .= '&#xA0; ' . html($controller_name) . '-&gt;<strong>' . html($method) . '</strong>(' . html(implode(', ', $arguments)) . ');<br />' . "\n";
				$note_html .= '&#xA0; ' . html($controller_name) . '-&gt;<strong>after</strong>();<br />' . "\n";
			}

			debug_note_html($note_html, 'H');

			unset($note_html, $controller_name);

		}

		config::set('output.folders', $folders);

		$controller->before();

		call_user_func_array(array($controller, $method), $arguments);

		$controller->after();

	} else {

		if (config::get('debug.level') >= 3) {
			debug_note_html('<strong>Action</strong>: Missing', 'H');
		}

		config::set('output.folders', $route_folders);

	}

	if (config::get('debug.level') >= 3) {

		$note_html = '<strong>Methods</strong>:<br />' . "\n";

		$note_html .= '&#xA0; $response = response_get();<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>template_set</strong>(\'default\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>view_path_set</strong>(VIEW_ROOT . \'/file.ctp\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>page_id_set</strong>(\'example_ref\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>title_set</strong>(\'Custom page title.\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>title_full_set</strong>(\'Custom page title.\');<br />' . "\n";

		foreach (config::get('output.title_folders') as $id => $value) {
			$note_html .= '&#xA0; $response-&gt;<strong>title_folder_set</strong>(' . html($id) . ', \'new_value\'); <span class="comment">// ' . html($value) . '</span><br />' . "\n";
		}

		$note_html .= '&#xA0; $response-&gt;<strong>description_set</strong>(\'Page description.\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>meta_set</strong>(\'name\', \'content\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>link_set</strong>(\'rel\', \'href\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>csp_source_add</strong>(\'frame-src\', \'https://www.example.com\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>js_add</strong>(\'/path/to/file.js\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>css_auto</strong>();<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>css_add</strong>(\'/path/to/file.css\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>css_alternate_add</strong>(\'/path/to/file.css\', \'print\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>css_alternate_add</strong>(\'/path/to/file.css\', \'all\', \'Title\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>head_add_html</strong>(\'&lt;html&gt;\');<br />' . "\n";
		$note_html .= '&#xA0; $response-&gt;<strong>mime_set</strong>(\'text/plain\');<br />' . "\n";

		$note_html .= '&#xA0; <strong>error_send</strong>(\'page-not-found\');<br />' . "\n";
		$note_html .= '&#xA0; <strong>message_set</strong>(\'The item has been updated.\');<br />' . "\n";

		$request_folders = config::get('request.folders');
		foreach ($request_folders as $id => $value) {
			$note_html .= '&#xA0; <strong>request_folder_get</strong>(' . html($id) . '); <span class="comment">// ' . html($value) . '</span><br />' . "\n";
		}
		if (count($request_folders) == 0) {
			$note_html .= '&#xA0; <strong>request_folder_get</strong>(0); <span class="comment">// NULL</span><br />' . "\n";
		}

		debug_note_html($note_html, 'H');

		unset($note_html, $id, $value, $request_folders);

	}

	unset($controller, $method, $folders, $arguments, $route_folders, $include_path);

?>