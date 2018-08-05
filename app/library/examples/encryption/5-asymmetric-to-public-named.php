<?php

//--------------------------------------------------
// 1. Create a key

	encryption::key_asymmetric_create('my-key');

	$key_public = encryption::key_get_public('my-key');

//--------------------------------------------------
// 2. Encrypt

	$message = 'Hello';

	// $encrypted = encryption::encode($message, 'my-key');

	$encrypted = 'TODO';

//--------------------------------------------------
// 3. Decrypt

	// $decrypted = encryption::decode($encrypted, $key_public);

	$decrypted = 'TODO';

//--------------------------------------------------
// Results

	echo debug_dump([
			$key_public,
			$encrypted,
			$decrypted,
		]);

?>