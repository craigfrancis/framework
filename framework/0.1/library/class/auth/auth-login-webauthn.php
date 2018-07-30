<?php

// https://www.w3.org/TR/webauthn/

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

			// https://www.imperialviolet.org/2018/03/27/webauthn.html

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
								},
							'user': {
									'name': "craig@example.com",
									'displayName': "Craig Francis",
									'id': new Uint8Array([0, 1, 2, 3, 4, 5, 6, 7])
								},
							'challenge': challenge_uInt8,
							'pubKeyCredParams': [
									{
										'type': "public-key",
										'alg': cose_alg_ECDSA_w_SHA256
									}
								],
							'timeout': 10000, // In milliseconds
							'excludeCredentials': []
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
