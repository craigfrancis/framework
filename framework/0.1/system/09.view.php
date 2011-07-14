<?php

//--------------------------------------------------
// View

	$view = new view();
	$view->render();

	unset($view);

	if (config::get('debug.level') >= 4) {
		debug_progress('View render', 1);
	}

//--------------------------------------------------
// Layout

	$layout = new layout();
	$layout->render();

	unset($layout);

	if (config::get('debug.level') >= 4) {
		debug_progress('Layout render', 1);
	}

?>