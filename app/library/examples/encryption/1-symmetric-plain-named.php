<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_symmetric_create('my-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted = encryption::encode($message, 'my-key');

//--------------------------------------------------
// 3. Decrypt

	$decrypted = encryption::decode($encrypted, 'my-key');

//--------------------------------------------------
// Results

	echo debug_dump([
			$encrypted,
			$decrypted,
		]);

?>