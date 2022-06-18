
# Cross Site Scripting

To protect pages from showing XSS (Cross Site Scripting), you should be [escaping your HTML output](../../../doc/security/strings/html-injection.md).

As an additional protection, you should setup a [Content Security Policy](../../doc/security/csp.md) that blocks all inline JavaScript and CSS.

There is a "X-XSS-Protection" header, but support is being removed from browsers due to the problems it can create (especially if the browser tries to sanitise the page).
