<?php

//--------------------------------------------------
// Only on stage

	if (SERVER != 'stage') {
		exit('Disabled');
	}

//--------------------------------------------------
// Unit name

	$unit_name = human_to_ref($this->sub_path_get());

//--------------------------------------------------
// Initialise object

	ob_start();

	if ($unit_name != '') {
		$unit_object = unit_get($unit_name, $_GET);
	} else {
		$unit_object = NULL;
	}

//--------------------------------------------------
// Response

	$response = response_get('html');
	$response->setup_output_set(ob_get_clean());

	if ($unit_object) {

		//--------------------------------------------------
		// Have a unit to show

			$response->title_set('Unit: ' . $unit_name);
			$response->unit_add($unit_object);

	} else {

		//--------------------------------------------------
		// List of units

			$units = array();
			$root = APP_ROOT . '/unit';
			foreach (array_merge(glob($root . '/*.php'), glob($root . '/*/*.php')) as $unit) {
				$units[] = substr($unit, (strrpos($unit, '/') + 1), -4);
			}
			sort($units);

		//--------------------------------------------------
		// List

			$html = '
				<h2>Units</h2>
				<ul>';

			foreach ($units as $unit) {
				$html .= '
						<li><a href="' . html(gateway_url('unit-test', $unit)) . '">' . html($unit) . '</a></li>';
			}

			$html .= '
				</ul>';

		//--------------------------------------------------
		// Add to response

			$response->title_set('Units');
			$response->view_add_html($html);

	}

	$response->send();

?>