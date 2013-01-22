# HTML Injection

[html](../../../doc/system/functions.md)() function.

---

## JavaScript

Avoid adding JavaScript code to the HTML, so something like:

	$response->head_add_html('<script>var x = ' . json_encode($x) . ';</script>');

Instead try:

	$response->js_code_add('var x = ' . json_encode($x) . ';');

The reason is that the variable could include a </script> tag, and the HTML parser (not being aware of the rules of JavaScript) will just see that as the end of the JavaScript, so will continue to treat the rest as HTML.

One very easy exploit would be to add an <img> tag for an image on a different domain. So if the victim is still running an old version of IE6, and the image contains code to cause a buffer overflow, or the image file actually contains [JavaScript code](http://adblockplus.org/blog/the-hazards-of-mime-sniffing), or is a 301 redirect back to the site for a nice [CRSF](../../../doc/security/csrf.md), then you may have a problem.
