# BeastFeedbacks

[日本語](README.md)

BeastFeedbacks is a WordPress plugin for collecting visitor feedback through
blocks added in the block editor. It provides likes, choice-based voting, and
customizable surveys. Responses can be reviewed in WordPress Admin and exported
as CSV.

## Features

- A Like button with a cumulative count
- Choice-based voting with multiple buttons
- Survey forms combining text fields, text areas, and choice fields
- Configurable required fields, widths, placeholders, and other field options
- Admin filters for feedback type and source page
- CSV export of response data
- Japanese translations

## Requirements

- WordPress 6.8 or later
- PHP 8.1 or later

Building or developing from source also requires:

- Node.js 24 (the version used in CI)
- npm
- Composer
- Docker (used by `wp-env` for local WordPress environments and tests)

## Installation

### Using a distribution ZIP

1. Upload the plugin ZIP from the Plugins screen in WordPress Admin.
2. Activate BeastFeedbacks.

Alternatively, extract the plugin into `wp-content/plugins/beastfeedbacks` and
activate it from WordPress Admin.

### Building a ZIP from source

```bash
git clone https://github.com/ytsuyuzaki/beastfeedbacks.git
cd beastfeedbacks
npm ci
npm run build
npm run plugin-zip
```

Upload the generated `beastfeedbacks.zip` file to WordPress.

## Usage

1. Open a post or page in the block editor.
2. Add the required block from the BeastFeedbacks category.
3. Customize its questions, choices, and input types, then publish the page.
4. Review responses from the BeastFeedbacks screen in WordPress Admin.
5. Use the Export button on the response list to download the data as CSV.

### Available blocks

- **Like button**: Displays a Like button and the cumulative count for the page.
- **Choice voting**: Creates a single-answer vote using buttons.
- **Survey Form**: The parent container for a survey.
- **Survey Input**: Adds a text input or text area inside a Survey Form.
- **Survey Choice**: Adds radio buttons, checkboxes, or a select field inside a
  Survey Form.

## Stored data

Responses are stored in the WordPress database as entries of the
`beastfeedbacks` custom post type. In addition to the submitted response, the
plugin records the source post, submission time, IP address, and User-Agent.
Operate the plugin in accordance with your privacy policy, applicable laws, and
data-retention policy.

## Development

Install the dependencies:

```bash
npm ci
composer install
```

Start a development build for the blocks:

```bash
npm start
```

Generate production assets:

```bash
npm run build
```

Start the local WordPress environment with `wp-env`:

```bash
npm run wp-env:start
```

Stop it when finished:

```bash
npm run wp-env:stop
```

The default `.wp-env.json` uses WordPress 7.1, PHP 8.5, and port 8889 for the
test environment.

## Linting and tests

Run formatting checks and the JavaScript, CSS, Markdown, PHP, and WordPress
Coding Standards linters together:

```bash
npm run lint
```

The complete test command builds the plugin, starts `wp-env`, runs PHPUnit, and
runs the Playwright end-to-end tests. Docker must be running.

```bash
npm test
```

Individual checks are also available:

```bash
npm run test:version
npm run wp-env:test
npm run test:e2e
```

## Project structure

```text
beastfeedbacks.php  Plugin entry point
includes/           PHP for Admin, submissions, and block registration
src/                Gutenberg block source code
public/             Admin CSS and JavaScript
languages/          Translation files
tests/phpunit/       PHPUnit tests
tests/e2e/           Playwright end-to-end tests
```

The `build/` directory is generated from `src/` by `npm run build` and loaded at
runtime.

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)
