
	<h1>Browser</h1>

	<?php if (!isset($form)) { ?>

		<p>Example disabled on this server.</p>

	<?php } else { ?>

		<p>Behind the scenes this form will load Googles homepage, enter the query on the search form, submit, and attempt to follow the first link in the results.</p>

		<p>It does this with the "socket_browser" helper, an object that will attempt to emulate a basic browser - where it remembers cookies set, follows links, submits forms, and returns the HTML or specific elements. It does not attempt anything like CSS rendering or JavaScript.</p>

		<?= $form->html(); ?>

	<?php } ?>
