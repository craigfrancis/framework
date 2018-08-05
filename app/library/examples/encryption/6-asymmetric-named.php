<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_asymmetric_create('my-key');
	encryption::key_asymmetric_create('recipient-key');

	$key1_public = encryption::key_get_public('my-key');
	$key2_public = encryption::key_get_public('recipient-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted = encryption::encode($message, $key2_public, 'my-key');

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, 'recipient-key', $key1_public);

//--------------------------------------------------
// Results

	echo debug_dump([
			$key1_public,
			$key2_public,
			$encrypted,
			$decrypted,
		]);

?>