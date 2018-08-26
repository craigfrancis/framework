
	<h1>Browser</h1>

	<?php if (!isset($form)) { ?>

		<p>Example disabled on this server.</p>

	<?php } else { ?>

		<p>This form will load the Google homepage, enter the search query, submit, and follow the first link in the results.</p>

		<p>It does this with the <a href="/doc/helpers/socket/">socket_browser</a> helper, an object that emulates a basic browser - where it remembers cookies set, follows links, submits forms, and returns the HTML or specific elements. It does not attempt anything like CSS rendering or JavaScript.</p>

		<?= $form->html(); ?>

	<?php } ?>
