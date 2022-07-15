<?php
	$response->set('title_html', '<h1>Page not found</h1>');
?>

	<p>Unfortunately the page you have requested cannot be found.</p>

	<?php

		if (config::get('debug.level') > 0) {
			$response = response_get();
			echo '<p>' . html(str_replace(ROOT, '', strval($response->view_path_get()))) . '</p>';
		}

	?>
