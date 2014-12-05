
# Site Framing

To protect pages from displaying in frames on malicious websites, you can specify how the page can be framed with:

	$config['output.framing'] = 'DENY';
	$config['output.framing'] = 'SAMEORIGIN';
	$config['output.framing'] = 'ALLOW';

This is enabled by default as DENY, and simply sets the 'X-Frame-Options' header, along with the 'frame-ancestors' [Content Security Policy](../../doc/security/csp.md) directive.
