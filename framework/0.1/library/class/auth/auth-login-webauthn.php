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

	<script type="text/javascript">
	//<![CDATA[

			// https://www.imperialviolet.org/2018/03/27/webauthn.html

		function created_credential(result) {
			console.log(result);
		}

		function create_credential() {

			console.log('hi');

			if ('credentials' in navigator) {

				const cose_alg_ECDSA_w_SHA256 = -7;

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
								'challenge': new Uint8Array([<?= htmlentities(implode(',', unpack('C*', $challenge))) ?>]),
								'pubKeyCredParams': [
										{
											'type': "public-key",
											'alg': cose_alg_ECDSA_w_SHA256
										}
									],
								'timeout': 10000, // In milliseconds
								'excludeCredentials': []
							}
					}).then(created_credential);

			}

		}

		var button = document.getElementById('button');
		if (button) {
			button.addEventListener('click', create_credential);
		}

	//]]>
	</script>
