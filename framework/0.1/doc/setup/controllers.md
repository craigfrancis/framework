
# Controllers

Typically controllers just get and configure the appropriate [units](../../doc/setup/units.md).

The controller may also interact with the [response object](../../doc/system/response.md), this represents the content that will be sent to the browser, typically a HTML page.

The loading of controllers is based on the requested URL, and more than one may be used.

---

## Example

To handle requests such as:

	/news/
	/news/article1-ref/
	/news/article2-ref/

You might use a controller such as:

	/app/controller/news.php

	<?php

		class news_controller extends controller {

			public function action_index($article_ref = NULL) {

				if ($article_ref === NULL) {

					$unit = unit_add('news_list', array(
							'view_url' => url('/news/:ref/'),
							'search' => false,
						));

				} else {

					$unit = unit_add('news_view', array(
							'ref' => $article_ref,
							'admin_url' => url('/admin/news/edit/'),
						));

				}

			}

		}

	?>

---

## Nesting

You can have multiple controllers load for a request, for example the URLs:

	/admin/products/images/
	/admin/products/images/edit/

Will load the controllers:

	/app/controller/admin.php
		admin_controller

	/app/controller/admin/products.php
		admin_products_controller

	/app/controller/admin/products/images.php
		admin_products_images_controller

The first URL will call the `action_index()` method on the last controller.

And because there aren't any further controllers, the second URL will call the `action_edit()` method.

Which controllers were loaded, and how they were used, is listed in the [debug information](../../doc/setup/debug.md).

---

## Route method

A controller can control the routing of the request.

So for example, the admin controller may use:

	class admin_controller extends controller {

		public function route() {

			if (!ADMIN_LOGGED_IN && !in_array(request_folder_get(1), array('login', 'logout'))) {
				$admin = config::get('admin');
				$admin->require_login();
			}

		}

		public function action_index() {
			// Protected
		}

		public function action_login() {
		}

		public function action_logout() {
		}

	}

So anything under /admin/ will get passed though the route() method, and check the admin is logged in.

---

## Before and after methods

Before or after the appropriate `action_*` method is called, the controllers before() and after() methods are called:

	class example_controller extends controller {

		public function before() {

			$response = response_get();
			$response->set('title_html', '<h1><a href="/example/">Section Heading</a></h1>');

		}

		public function after() {
		}

	}
