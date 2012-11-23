<?php

//--------------------------------------------------
// Config

	$config = array();

	foreach (array('width', 'width_min', 'width_max', 'height', 'height_min', 'height_max') as $key) {
		$val = intval(request($key));
		if ($val > 0 && $val <= 300) {
			$config[$key] = $val;
		}
	}

	foreach (array('stretch', 'crop', 'grow') as $key) {
		$val = request($key);
		if ($val !== NULL) {
			$config[$key] = ($val == 'true');
		}
	}

//--------------------------------------------------
// Process image

	$image = new image(PUBLIC_ROOT . '/a/img/test/treeLandscape.jpg');
	$image->resize($config);
	$image->output_jpg();
	exit();

?>