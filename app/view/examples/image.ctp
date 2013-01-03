
	<h1>Image helper</h1>
	<p>Below is a list of examples for the <a href="/doc/helpers/image/">image helper</a>.</p>

	<?php if (isset($testing_url)) { ?>

		<p><a href="<?= html($testing_url) ?>">Testing</a>.</p>

	<?php } ?>

	<p><a href="<?= html(gateway_url('image-export')) ?>">Download</a> stand alone version of image class.</p>
