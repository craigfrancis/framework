
	<?php if (isset($examples)) { ?>

		<h2>Encryption helper</h2>

		<p>For most projects you should allow the framework to store and manage your keys:</p>

		<ul>
			<?php foreach ($examples['named'] as $example) { ?>
				<li><a href="<?= html($example['url']) ?>"><?= html($example['name']) ?></a></li>
			<?php } ?>
		</ul>

		<p>If you want to manage the keys yourself:</p>

		<ul>
			<?php foreach ($examples['unnamed'] as $example) { ?>
				<li><a href="<?= html($example['url']) ?>"><?= html($example['name']) ?></a></li>
			<?php } ?>
		</ul>

	<?php } else if (isset($results)) { ?>

		<h2>Encryption helper</h2>

		<?php foreach ($results as $ref => $result) { ?>
			<h3><?= html($ref) ?></h3>
			<p class="example"><?= html($result) ?></p>
		<?php } ?>

	<?php } else { ?>

		<h2>Encryption helper</h2>

		<p><?= html(ucfirst($example_type)) ?> example: <?= html($example_name) ?></p>

		<?php if (isset($example_text)) { ?>
			<p><?= html($example_text) ?></p>
		<?php } ?>

		<p class="example"><?= html($example_content) ?></p>

		<?php if (isset($example_output)) { ?>
			<p class="example"><?= html($example_output) ?></p>
		<?php } ?>

	<?php } ?>

	<?php if (isset($version_url_1)) { ?>
		<p><a href="<?= html($index_url) ?>">Back</a> | <a href="<?= html($version_url_1) ?>">Switch to OpenSSL</a></p>
	<?php } else if (isset($version_url_2)) { ?>
		<p><a href="<?= html($index_url) ?>">Back</a> | <a href="<?= html($version_url_2) ?>">Switch to LibSodium</a></p>
	<?php } else if (isset($index_url)) { ?>
		<p><a href="<?= html($index_url) ?>">Back</a></p>
	<?php } else if (isset($all_url)) { ?>
		<p><a href="<?= html($all_url) ?>">View all</a></p>
	<?php } ?>
