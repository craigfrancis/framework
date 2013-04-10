
# Controllers

Typically the **controller** will return information from the [database](../../doc/system/database.md), and interact with [helpers](../../doc/helpers.md), to help provide data to the [view](../../doc/setup/views.md).

The main thing the **controller** will interact with is the [response helper](../../doc/system/response.md), this represents the content that will be sent to the browser, typically a HTML page.

So for example, in your profile controller you might have something like:

	/app/controller/profile.php
		<?php
			class profile_controller extends controller {
				public function action_index() {
					$response = response_get();
					$response->set('name', 'Craig');
				}
			}
		?>

	/app/view/profile.php
		<p>Hi <?= html($name); ?></p>

---

## Route method

	class admin_controller extends controller {
		public function route() {
		}
	}

Anything under /admin/ will get passed though the route() method, which can check the user permissions.

---

## Before and after methods

	class example_controller extends controller {
		public function before() {
		}
		public function after() {
		}
	}

---

## Common controllers

Ref the /app/library/controller/ folder.
