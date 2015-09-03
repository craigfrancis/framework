
# Gateways

Helper function:

	gateway_url('xyz');

Returns a url such as:

	/a/api/xyz/

Which can be loaded by a browser (i.e. to return JSON data to some JavaScript).

This URL runs the script located at:

	/app/gateway/xyz.php

To run a gateway from elsewhere on the site use:

	$gateway = new gateway();
	$gateway->run('xyz');

---

TODO: add notes about the [tester helper](../../doc/system/tester.md), and maintenance gateway.

---

## Notes on oAuth:

Might be issues:

	http://hueniverse.com/2012/07/oauth-2-0-and-the-road-to-hell/

	Eran Hammer:
		What is now offered is a blueprint for an authorisation
		protocol, "that is the enterprise way", providing a "whole new
		frontier to sell consulting services and integration solutions".

Discussion with 2-legged auth in 2.0 (not good):

	http://www.ietf.org/mail-archive/web/oauth/current/msg07957.html

An idea of how to implement in 2.0:

	https://stackoverflow.com/q/14250383/how-does-2-legged-oauth-work-in-oauth-2-0

Overview of 1.0 and 2.0, with a possible solution:

	http://blog.facilelogin.com/2011/12/2-legged-oauth-with-oauth-10-and-20.html

Implementation, documentation and discussion for 1.0:

	http://www.ietf.org/mail-archive/web/oauth/current/msg06218.html
	https://developers.google.com/accounts/docs/OAuth#GoogleAppsOAuth
	http://oauth.googlecode.com/svn/spec/ext/consumer_request/1.0/drafts/2/spec.html

Google testing area, more so for 3-legged auth though:

	https://code.google.com/oauthplayground/
	https://developers.google.com/accounts/docs/OAuth2#CS
