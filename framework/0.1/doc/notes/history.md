
# History

PHP Prime effectively started in 2006, before many frameworks became popular.

It was originally a collection of [helper objects](../../doc/helpers.md), that I have since found indispensable when creating websites.

The original handling of HTTP requests was done by:

1. A single "rewrite.php" script that took the `SCRIPT_URL/REQUEST_URI` server variable;
2. Which included the relevant processing script, containing the majority of the server side PHP code for the page (now known by many as a "controller");
3. The HTML was started with the site template "pageTop.php" script (which contained things like the main navigation);
4. Then the relevant PHP script for the pages content (now known as the "view");
5. And the final "pageBottom.php" script put in the last bits of HTML (e.g. the footer).

In 2011 I created a basic environment where these helper objects are always available via an auto loader, and a common interface for handling the HTTP requests, via controller objects.

It's intent is to remain fast for creating websites (and obviously loading pages), and allow font end develoeprs (HTML) to edit the website via a simple [collection of files](../../doc/introduction.md), based on the URL used to request the page.
