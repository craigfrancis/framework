
# Secrets helper

Works with the [encryption helper](../../doc/helpers/encryption.md), and is used to record keys, passwords, etc.

If you have a password (e.g. for the database), the secrets helper will store that for you.

If you have something to encrypt (e.g. some files), create an encryption key for that purpose, then use the secrets helper to store that key (and make a backup).

---

## Why

You could store your secrets in a file on your server, in plain text.

But these files are likely to be copied many times (especially when using a version control system, or when you have backups).

This makes it very difficult to be sure the secrets remain secret.

Also, we should always assume we have made a mistake in our code.

A classic one is something like this:

    header('Content-Type: image/jpeg');
    readfile('/path/to/uploaded/images/' . $_GET['file']); // INSECURE
    exit();

Where the file name is passed in via the URL, and instead of it being set to "123.gif", an attacker could set it to "../../../config.php".

This mistake could happen anytime a user supplied value is used with `readfile()`, `file_get_contents()`, `include()`, etc;

Or even via an SQL injection vulnerability, when the database is running on the same server, and the attacker might be able to use `LOAD_FILE()`.

This is why we assume there a mistake which allows the attacker to read (or write to) any file the web-server account (e.g. "www-data") can access.

---

## Setup

Each server will have it's own `PRIME_CONFIG_KEY`.

It's typically stored in `/etc/prime-config-key`.

This key should **only** be used by the secrets helper - If you need something to be encrypted, get the secrets helper to create a key for you.

On `demo` or `live`, this file is only readable by `root`. On `stage` this file may be readable by the developers account.

This encryption key is provided to PHP as an environment variable.

It can be setup via:

    ./cli --secrets=check

It will ask for your sudo password to set the appropriate permissions.

Or, if you want to do this yourself, use `encryption::key_symmetric_create()` to get a symmetric encryption key; then make sure PHP can see it via `getenv('PRIME_CONFIG_KEY')`.

---

## New Values

Update your projects config file:

    /app/library/setup/config.php

To add to the `$secrets` array, e.g.

    $secrets['db.pass'] = ['type' => 'str'];

    $secrets['service.key'] = ['type' => 'key'];

Then re-run:

    ./cli --secrets=check

The you will be asked for any missing values, which are encrypted, and written to a file in `/private/secrets/`.

---

## New Values on Demo/Live

When uploading the project via:

    ./cli --upload=demo
    ./cli --upload=live

The upload process will find missing secrets, and either prompt for the value, or generate a key.

Or, to just run this check separately:

    ./cli --secrets=check

As the `/etc/prime-config-key` is not readable, you will either need to:

- Set config 'output.domain' (so the values can be encrypted by a web request);
- Manually provide the key;
- Or, use sudo.

---

## Using Values

This is simply done via:

    $value = secrets::get('variable.name');

---

## Backups

Backups will be exported via asymmetric encryption; as in, using a public and secret key.

The secret key is not stored on the server - maybe it's only available in your password manager, or somewhere safe in your disaster recovery system.

The public key is provided to the export process, and can be kept on the server.

To generate a new key pair, simply run this command:

    ./cli --secrets=export

To create an export, append a comma, then the public key:

    ./cli --secrets=export,KA2P.0.Gd3Y...GVDI

You will probably want to store this in a file, so maybe use something like:

    ./cli --secrets=export,KA2P.0.Gd3Y...GVDI > /path/to/file

To import, which you should be checking frequently to prove that it's working, use:

    ./cli --secrets=import,KA2S.0.825T...mqqc < tmp

---

## Key Rotation

For keys you have stored with the secrets helper, like those to encrypt files...

TODO [secrets-keys] - Not complete yet... also, can the list of current key ID's be exported (inc created/edited dates), so the old keys can be removed.

1. Add a new key to your config file:

    $secrets['service.key'] = ['type' => 'key'];

2. Run the following to generate the first key:

    ./cli --secrets=check

To rotate the key:

1. Create a new key that can be used; the old key IDs and new key ID will be shown (or `key-list` can be used):

    ./cli --secrets=key-create,service.key

    New Key: 9
    Old Keys: 7, 8

	./cli --secrets=key-list,service.key

    New Key: 9
    Old Keys: 7, 8

2. Update any values that have been encrypted with the old key, will have a prefix such as "ES2.7..."; this RegEx should find values using the old keys 7 and 8:

    ^E(S|AS|AP|AT)[0-9]+\.(7|8)\.

3. Delete the old key with:

    ./cli --secrets=key-delete,service.key,8

---

## Main Key Rotation

    ./cli --secrets=rotate-main

TODO [secrets-keys]

- Create new key
- Re-encrypt secrets with new key (new file),

- [any manual steps? other websites?]

- Replace key in "/etc/prime-config-key", with a mv to a backup file using the current timestamp?
- Check `PRIME_CONFIG_KEY` has been updated?
- Remove all old value files (if previous rotate failed there may be more than 1).
- Update "backup.key-import"

Support servers with multiple projects using this same key.

---

## How it Works

Each server has it's own `PRIME_CONFIG_KEY` which is unique to the server, and never leaves it (not even in a backup).

This key is only used to encrypt value/key secrets.

None of the files are editable by the www-data user.

While it would be technically possible to make the files only readable by the www-data user (chmod 400), this provides little security benefit (the developer account can already edit PHP scripts), and makes general development and server admin tricky (e.g. backups).

The data file name is based on a partial hash of the current `PRIME_CONFIG_KEY` value. This allows for simple key rotation, and to make it tricky for an attacker to use an arbitrary file read vulnerability (as in, they can read any file they specify, but they can't list the contents of a folder).

This results in a file structure such as:

    400 /etc/prime-config-key

    755 /www/.../private/secrets/
    755 /www/.../private/secrets/data/
    644 /www/.../private/secrets/data/sha256-ec5770f0d969dd27
    644 /www/.../private/secrets/data/sha256-32e9bf1ce9aa0672
