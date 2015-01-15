
# Routes

These allow you to make generic changes to URLs for your website, for example you may prefix every URL with the ISO language code:

	https://www.example.com/en/about/
	https://www.example.com/fr/about/
	https://www.example.com/de/about/

Where the /about/ page is handled with exactly the same code, and has access to the language code in a variable.

The configuration for this is stored in:

	/app/library/setup/routes.php

And contains something like:

	$routes[] = array(
			'path' => '^/(en|fr|de)/',
			'replace' => '/',
			'method' => 'regexp',
			'variables' => array(
					'language',
				),
		);

	$routes[] = array(
			'path' => '/blog/',
			'replace' => '/news/',
		);

	$routes[] = array(
			'path' => '^/(desert|sea)/',
			'replace' => '/location-\1/',
			'method' => 'regexp',
			'variables' => array(
					'location',
				),
		);

	$routes[] = array( // This example is better handled with a controller.
			'path' => '/news/*/',
			'replace' => '/news/item/',
			'method' => 'wildcard',
			'variables' => array(
					'ref',
					'error',
				),
		);


 // wildcard, prefix, suffix, exact, regexp, preg
