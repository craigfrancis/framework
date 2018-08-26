<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_asymmetric_create('my-key');

	$key_public = encryption::key_get_public('my-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	// $encrypted = encryption::encode($message, 'my-key', 'sign');

	$encrypted = 'Not supported';

//--------------------------------------------------
// 3. Decrypt

	// $decrypted = encryption::decode($encrypted, $key_public);

	$decrypted = $encrypted;

//--------------------------------------------------
// Results

	echo debug_dump([
			$key_public,
			$encrypted,
			$decrypted,
		]);

?>