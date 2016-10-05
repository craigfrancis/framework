
	<h2>Form methods</h2>
	<ul>
	<?php

		foreach ($form_classes as $class) {

			echo '
				<li id="' . html($class) . '">
					<p><a href="./?show=' . html(urlencode($class)) . '#' . html(urlencode($class)) . '">' . html($class) . '</a></p>';

			if ($show == $class && count($class_methods) > 0) {

				echo '
					<ul>';

				foreach ($class_methods as $method) {
					echo '
						<li>' . html($method['name']) . '(<span class="parameters">' . html(implode(', ', $method['parameters'])) . '</span>)</li>';
				}

				echo '
					</ul>';

			}

			echo '
				</li>';

		}

	?>
	</ul>
