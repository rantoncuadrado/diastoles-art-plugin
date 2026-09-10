# Security and API keys

The installable plugin ZIP does not contain Anthropic API keys.

## Where the API key is configured

The site owner can configure Anthropic in:

```text
wp-admin → Diástoles → AI settings
```

The field is:

```text
Anthropic API key
```

It is rendered as a password input. Leaving it blank keeps the existing key.

## Where the key is stored

If entered in wp-admin, the key is stored in the WordPress options table:

```text
diastoles_anthropic_key
```

The value is encrypted before storage with AES-256-GCM. The encryption key is derived from:

```php
wp_salt( 'secure_auth' )
```

The option is stored with `autoload = false`.

## Server constant alternative

For stricter hosting setups, define the key outside the plugin:

```php
define( 'DIASTOLES_ANTHROPIC_API_KEY', 'your-key-here' );
```

This can live in `wp-config.php` or another private server configuration layer.

## What is safe to share

Usually safe:

- this repository;
- `dist/diastoles.zip`;
- the plugin source code.

Do not share publicly:

- WordPress database dumps;
- full hosting backups;
- `wp-config.php`;
- server environment variables;
- participant exports unless you intend to share those research data.

## Participant tokens

Anonymous session and recovery tokens are generated with `random_bytes(32)`.

The database stores hashed versions using:

```php
hash_hmac( 'sha256', $token, wp_salt( 'auth' ) )
```

The private recovery URL itself should be treated as private by the participant, because anyone with that link can return to that anonymous session.
