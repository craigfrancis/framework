
Still a work in progress...

	gzip on;
	gzip_types text/plain text/css text/xml application/javascript;

	charset utf-8;
	server_tokens off;

	log_format inc_info '$remote_addr - $remote_user [$sent_http_x_log_info] [$time_iso8601] [$request_time/XXX] "$request" $status $body_bytes_sent "$http_referer" "$http_user_agent"';

	ssl_protocols TLSv1 TLSv1.1 TLSv1.2;
	ssl_ciphers 'ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES256-GCM-SHA384:DHE-RSA-AES128-GCM-SHA256:DHE-DSS-AES128-GCM-SHA256:kEDH+AESGCM:ECDHE-RSA-AES128-SHA256:ECDHE-ECDSA-AES128-SHA256:ECDHE-RSA-AES128-SHA:ECDHE-ECDSA-AES128-SHA:ECDHE-RSA-AES256-SHA384:ECDHE-ECDSA-AES256-SHA384:ECDHE-RSA-AES256-SHA:ECDHE-ECDSA-AES256-SHA:DHE-RSA-AES128-SHA256:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA256:DHE-RSA-AES256-SHA256:DHE-DSS-AES256-SHA:DHE-RSA-AES256-SHA:ECDHE-RSA-DES-CBC3-SHA:ECDHE-ECDSA-DES-CBC3-SHA:AES128-GCM-SHA256:AES256-GCM-SHA384:AES128-SHA256:AES256-SHA256:AES128-SHA:AES256-SHA:AES:CAMELLIA:DES-CBC3-SHA:!aNULL:!eNULL:!EXPORT:!DES:!RC4:!MD5:!PSK:!aECDH:!EDH-DSS-DES-CBC3-SHA:!EDH-RSA-DES-CBC3-SHA:!KRB5-DES-CBC3-SHA';
	ssl_prefer_server_ciphers on;

	ssl_session_cache shared:SSL:10m; # 10MB -> ~40,000 sessions.
	ssl_session_timeout 24h;		  # 24 hours
	ssl_buffer_size 1400;			 # 1400 bytes to fit in one MTU

	keepalive_timeout 300;
	spdy_keepalive_timeout 300;
	spdy_headers_comp 6;

	server {

		listen 192.168.0.1:80;

		server_name example.com *.example.com;

		rewrite ^ https://www.example.com$request_uri? permanent;

	}

	server {

		listen 192.168.0.1:443 ssl spdy;
		server_name www.example.com;

		access_log /www/live/test.project/logs/nginx.access_log inc_info;
		error_log /www/live/test.project/logs/nginx.error_log warn;

		ssl_certificate /etc/apache2/ssl/www.example.com.pem;
		ssl_certificate_key /etc/apache2/ssl/www.example.com.key;

		ssl_stapling on;
		ssl_stapling_verify on;
		ssl_trusted_certificate /etc/apache2/ssl/www.example.com.ca;
		resolver 8.8.8.8;

		add_header 'X-Frame-Options' 'deny';
		add_header 'X-XSS-Protection' '1; mode=block';
		add_header 'X-Content-Type-Options' 'nosniff';
		add_header 'Strict-Transport-Security' 'max-age=31536000; includeSubDomains';

		if ($http_host != "www.example.com") {
			rewrite ^ https://www.example.com$request_uri? permanent;
		}

		rewrite "^(.*)/[0-9]{10,}-([^/]+)$" $1/$2;

		root "/www/live/test.project/app/public";

		location / {
			try_files $uri @php-fpm;
			location ~* \.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|eot|mp4|ogg|ogv|webm|pdf)$ {
				expires max;
				try_files $uri @php-fpm;
			}
		}

		location ^~ /a/files/ {
			alias /www/live/test.project/files/;
			location ~* \.(css|js|jpg|jpeg|gif|png|ico|gz|svg|svgz|ttf|otf|woff|eot|mp4|ogg|ogv|webm|pdf)$ {
				expires max;
			}
		}

		location @php-fpm {
			include /etc/nginx/fastcgi_params;
			fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
			fastcgi_param SCRIPT_FILENAME $document_root/index.php;
			fastcgi_param PHP_VALUE "newrelic.appname='Test: Website'";
		}

		location ~* (/\.|\.php$) {
			return 404;
		}

	}

---

Also look at:

	sendfile on;
	sendfile_max_chunk 1m;

	https://www.nginx.com/resources/admin-guide/serving-static-content/

	Use this for /app/public/a/* and /files/*
