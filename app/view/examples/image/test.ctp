
	<table class="basic_table test_table">
		<thead>
			<tr>
				<th scope="col">Config</th>
				<th scope="col">Normal</th>
				<th scope="col">Background</th>
				<th scope="col">Background + Grow</th>
				<th scope="col">Stretch</th>
			</tr>
		</thead>
		<tbody>
			<?php

				foreach ($images as $id => $image) {

					$url = $image['url'];

					$config = $image;
					unset($config['url']);

					echo '
						<tr>
							<td><pre>' . print_r($config, true) . '</pre></td>
							<td class="image"><img src="' . html($url) . '" alt="" /></td>
							<td class="image"><img src="' . html($url->get(['background' => '000000', 'grow' => 'false'])) . '" alt="" /></td>
							<td class="image"><img src="' . html($url->get(['background' => '000000'])) . '" alt="" /></td>
							<td class="image"><img src="' . html($url->get(['stretch' => 'true'])) . '" alt="" /></td>
						</tr>';

				}

			?>
		</tbody>
	</table>
