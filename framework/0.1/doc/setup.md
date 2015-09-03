
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