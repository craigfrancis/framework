
	<h2>AWS S3 Upload</h2>

	<p>Files uploaded here will be encrypted and sent to AWS S3.</p>

	<?php if (isset($links)) { ?>

		<ul>
			<?php foreach ($links as $link => $url) { ?>
				<li><a href="<?= html($url) ?>"><?= html(ucfirst($link)) ?></a></li>
			<?php } ?>
		</ul>

	<?php } else { ?>

		<?= $form->html(); ?>

	<?php } ?>
