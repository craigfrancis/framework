<?php

	$examples = array(
		'human' => 'Example Text',
		'ref' => 'example_text',
		'link' => 'example-text',
		'camel' => 'exampleText',
	);

?>

	<h1>Conversion functions</h1>
	<p>Below is the matrix for the different <a href="/doc/helpers/functions/">conversion functions</a>.</p>

	<table cellspacing="0" cellpadding="1" border="1" class="basic_table">
		<thead>
			<tr>

				<th scope="col">&#xA0;</th>
				<th scope="col">&#xA0;</th>

				<?php foreach ($examples as $type => $example) { ?>
					<th scope="col"><?= html(ucfirst($type)) ?></th>
				<?php } ?>

			</tr>
		</thead>
		<tbody>

			<?php foreach ($examples as $from_type => $example) { ?>

				<tr>

					<th scope="row"><?= html(ucfirst($from_type)) ?></th>
					<td><?= html($example) ?></td>

					<?php foreach (array_keys($examples) as $to_type) { ?>
						<?php if ($from_type == $to_type) { ?>

							<td>-</td>

						<?php } else { ?>

							<td><?= html(call_user_func($from_type . '_to_' . $to_type, $example)) ?></td>

						<?php } ?>
					<?php } ?>

				</tr>

			<?php } ?>

		</tbody>
	</table>
