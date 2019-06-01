<?php

	class auth_login_webauthn_base extends check {

		//--------------------------------------------------
		// Variables

			protected $auth = NULL;

			public function __construct($auth) {
				$this->auth = $auth;
			}

		//--------------------------------------------------
		// Fields



		//--------------------------------------------------
		// Actions



	}

?>
<?php

	$challenge = random_bytes(32);

?>

	<p><input type="button" id="create_button" value="Create" /></p>
	<script type="text/javascript">
	//<![CDATA[

			// https://www.w3.org/TR/webauthn/
			// https://developer.mozilla.org/en-US/docs/Web/API/Web_Authentication_API#Examples
			// https://www.imperialviolet.org/2018/03/27/webauthn.html

			//--------------------------------------------------
			// https://webauthndemo.appspot.com
			//--------------------------------------------------
			//	GET https://webauthndemo.appspot.com/BeginMakeCredential
			//	>>>
			//		{}
			//	<<<
			//		{
			//			"rp": {
			//				"id": "webauthndemo.appspot.com",
			//				"name": "webauthn-demo"
			//			},
			//			"user": {
			//				"name": "craig.francis",
			//				"displayName": "craig.francis",
			//				"id": "Y3JhaWcuZnJhbmNpc0BnbWFpbC5jb20="
			//			},
			//			"challenge": "UF83LUp3xnvTMC5Cfw9sBDuc2DTYUe1nVjkyypfqT88=",
			//			"pubKeyCredParams": [
			//				{"type": "public-key","alg": -7},
			//				{"type": "public-key","alg": -35},
			//				{"type": "public-key","alg": -36},
			//				{"type": "public-key","alg": -257},
			//				{"type": "public-key","alg": -258},
			//				{"type": "public-key","alg": -259},
			//				{"type": "public-key","alg": -37},
			//				{"type": "public-key","alg": -38},
			//				{"type": "public-key","alg": -39}
			//			],
			//			"authenticatorSelection": {
			//				"requireResidentKey": false
			//			},
			//			"session": {
			//				"id": 5076324926357504,
			//				"challenge": "UF83LUp3xnvTMC5Cfw9sBDuc2DTYUe1nVjkyypfqT88=",
			//				"origin": "webauthndemo.appspot.com"
			//			}
			//		}
			//--------------------------------------------------
			//	POST https://webauthndemo.appspot.com/FinishMakeCredential
			//	>>>
			//		{
			//			"id": "4MmQQekq2sLqiOc6LXPPjT41j00WLBsFUHxd3R2cNFCblUoSWjOczBuHBa1j-irk4XtLvXgWjDpmZo6L0lyQLg",
			//			"type": "public-key",
			//			"rawId": "4MmQQekq2sLqiOc6LXPPjT41j00WLBsFUHxd3R2cNFCblUoSWjOczBuHBa1j+irk4XtLvXgWjDpmZo6L0lyQLg==",
			//			"response": {
			//				"clientDataJSON": "eyJjaGFsbGVuZ2UiOiJVRjgzTFVwM3hudlRNQzVDZnc5c0JEdWMyRFRZVWUxblZqa3l5cGZxVDg4Iiwib3JpZ2luIjoiaHR0cHM6Ly93ZWJhdXRobmRlbW8uYXBwc3BvdC5jb20iLCJ0eXBlIjoid2ViYXV0aG4uY3JlYXRlIn0=",
			//				"attestationObject": "o2NmbXRkbm9uZWdhdHRTdG10oGhhdXRoRGF0YVjERsx/uWedVbLbkJLhyNnl4dArdYDwtIEsdwli4eSPWthBAAAAAAAAAAAAAAAAAAAAAAAAAAAAQODJkEHpKtrC6ojnOi1zz40+NY9NFiwbBVB8Xd0dnDRQm5VKEloznMwbhwWtY/oq5OF7S714Fow6ZmaOi9JckC6lAQIDJiABIVgg54ZIfJs+hla+uqFQPvslGElGq0cjF0CImG/2FxcVc8EiWCAC5+3rPDmlSDDSQLzf/3T0u+sBiSAtjwuKq5WUwLfUcw=="
			//			}
			//		}
			//	<<<
			//		{"success":true,"message":"Successfully created credential"}
			//--------------------------------------------------
			//	GET https://webauthndemo.appspot.com/BeginGetAssertion
			//	>>>
			//		{}
			//	<<<
			//		{
			//			"challenge": "e/ZNAx6HeroMJQs4En+7kOjYn32EkGCSS0ZD4XGSpmI=",
			//			"rpId": "webauthndemo.appspot.com",
			//			"allowCredentials": [
			//				{
			//					"type": "public-key",
			//					"id": "4MmQQekq2sLqiOc6LXPPjT41j00WLBsFUHxd3R2cNFCblUoSWjOczBuHBa1j+irk4XtLvXgWjDpmZo6L0lyQLg=="
			//				}
			//			],
			//			"session": {
			//				"id": 5657382461898752,
			//				"challenge": "e/ZNAx6HeroMJQs4En+7kOjYn32EkGCSS0ZD4XGSpmI=",
			//				"origin": "webauthndemo.appspot.com"
			//			}
			//		}
			//--------------------------------------------------
			//	GET https://webauthndemo.appspot.com/BeginMakeCredential
			//	>>>
			//		{
			//			"id": "4MmQQekq2sLqiOc6LXPPjT41j00WLBsFUHxd3R2cNFCblUoSWjOczBuHBa1j-irk4XtLvXgWjDpmZo6L0lyQLg",
			//			"type": "public-key",
			//			"rawId": "4MmQQekq2sLqiOc6LXPPjT41j00WLBsFUHxd3R2cNFCblUoSWjOczBuHBa1j+irk4XtLvXgWjDpmZo6L0lyQLg==",
			//			"response": {
			//				"clientDataJSON": "eyJjaGFsbGVuZ2UiOiJlX1pOQXg2SGVyb01KUXM0RW4tN2tPalluMzJFa0dDU1MwWkQ0WEdTcG1JIiwib3JpZ2luIjoiaHR0cHM6Ly93ZWJhdXRobmRlbW8uYXBwc3BvdC5jb20iLCJ0eXBlIjoid2ViYXV0aG4uZ2V0In0=",
			//				"authenticatorData": "Rsx/uWedVbLbkJLhyNnl4dArdYDwtIEsdwli4eSPWtgBAAAABQ==",
			//				"signature": "MEYCIQDTYt9mSVfzTUSyYikVoMYvAvcix+YG8nopnnb708rIpAIhALMGJWCZqtTlphuwYIaFsHTol7ywrQNGUGFWLC9ahQCG",
			//				"userHandle": ""
			//			}
			//		}
			//	<<<
			//		{"success":true,"message":"Successful assertion","handle":"E0C99041E92ADAC2EA88E73A2D73CF8D3E358F4D162C1B05507C5DDD1D9C34509B954A125A339CCC1B8705AD63FA2AE4E17B4BBD78168C3A66668E8BD25C902E"}
			//--------------------------------------------------

		var challenge_uInt8 = new Uint8Array([<?= htmlentities(implode(',', unpack('C*', $challenge))) ?>])
			challenge_base64 = <?= json_encode(base64_encode($challenge)) ?>;

		function parse_auth_data(buffer) {

				// https://gist.github.com/herrjemand/b5137f71df4eea0b23db86f64d56bc63
				//
				// This does not work.

			var rpIdHash      = buffer.slice(0, 32);          buffer = buffer.slice(32);
			var flagsBuf      = buffer.slice(0, 1);           buffer = buffer.slice(1);
			var flagsInt      = flagsBuf[0];
			var flags         = {
					'up': !!(flagsInt & 0x01),
					'uv': !!(flagsInt & 0x04),
					'at': !!(flagsInt & 0x40),
					'ed': !!(flagsInt & 0x80),
					'flagsInt' : flagsInt
				};

			var counterBuf    = buffer.slice(0, 4);           buffer = buffer.slice(4);
			var counter       = null; // counterBuf.readUInt32BE(0);

			var aaguid        = buffer.slice(0, 16);          buffer = buffer.slice(16);
			var credIDLenBuf  = buffer.slice(0, 2);           buffer = buffer.slice(2);
			var credIDLen     = 0; // credIDLenBuf.readUInt16BE(0);
			var credID        = buffer.slice(0, credIDLen);   buffer = buffer.slice(credIDLen);
			var COSEPublicKey = null; // buffer

			return {
					'rpIdHash': rpIdHash,
					'flags': flags,
					'counter': counter,
					'aaguid': aaguid,
					'credID': credID,
					'COSEPublicKey': COSEPublicKey
				};

		}

		function create_credential() {

			this.setAttribute('disabled', 'disabled');

			if (!('credentials' in navigator) || !('Uint8Array' in window) || !('TextDecoder' in window)) {
				return;
			}

			var cose_alg_ECDSA_w_SHA256 = -7;

			navigator.credentials.create({
					'publicKey': {
							'rp': {
									'name': "Test Website"
									// 'id': 'login.example.com',
									// 'icon': 'https://login.example.com/login.ic'
								},
							'user': {
									'name': "craig@example.com",
									'displayName': "Craig Francis",
									'id': new Uint8Array([0, 1, 2, 3, 4, 5, 6, 7])
								},
							'challenge': challenge_uInt8,
							'pubKeyCredParams': [
									{
										'type': "public-key", // As of March 2019, only "public-key" may be used.
										'alg': cose_alg_ECDSA_w_SHA256 // -7 indicates the elliptic curve algorithm ECDSA with SHA-256, https://www.iana.org/assignments/cose/cose.xhtml#algorithms
									}
								],
							'timeout': 10000, // In milliseconds
							'attestation': 'none', // Default, or could be "direct" or "indirect"
							'excludeCredentials': [ // Avoid creating new public key credentials for an existing user who already have some.
								// {
								// 	'type': "public-key",
								// 	'id': new Uint8Array(26)
								// }
							]
						}
				}).then(function(result) {

					var decoder = new TextDecoder("utf-8"),
						client_data = JSON.parse(decoder.decode(result.response.clientDataJSON));

					console.log('ID: "' + result.id + '"');
					console.log('Type: "' + result.type + '"');

					console.log('Check type ["webauthn.create" == "' + client_data.type + '"]');
					console.log('Check origin ["' + location.origin + '" == "' + client_data.origin + '"]');

					var challenge_source_1 = btoa(String.fromCharCode.apply(null, challenge_uInt8)).replace(/=+$/, ''),
						challenge_source_2 = challenge_base64.replace(/=+$/, ''),
						challenge_parsed = client_data.challenge.replace(/=+$/, '').replace(/-/g, '+').replace(/_/g, '/');

					console.log('Check challenge ["' + challenge_source_1 + '" == "' + challenge_source_2 + '" == "' + challenge_parsed + '"]');

						// Note: The challenge from the device is using "_" instead of "/", and "-" instead of "+"... and no "=" padding.

					console.log(parse_auth_data(result.response.attestationObject));

				}).catch(function(e) {

					console.log('Error', e);

				});

		}

		var create_button = document.getElementById('create_button');
		if (create_button) {
			create_button.addEventListener('click', create_credential);
		}

	//]]>
	</script>

	<p><input type="button" id="get_button" value="Get" /></p>
	<script type="text/javascript">
	//<![CDATA[

		function got_credential(result) {
			console.log(result);
		}

		function get_credential() {

			this.setAttribute('disabled', 'disabled');

			if (!('credentials' in navigator) || !('Uint8Array' in window)) {
				return;
			}

			var cose_alg_ECDSA_w_SHA256 = -7;

			navigator.credentials.get({
					'publicKey': {
							'challenge': new Uint8Array([<?= htmlentities(implode(',', unpack('C*', $challenge))) ?>]),
							'timeout': 10000, // In milliseconds
							'allowCredentials': [
									{
										'id': new Uint8Array([[31, 140, 131, 208, 61, 2, 220, 185, 219, 74, 252, 198, 51, 81, 104, 23, 164, 25, 165, 70, 252, 199, 187, 156, 150, 144, 119, 240, 243, 69, 12, 76, 0, 122, 246, 242, 13, 93, 196, 107, 82, 137, 113, 142, 6, 174, 77, 179, 198, 50, 165, 164, 12, 142, 141, 56, 126, 94, 141, 171, 97, 63, 84, 102]]),
										'type': 'public-key',
										'transports': ['usb', 'nfc', 'ble']
									}
								]
						}
				}).then(got_credential);

		}

		var get_button = document.getElementById('get_button');
		if (get_button) {
			get_button.addEventListener('click', get_credential);
		}

	//]]>
	</script>
