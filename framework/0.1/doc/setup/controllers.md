
# Controllers

Typically the **controller** will return information from the [database](../../doc/system/database.md), and interact with [helpers](../../doc/helpers.md), to help provide data to the [view](../../doc/setup/views.md).

The main thing the **controller** will interact with is the [response helper](../../doc/system/response.md), this represents the content that will be sent to the browser, typically a HTML page.

So for example, in your profile controller you might have something like:

	/app/controller/profile.php
		<?php
			class controller_profile extends controller {
				function action_index() {
                	$response = response_get();
					$response->set('name', 'Craig');
				}
			}
		?>

	/app/view/profile.php
		<p>Hi <?= html($name); ?></p>

---

## Common controllers

Ref the /app/library/controller/ folder.
