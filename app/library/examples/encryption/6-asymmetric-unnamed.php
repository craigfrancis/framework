<?php

//--------------------------------------------------
// 1. Create a key

	list($key1_public, $key1_secret) = encryption::key_asymmetric_create(); // Sender
	list($key2_public, $key2_secret) = encryption::key_asymmetric_create(); // Recipient

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted = encryption::encode($message, $key2_public, $key1_secret);

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, $key2_secret, $key1_public);

//--------------------------------------------------
// Results

	echo debug_dump([
			$key1_public,
			$key1_secret,
			$key2_public,
			$key2_secret,
			$encrypted,
			$decrypted,
		]);

?>