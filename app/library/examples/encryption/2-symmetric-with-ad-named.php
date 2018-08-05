<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_symmetric_create('my-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';
	$associated_data = 1234; // e.g. user id

	$encrypted = encryption::encode($message, 'my-key', $associated_data);

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, 'my-key', $associated_data);

//--------------------------------------------------
// Results

	echo debug_dump([
			$associated_data,
			$encrypted,
			$decrypted,
		]);

?>