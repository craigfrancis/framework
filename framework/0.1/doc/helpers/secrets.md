
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
    readfile('/path/to/uploaded/images/' . $_GET['file']);
    exit();

Where the file name is passed in via the URL, and instead of it being set to "123.gif", an attacker could set it to "../../../config.php".

This mistake could happen anytime a user supplied value is used with `readfile()`, `file_get_contents()`, `include()`, etc;

Or even via an SQL injection vulnerability, when the database is running on the same server, and the attacker is able to use `LOAD_FILE()`.

This is why we assume there a mistake which allows the attacker to read (or write to) any file the web-server account (e.g. "www-data") can access.

---

## Setup

Each server will have it's own `PRIME_CONFIG_KEY`.

It's typically stored in `/etc/prime-config-key`.

This key should **only** be used by the secrets helper - If you need something to be encrypted, create your own key, use the secrets helper to store it (and make a backup).

On `demo` or `live`, the file is only readable by `root`. On `stage` this file may be readable by the developers account.

This encryption key is provided to PHP as an environment variable.

It can be setup via:

    ./cli --secrets=init

Where it will ask for your sudo password to set the appropriate permissions.

Or, if you want to do this yourself, use `encryption::key_symmetric_create()` to get a symmetric encryption key; then make sure PHP can see it via `getenv('PRIME_CONFIG_KEY')`.

---

## New Values

During development, starting on `stage` (development server), the developer will using one of these commands:

    ./cli --secrets=add
    ./cli --secrets=add,name
    ./cli --secrets=add,name,type

Where `type` is either 'key' or 'value'.

The name and type is stored in `/app/library/setup/secrets.json`.

The value is then encrypted, and stored to a file in `/private/secrets/`.

---

## New Values on Demo/Live

When uploading the project via:

    ./cli --upload=demo
    ./cli --upload=live

The upload process will notice when secrets are missing, and prompt for the value.

Or, to just run this check separately:

    ./cli --secrets=check

As the `/etc/prime-config-key` is not readable, you will either need to set config 'output.domain' (so the values can be encrypted by a web request), manually provide the key, or use sudo.

---

## Using Values

This is simply done via:

    $value = secrets::get();

---

## Removing Values

On stage, you simply run:

    ./cli --secrets=remove
    ./cli --secrets=remove,name

See below on how old keys are removed during key rotation.

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

TODO [secrets] - Not complete yet... also, can the list of current key ID's be exported (inc created/edited dates), so the old keys can be removed.

1. Add a new key to the list

    ./cli --secrets=add,my-key

2. Start using new key

3. Optionally update any existing values using the old key, then remove the old key.

    ./cli --secrets=remove,my-key,key,1

---

## Main Key Rotation

    ./cli --secrets=rotate-main

TODO [secrets]

- Create new key
- Re-encrypt secrets with new key (new file),

- [any manual steps? other websites?]

- Replace key in "/etc/prime-config-key", with a mv to a backup file using the current timestamp?
- Check `PRIME_CONFIG_KEY` has been updated (Apache restart? like "framework-opcache-clear"? check by passing hash, get back a pass/fail?)
- Remove all old value files (if previous rotate failed there may be more than 1).

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

    644 /www/.../app/library/setup/secrets.json

    755 /www/.../private/secrets/
    644 /www/.../private/secrets/sha256-ec5770f0d969dd27
    644 /www/.../private/secrets/sha256-32e9bf1ce9aa0672
