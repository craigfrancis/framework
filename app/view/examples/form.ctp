
	<h1>Form helper</h1>
	<p>Below is a list of examples for the <a href="/doc/helpers/form/">form helper</a>.</p>

	<table class="basic_table">
		<thead>
			<tr>
				<th scope="col">Type</th>
				<th scope="col" colspan="2">Examples</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($examples as $example) { ?>

				<tr>
					<td><?= html($example['name']) ?></td>
					<td><a href="<?= html($example['url_basic']) ?>">basic</a></td>
					<td><a href="<?= html($example['url_database']) ?>">database</a></td>
				</tr>

			<?php } ?>
		</tbody>
	</table>

	<p><a href="/examples/form/example/?type=paginated">Pagination</a> example, without JavaScript or Sessions.</p>

	<p><a href="<?= html(gateway_url('form-export')) ?>">Download</a> stand alone version of the forms class.</p>
