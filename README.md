# Diástoles Art Plugin

Diástoles is a WordPress plugin for an anonymous, multilingual, smell-based collective artwork.

Participants enter through a single public page, answer short scent prompts in any language, keep a private return link, and discover anonymous affinities with other human fragments. The interface is multilingual, but participant words are preserved: the AI translates and relates them; it does not rewrite them as creative material.

## What the plugin does

- Creates the `[diastoles_experience]` shortcode.
- Lets participants join anonymously, with optional profile/context fields.
- Stores scent responses and optional explanations.
- Translates participant responses into the active interface languages.
- Extracts scent concepts, matices, semantic fields and emotional tones.
- Finds possible connections between responses.
- Supports moderation of responses and connections from `wp-admin`.
- Provides several collective-map views for the public experience.
- Exports research data as CSV/JSON ZIP files.
- Logs user-facing activity events for later analysis.

## Install

The latest installable ZIP is included here:

- [`dist/diastoles.zip`](dist/diastoles.zip)

In WordPress:

1. Go to `Plugins → Add New Plugin → Upload Plugin`.
2. Upload `dist/diastoles.zip`.
3. Activate `Diástoles`.
4. Create a page containing:

   ```text
   [diastoles_experience]
   ```

5. Open `Diástoles → AI settings`.
6. Use Mock mode for testing, or Live mode with an Anthropic API key.

## Configure AI

The plugin uses Anthropic from the WordPress server, never from browser JavaScript.

Recommended defaults:

- Routine processing model: Claude Haiku 4.5.
- Connection ranking model: Claude Sonnet 5.
- Embeddings: currently disabled.

You can configure the key in either place:

1. `wp-admin → Diástoles → AI settings → Anthropic API key`; or
2. server configuration / `wp-config.php`:

   ```php
   define( 'DIASTOLES_ANTHROPIC_API_KEY', 'your-key-here' );
   ```

The ZIP does not contain API keys. If a key is entered in the admin screen, it is stored encrypted in WordPress options as `diastoles_anthropic_key` with `autoload = false`.

## Privacy notes

Diástoles does not require real names, phone numbers or email addresses.

Participants may optionally provide:

- a pseudonym;
- profile/context details to diversify matches;
- an email address only for a future publication notice.

Publication emails are not sent to Anthropic and are excluded from the portable research export.

Participant session and recovery tokens are stored as hashes in the database. The private recovery link itself should still be treated as private by the participant.

## How connections are calculated

See:

- [`docs/connection-matching.md`](docs/connection-matching.md)

Short version:

1. A response is saved exactly as written.
2. A routine AI model translates and extracts structured signals.
3. The plugin cheaply preselects candidates using matices, semantic fields, themes, tones, profile tags and previous pair history.
4. A stronger AI model ranks only the shortlisted candidates.
5. Connections become `approved`, `pending`, `held` or `rejected` depending on thresholds and moderation.

The model is constrained to classify relationships and cite existing human words. It is not asked to invent participant fragments.

## Local development

Requirements:

- Docker
- Docker Compose
- Bash/zsh

Start local WordPress:

```bash
./tools/setup-local.sh
```

Then open:

```text
http://localhost:8080/
```

More details:

- [`LOCAL_TESTING.md`](LOCAL_TESTING.md)

## Verify

With the local environment running:

```bash
./tools/check.sh
```

## Build the installable ZIP

```bash
./tools/build-plugin.sh
```

The generated file is:

```text
dist/diastoles.zip
```

## Repository layout

```text
diastoles/              WordPress plugin source
dist/diastoles.zip      Installable plugin package
docs/                   Public documentation
tools/                  Local setup, checks and build scripts
compose.yaml            Local WordPress/MySQL environment
LOCAL_TESTING.md        Local testing instructions
```

## Credits

Photographs by [Paco Santamaría](https://www.pacosantamaria.es/).

Ideation by [Raúl Antón Cuadrado](https://remotefrog.com/).

Diástoles is a companion project to [Sístoles](https://sistoles.com/).

## License

GPLv2 or later, matching the WordPress plugin header.
