<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_symmetric_create('my-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted = encryption::encode($message, 'my-key');

//--------------------------------------------------
// 3. Create a new key

	encryption::key_symmetric_create('my-key');

//--------------------------------------------------
// 4. Decrypt with old key

	$decrypted = encryption::decode($encrypted, 'my-key');

//--------------------------------------------------
// 5. Re-Encrypt with new key

	$encrypted_2 = encryption::encode($decrypted, 'my-key');

//--------------------------------------------------
// 6. Decrypt with new key

	$decrypted_2 = encryption::decode($encrypted_2, 'my-key');

//--------------------------------------------------
// Results

	echo debug_dump([
			$encrypted,
			$decrypted,
			$encrypted_2,
			$decrypted_2,
		]);

?>