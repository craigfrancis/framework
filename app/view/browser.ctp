
	<?php if (!isset($form)) { ?>

		<p>Example disabled on this server.</p>

	<?php } else { ?>

		<p>Behind the scenes this form will load Googles homepage, enter the query on the search form, submit, and attempt to follow the first link in the results.</p>

		<?= $form->html(); ?>

	<?php } ?>
