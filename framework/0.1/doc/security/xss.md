
# Cross site scripting

To protect pages from showing XSS (Cross Site Scripting), you should be [escaping your HTML output](../../../doc/security/strings/html-injection.md).

However, as an additional protection, you can instruct some browsers to look for a particular kind of XSS, known as [Reflected XSS](https://www.owasp.org/index.php/Cross-site_Scripting_%28XSS%29), using the following:

	$config['output.xss_reflected'] = 'block';
	$config['output.xss_reflected'] = 'filter';

This is enabled by default as 'block', and simply sets the 'X-XSS-Protection' header, along with the 'reflected-xss' [Content Security Policy](../../doc/security/csp.md) directive.
