<?php

//--------------------------------------------------
// 1. Create a key

	$key = encryption::key_symmetric_create();

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';
	$associated_data = 1234; // e.g. user id

	$encrypted = encryption::encode($message, $key, $associated_data);

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, $key, $associated_data);

//--------------------------------------------------
// Results

	echo debug_dump([
			$key,
			$associated_data,
			$encrypted,
			$decrypted,
		]);

?>