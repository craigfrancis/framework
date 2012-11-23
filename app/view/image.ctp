
	<table class="basic_table">
		<thead>
			<tr>
				<th scope="col">Config</th>
				<th scope="col">Normal</th>
				<th scope="col">Crop</th>
				<th scope="col">Grow</th>
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
							<td><img src="' . html($url) . '" alt="" /></td>
							<td><img src="' . html($url->get(array('crop' => 'true'))) . '" alt="" /></td>
							<td><img src="' . html($url->get(array('grow' => 'true'))) . '" alt="" /></td>
							<td><img src="' . html($url->get(array('stretch' => 'true'))) . '" alt="" /></td>
						</tr>';

				}

			?>
		</tbody>
	</table>
