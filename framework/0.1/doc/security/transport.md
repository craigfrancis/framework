
# Strict transport security

Once you have setup a 301 redirect for all HTTP to HTTPS connections, then update the WebServers config to set the following header:

	Strict-Transport-Security: max-age=31536000; includeSubDomains; preload;

The framework does not do this itself, as ideally it should be set for every request - including images.
