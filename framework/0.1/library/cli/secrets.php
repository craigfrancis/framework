<?php

// Check config-encrypt.php ... this might replace it.

// Check TODO: /private/secrets/

//--------------------------------------------------

// PRIME_CONFIG_KEY is unique to each server, never leaves it, and is only used to encrypt config values.

// If something needs encrypting, create a second key (that can be backed up), and that key is encrypted with PRIME_CONFIG_KEY.

// encryption::key_symmetric_create() should be updated to return a Base64 encoded JSON encoded object.

// So all keys, including PRIME_CONFIG_KEY can support key rotation, by having each one being stored against the creation date:
// {
//   '2020-01-02 09:58:24': 'KS2.9k3dY4Fb73fLHVkRPaQwM4wEwu8Rxrikf8yFPCZ3oxs',
//   '2020-03-08 12:20:05': 'KS2.zDYtuZd12Z4fanVakcjovCa22MaMfXMyfWJsYEun2oy',
// }

// encryption::key_symmetric_create() should accept an optional parameter for the old key (to append)

// Add encryption::key_remove() or encryption::key_cleanup() to remove old keys?

//--------------------------------------------------

// This script should...

// Start by checking if "/etc/prime-config-key" exists, and propose it's contents (or if run by root, create it automatically?)

// Check if the folder "/private/secrets/" exists, and if not, create it. Where it contains files only www-data can read.

// For each key in PRIME_CONFIG_KEY, there is a file, where that key can decrypt the values.

// The file names could be based on the hash of the key? via quick_hash_create()?
//    https://twitter.com/craigfrancis/status/1236638173091987456

// Contents use ini format, and are read back via parse_ini_file()

//--------------------------------------------------

// Options...
//
// - Option to store a new secret...
//      "./cli --secrets=add"
//      "./cli --secrets=add,name"
//      Take a name, and a value (it could be a key itself, e.g. copying from Live to Backup).
//
// - Option to create/store a new key...
//      "./cli --secrets=add-key"
//      "./cli --secrets=add-key,name"
//      Take a name only.
//      Also offer to "export", as a reminder to backup.
//
// - Option to export/import all secrets - for backup...
//      "./cli --secrets=export"
//      "./cli --secrets=import"
//      Ask for a password (implementation below).
//
// - For secrets that are themselves keys, option to key rotate (add new, then remove/cleanup old keys)?
//      "./cli --secrets=rotate"
//      "./cli --secrets=rotate,name"
//
// - Option to key rotate "/etc/prime-config-key" ...
//      "./cli --secrets=rotate-main"
//      Create new key
//      Re-encrypt secrets with new key (new file),
//         [manual steps follow?]
//      Replace key in "/etc/prime-config-key"
//      Check PRIME_CONFIG_KEY has been updated (Apache restart? maybe like "cli-opcache-clear"? check by passing hash, get back a pass/fail?)
//      Remove all old value files (if previous rotate failed there may be more than 1).

//--------------------------------------------------

// Password protect secrets when exporting

	function secrets_export($password, $secrets) {

		$config = [
				'size'      => SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_KEYBYTES,
				'salt'      => random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES),
				'limit_ops' => SODIUM_CRYPTO_PWHASH_OPSLIMIT_SENSITIVE,
				'limit_mem' => SODIUM_CRYPTO_PWHASH_MEMLIMIT_SENSITIVE,
				'alg'       => SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13,
				'nonce'         => random_bytes(SODIUM_CRYPTO_AEAD_CHACHA20POLY1305_IETF_NPUBBYTES),
			];

		// $config['limit_ops'] = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE; // TODO: Remove
		// $config['limit_mem'] = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE; // TODO: Remove

		$key = sodium_crypto_pwhash(
				$config['size'],
				$password,
				$config['salt'],
				$config['limit_ops'],
				$config['limit_mem'],
				$config['alg'],
			);

		$encrypted = [];

		foreach ($secrets as $name => $secret) {
			$encrypted[$name] = sodium_crypto_aead_chacha20poly1305_ietf_encrypt($secret, $config['nonce'], $config['nonce'], $key);
		}

		return json_encode([
				'config' => array_map('base64_encode', $config),
				'encrypted' => array_map('base64_encode', $encrypted),
			], JSON_PRETTY_PRINT);

	}

	function secrets_import($password, $data) {

		$data = json_decode($data, true);
		$config = array_map('base64_decode', $data['config']);
		$encrypted = array_map('base64_decode', $data['encrypted']);

		$key = sodium_crypto_pwhash(
				$config['size'],
				$password,
				$config['salt'],
				$config['limit_ops'],
				$config['limit_mem'],
				$config['alg'],
			);

		$secrets = [];
		foreach ($encrypted as $name => $value) {
			$secrets[$name] = sodium_crypto_aead_chacha20poly1305_ietf_decrypt($value, $config['nonce'], $config['nonce'], $key);
		}

		return $secrets;

	}

	$password = 'example-password';
	$secrets = [
			'db_pass'  => '12345',
			'api_pass' => '54321',
		];

	$data = secrets_export($password, $secrets);

	$secrets = secrets_import($password, $data);

?>