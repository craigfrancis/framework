
# Frameworks

As discussed in the [history](../../doc/notes/history.md), PHP Prime kind of started before many frameworks did (or became popular), such as:

* [Kohana](http://kohanaframework.org)
* [Symfony](http://symfony.com)
* [CodeIgniter](http://ellislab.com/codeigniter)
* [FuelPHP](http://fuelphp.com/)
* [CakePHP](http://www.cakephp.org)
* [Drupal](http://drupal.org)
* [Zend](http://framework.zend.com)

These frameworks have a much larger community behind them, and I would recommend using them (order dependent on the situation). HOWEVER, they are far from perfect, where I find that they often do not provide me with the functionality I have come to expect when programming.

In a way, I would suggest that everyone should have a go at creating a framework, just so they know what goes on behind the scenes, however it's typically not advisable for production use, simply due to the fact that most of the basic work has already been done already, and many bugs/issues/security problems have been resolved.

---

## Whats going on

My main problem with the frameworks is that when I start using them, they often try to avoid showing their internals... something I have fought against many times when I realised they were not working as expected.

For example, the SQL that some frameworks produce can return large amounts of data that might not be needed.

This is why PHP Prime has [extensive debug output](../../doc/setup/debug.md), where a simple panel is added to the bottom of the HTML page (when in dev mode) so you can quickly see:

* The processing time for the page.
* Review all of the SQL that has been run (with 'explain' output).
* See the [config values](../../doc/setup/config.md) for the website.
* How the request was handled (e.g. the controller and view file used).
* Get a quick reminder on common features (e.g. including a [JavaScript file](../../doc/setup/resources.md)).

---

## Database interaction

In CakePHP I did finally get the required [debug output](https://github.com/craigfrancis/framework/blob/master/resources/alternatives/cakePHP/dbo_mysql_custom.php), and found that due to how the Models are created (with the relationships being setup site wide), it was often returning information that the current query did not need. So you have to use things like [unbindModel()](http://book.cakephp.org/2.0/en/models/associations-linking-models-together.html#creating-and-destroying-associations-on-the-fly), and remember that these may only work for the next query.

Likewise other things happen by "default", and without actually thinking/tracing it though, you end up with issues such as the [Mass Assignment Vulnerability](http://stackoverflow.com/questions/10458468/), a name for which [I didn't know about](https://groups.google.com/d/topic/cake-php/yvl-x88hl6E/discussion) at the time.

Or a simple validation rule that can be skipped as the key in the model is accidentally named "post_code", but in the database "postcode".

Its also not good when your adding exceptions to your base validation rules (set on a model), especially when you start off with a basic min/max length check, then at a later date need to add a new rule that should be applied in some places (e.g. web orders need a delivery address, but the new offline orders do not).

Thats not to say the database abstraction isn't useful at times. For example, if you have tables for things that need to be moderated, the base "Model" can check to see if the table has a field called "moderation_state", and then update all queries (in theory) to limit the returned records based on the current users permission (it worked, but there were some very odd edge cases that cropped up).

But there are also common issues with both approaches (database abstraction vs raw SQL), for example you may find that your doing the same kind of query many times in the project, so you create a common function/method to get the data, but as this code is being re-used and extended for many different cases, you will find that its returning much more data than is required for the majority of cases.

---

## External dependencies

As with most frameworks you should be able to use code provided by other developers, and this usually is much better tested than anything you can come up with yourself. However you should always have a rough idea on how things work, as sometimes things can go very wrong.

For example on one Flash based website I was working on, the Flash animation on the website would talk directly to the server via AMF. But on one day it stopped working altogether, and there was no indication what went wrong (nothing in the error logs, and no tools to debug the binary protocol).

---

## Custom HTML

Some Frameworks like [Drupal](http://drupal.org) are [well known](http://drupal.org/node/1324382) for their auto generated HTML, and that it is [possible to edit this](http://api.drupal.org/api/drupal/includes%21module.inc/group/hooks/7), but can become very difficult.

For example Zend uses [decorators](http://framework.zend.com/manual/1.12/en/zend.form.decorators.html) to customise the output of form fields, however I have no idea how to explain editing this to someone with HTML experience - it took me long enough to add a simple <div> wrapper.

Thats not to say PHP Prime is any easier to edit the generated HTML output, but as I know how it all works (and it tries to remain fairly simple), I have a good idea what can be done for my clients.

---

## Controllers

Most frameworks also only work with a simple controller/action url structure, however I have found this very limited, and often very difficult to use if custom URL structures are required.

For example, the typical framework will take a request such as:

	/admin/users/edit/

And will either automatically map that to the "admin_add()" action on the "users" controller, where /admin/ is treated as a special case... or they will expect you to manage custom routes to the controllers.

At no point have I found this setup useful, especially when you have complicated nesting of elements, for example:

	/admin/assessment/add/
	/admin/assessment/edit/

	/admin/profile/

	/tutor/assessment/add/ - very different to the admin version.
	/tutor/assessment/edit/ - different layout and functionality to admin version.
	/tutor/assessment/edit/appointment/
	/tutor/assessment/edit/report/edit/ - 10 pages of form fields.
	/tutor/assessment/edit/report/upload/

	/tutor/support/add/
	/tutor/support/session/add/
	/tutor/support/session/edit/ - very different to the add page.
	/tutor/support/student/
	/tutor/support/invoice/

	/tutor/profile/ - same as the admin profile page.
	/tutor/diary/ - to view sessions and appointments.

	/student/ - simple view of appointments and sessions.

	/files/ - list of files that certain members of staff can view.

Instead I find that having the option to have multiple levels of controllers, where the one at the root level can effect the routing of the request, is much more useful, especially as you can then use a very simple [url helper](../../../doc/helpers/url.md).

Also, as each controller can effect the routing, it can perform all kinds of custom verification... so in the example above, I can have a simple check that any page loaded under /tutor/ has a logged in tutor, /admin/ is a logged in admin, and /files/ is any type of staff member.

Likewise, you can have a [common controller](../../doc/setup/controllers.md) shared between the user types (i.e. the profile page in the example above).

---

## Additional

This is where I believe frameworks need to do more by default, for example:

- [CSP](../../doc/security/csp.md) headers.
- Built in [CSRF](../../doc/security/csrf.md) checks.
- [Sessions](../../doc/helpers/session.md) that avoid [fixation](../../doc/security/sessions.md) problems.
- Building a [table](../../doc/helpers/table.md) for HTML and CSV download.
- Including [CSS files](../../doc/setup/resources.md) based on view path, browser cache handling, and tidy.
- Adding "inline" [JavaScript](../../doc/setup/resources.md), where the browser actually loads an external file.
- [Forms](../../doc/helpers/form.md) remembering values when the users session expires.
- [User accounts](../../doc/helpers/user.md) with registration, login, profile, and forgotten password support.
