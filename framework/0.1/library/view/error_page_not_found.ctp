
	<h1>Page not found</h1>
	<p>Unfortunately the page you have requested cannot be found.</p>

	<?php if (SERVER == 'stage') { ?>

		<p><?= html(config::get('view.path')) ?></p>

	<?php } ?>
