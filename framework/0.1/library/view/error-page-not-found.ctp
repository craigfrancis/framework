
	<h1>Page not found</h1>
	<p>Unfortunately the page you have requested cannot be found.</p>

	<?php if (config::get('debug.level') > 0) { ?>

		<p><?= html(str_replace(ROOT, '', config::get('view.path'))) ?></p>

	<?php } ?>
