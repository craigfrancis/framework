
# Setup

[Download](https://github.com/craigfrancis/framework) the framework code.

Create a folder for your project, e.g.

	/Volumes/WebServer/Projects/framework/
	/Volumes/WebServer/Projects/test.project/

Then in the terminal, cd into your project folder and run the command:

	../framework/framework/0.1/cli/run.sh -i

This will automatically create the files and folders for the [site structure](../../doc/setup/structure.md).

As to the Apache config, use something like:

	<VirtualHost *:80>

		ServerName www.example.com
		DocumentRoot /Volumes/WebServer/Projects/test.project/app/public/

		RewriteEngine on

		RewriteRule ^/a/files/(.*) /Volumes/WebServer/Projects/test.project/files/$1 [S=1]
		RewriteRule ^(.*)$ %{DOCUMENT_ROOT}$1

		RewriteCond $1/$2 -f
		RewriteRule ^(.*)/[0-9]+-([^/]+)$ $1/$2 [L]

		RewriteCond %{REQUEST_FILENAME} !^/a/files/
		RewriteCond %{REQUEST_FILENAME} !-d
		RewriteCond %{REQUEST_FILENAME} !-f
		RewriteRule ^(.*)$ %{DOCUMENT_ROOT}/index.php [L]

	</VirtualHost>

If your [configuration](../../doc/setup/config.md) sets the SERVER to 'stage', then [development mode](../doc/setup/debug.md) will be enabled.

From this point, you can create a very simple [view](../../doc/setup/views.md) file, e.g.

	/app/view/home.ctp

Or customise the overall page [template](../../doc/setup/templates.md):

	/app/template/default.ctp

Then start creating [units](../../doc/setup/units.md), and loading them with [controllers](../../doc/setup/controllers.md).

---

## Alternative Apache config

If you want to get fancy, and use a URL structure such as:

	http://test.project.emma.devcf.com/

Which maps automatically to the appropriate folder (as above), then use something like:

	#--------------------------------------------------
	# Server setup
	#--------------------------------------------------

	ServerName "emma.devcf.com"

	LoadModule php5_module libexec/apache2/libphp5.so

	DirectoryIndex index.html index.php
	AddType application/x-httpd-php .php
	AddType application/x-httpd-php-source .phps

	#--------------------------------------------------
	# Development environment
	#--------------------------------------------------

	NameVirtualHost *:80

	<VirtualHost *:80>

		DocumentRoot /Volumes/WebServer/Projects/craig.homepage/public
		UseCanonicalName off

		LogFormat "%h %l %u [%{SESSION}n] [%{LOG_INFO}n] [%{%Y-%m-%d %H:%M:%S}t] \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\"" inc_info
		CustomLog /private/var/log/apache2/access_log inc_info
		ErrorLog /private/var/log/apache2/error_log

		Alias /phpMyAdmin/ /Volumes/WebServer/Projects/craig.homepage/phpMyAdmin/

		RewriteEngine on
		#RewriteLog /var/log/apache2/rewrite_log
		#RewriteLogLevel 2
		#tail -f /var/log/apache2/rewrite_log | grep -o -E "(initial|subreq).*"

		# Map project from HOST into FILENAME
		RewriteCond %{HTTP_HOST} ^([a-z0-9]+(\.[a-z0-9]+))*\.[a-z]+\.devcf\.com$
		RewriteRule ^(/.*) - [NS,E=WEB_ROOT:/Volumes/WebServer/Projects/%1]

		# Drop into /files/ folder (if present)
		RewriteCond %{REQUEST_FILENAME} ^/a/files
		RewriteCond %{ENV:WEB_ROOT}/files -d
		RewriteRule ^/a/files(/.*) $1 [NS,E=WEB_ROOT:%{ENV:WEB_ROOT}/files]

		# Drop into /app/public/ folder (if present)
		RewriteCond %{ENV:WEB_ROOT}/app/public -d
		RewriteRule ^(/.*) - [NS,E=WEB_ROOT:%{ENV:WEB_ROOT}/app/public]

		# Drop into /public/ folder (if present)
		RewriteCond %{ENV:WEB_ROOT}/public -d
		RewriteRule ^(/.*) - [NS,E=WEB_ROOT:%{ENV:WEB_ROOT}/public]

		# Drop timestamp from url - e.g. "/a/12345-logo.jpg" to "/a/logo.jpg"
		RewriteCond %{ENV:WEB_ROOT}/$1/$2 -f
		RewriteRule ^(.*)/[0-9]+-([^/]+)$ $1/$2

		# If there is a rewrite.php script
		RewriteCond %{REQUEST_FILENAME} !^/a/
		RewriteCond %{ENV:WEB_ROOT}/a/php/rewrite.php -f
		RewriteRule ^(/.*) %{ENV:WEB_ROOT}/a/php/rewrite.php [L]

		# Apply the WEB_ROOT, both if initial request or as a sub-request... but only when set (not homepage)
		RewriteCond %{ENV:WEB_ROOT} !=""
		RewriteRule ^(/.*) %{ENV:WEB_ROOT}$1

	</VirtualHost>

	<Directory "/Volumes/WebServer/Projects">

		AllowOverride All
		Options +Indexes +Includes
		Order allow,deny
		Allow from all

	</Directory>

	<Directory "/Volumes/WebServer/Projects/craig.homepage/phpMyAdmin">

		Order deny,allow
		Deny from all
		Allow from 127.0.0.1

	</Directory>

	<VirtualHost *:80>

		ServerName localhost

		RewriteEngine on
		RewriteRule   ^/(.*)  http://emma.devcf.com/$1  [R=301]

	</VirtualHost>
