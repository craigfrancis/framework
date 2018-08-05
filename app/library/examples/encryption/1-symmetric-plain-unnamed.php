<?php

//--------------------------------------------------
// 1. Create a key

	$key = encryption::key_symmetric_create();

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted = encryption::encode($message, $key);

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, $key);

//--------------------------------------------------
// Results

	echo debug_dump([
			$key,
			$encrypted,
			$decrypted,
		]);

?>