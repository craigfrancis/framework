<?php

//--------------------------------------------------
// 1. Create a key

	list($key_public, $key_secret) = encryption::key_asymmetric_create();

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted = encryption::encode($message, $key_public);

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, $key_secret);

//--------------------------------------------------
// Results

	echo debug_dump([
			$key_public,
			$key_secret,
			$encrypted,
			$decrypted,
		]);

?>