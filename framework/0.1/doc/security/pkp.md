
# Public Key Pinning

Otherwise known as PKP, it allows your website to inform the browser which certificates are allowed.

The Live website should be configured to only accept HTTPS connections first, ideally as a 301 redirect.

Then you should enable the "[Strict Transport Security](../../doc/security/transport.md)" header.

And create a backup key, just incase your current certificate needs to be revoked, or simply expires:

	openssl genrsa -out "backup.key" 2048;

Now find the SPKI fingerprint for both your current certificates key, and your new backup key... you can use one of the following:

	openssl rsa -in "my-website.key" -outform der -pubout |
	   openssl dgst -sha256 -binary | base64

	openssl req -in "my-website.csr" -pubkey -noout |
	   openssl rsa -pubin -outform der |
	   openssl dgst -sha256 -binary | base64

	openssl x509 -in "my-website.crt" -pubkey -noout |
	   openssl rsa -pubin -outform der |
	   openssl dgst -sha256 -binary | base64

Then the following config can be used (remember to substitute the fingerprints):

	$config['output.pkp_enforced'] = false;
	$config['output.pkp_report'] = false;
	$config['output.pkp_pins'] = array(
			'pin-sha256="bRmMf0OkJ8ArV9VPmDsSFeK253UBjMBVo5t8VmdY4Lw="',
			'pin-sha256="7fPFjIXIMozawdIR/Ue7AjOusulKX6Q+4hqdhazjr9E="',
			'max-age=2592000',
			'includeSubDomains',
		);

---

## Reporting

By default you should have a `system_report_pkp` table, which is populated when the browser posts data to /a/api/pkp-report/

If you want to record additional information in this table, you can either set the config variable:

	/app/library/setup/setup.php

	<?php

		config::set('output.pkp_report_extra', array(
				'user_id'   => strval(USER_ID),
				'user_name' => strval(USER_NAME),
			));

	?>

Or you can create a function which is called from the API:

	$config['output.pkp_report_handle'] = 'pkp_report';

	function pkp_report($report, $data_raw) {
		// Your code
	}

If this function returns an array, then it will work the same as `output.pkp_report_extra`.
