
# Sitemap.xml

This file allows search engines to find all the pages on your website.

In most situations this is **not required**, as you should have built the website so the search engine can find the pages by themselves.

This is only relevant if your website hides content behind things like search forms and JavaScript only navigation (usually not a good sign).

The reason this is usually an issue is because most website owners forget to keep the `sitemap.xml` up to date, or even working.

But if you really need it, create the file:

	/app/library/setup/sitemap.php

And just echo the XML in whatever way you like. For example:

	<?php

		$paths = [];

		echo '<?xml version="1.0" encoding="' . xml(config::get('output.charset')) . '"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		foreach ($paths as $path) {
			echo '<url><loc>' . xml($path) . '</loc></url>';
		}

		echo '</urlset>';

	?>

For more information, see [sitemaps.org](http://www.sitemaps.org/).
