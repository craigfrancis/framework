
# Setup

[Download](https://github.com/craigfrancis/framework) the framework code.

Create a folder for your project, e.g.

	/www/live/framework/
	/www/live/test.project/

Then in the terminal, cd into your project folder and run the command:

	../framework/framework/0.1/cli/run.sh -i

This will automatically create the files and folders for the [site structure](../../doc/setup/structure.md).

For the web server config, use one of the examples for [Apache](../../doc/setup/server/apache.md) or [Nginx](../../doc/setup/server/nginx.md).

You can now create a very simple [view](../../doc/setup/views.md) file, e.g.

	/app/view/home.ctp

Or customise the overall page [template](../../doc/setup/templates.md):

	/app/template/default.ctp

For dynamic content, create [units](../../doc/setup/units.md), which can be loaded by [controllers](../../doc/setup/controllers.md).

And during development, it is worth enabling [debug mode](../doc/setup/debug.md).

When your are ready to upload to a server, look at the [uploading process](../../doc/system/uploading.md).

---

As an aside, the framework itself is **not** built in a typical MVC structure, and only borrows ideas that are appropriate for a web based system.

Instead code is broken down into multiple components:

1. [Routes](../doc/setup/routes.md) - rarely used.
2. [Controllers](../doc/setup/controllers.md) - selects and configures the appropriate unit(s).
3. [Units](../doc/setup/units.md) - a thing on the page (form, table, etc).
4. [Helpers](../doc/helpers.md) - typically used by units.
5. [Views](../doc/setup/views.md) - for simple pages.
6. [Templates](../doc/setup/templates.md) - for the overall page (generic to the whole site).
7. [Resources](../doc/setup/resources.md) - for CSS, JS, [favicon.ico](../../doc/setup/resources/favicon.md), [robots.txt](../../doc/setup/resources/robots.md), [sitemap.xml](../../doc/setup/resources/sitemap.md).
8. [Gateways](../doc/setup/gateways.md) - for API's.
9. [Jobs](../doc/setup/jobs.md) - like cron jobs.