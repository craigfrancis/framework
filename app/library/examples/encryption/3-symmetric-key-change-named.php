<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_symmetric_create('my-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	$encrypted_1 = encryption::encode($message, 'my-key');

//--------------------------------------------------
// 3. Create new key, re-encrypt, delete old key

	$new_key_id = encryption::key_symmetric_create('my-key');

	$decrypted_1 = encryption::decode($encrypted_1, 'my-key');
	$encrypted_2 = encryption::encode($decrypted_1, 'my-key');

	encryption::key_cleanup('my-key', [$new_key_id]);

//--------------------------------------------------
// 4. Decrypt with new key

	$decrypted_2 = encryption::decode($encrypted_2, 'my-key');

//--------------------------------------------------
// Results

	echo debug_dump([
			$encrypted_1,
			$decrypted_1,
			$new_key_id,
			$encrypted_2,
			$decrypted_2,
		]);

?>