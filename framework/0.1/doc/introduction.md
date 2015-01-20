
# Introduction

PHP Prime is a framework built for the clients of [Code Poets](https://www.code-poets.co.uk/).

It is not supported for anyone else, however you may find some of the ideas interesting.

---

The framework itself is **not** built in a typical MVC structure, and only borrows ideas that are appropriate for a web based system.

Instead code is broken down into multiple components:

1. [Routes](../doc/setup/routes.md) - rarely used.
2. [Controllers](../doc/setup/controllers.md) - selects and configures the appropriate unit(s).
3. [Units](../doc/setup/units.md) - a thing on the page (form, table, etc).
4. [Helpers](../doc/helpers.md) - typically used in units.
5. [Views](../doc/setup/views.md) - for simple pages.
6. [Templates](../doc/setup/templates.md) - for the overall page (generic to the whole site).
7. [Resources](../doc/setup/resources.md) - for CSS, JS, [favicon.ico](../../doc/setup/resources/favicon.md), [robots.txt](../../doc/setup/resources/robots.md), [sitemap.xml](../../doc/setup/resources/sitemap.md))
8. [Gateways](../doc/setup/gateways.md) - for API's.
9. [Jobs](../doc/setup/jobs.md) - like cron jobs.

---

# Next steps

At least scan over the notes on [security](../doc/security.md), which applies to all websites/frameworks.

Then [setup](../doc/setup.md) your project.
