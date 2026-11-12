# Real Estate Platform

A real estate sales catalog for Kyiv built on WordPress as an engineering platform: Bedrock,
a Composer plugin with PSR-4 autoloading, a block theme, Gutenberg blocks and a REST search
endpoint backed by an index table.

The project is at an early stage. The plan is in [docs/project-plan.md](docs/project-plan.md)
(in Russian), the design prototype is in [docs/prototype/](docs/prototype/).

## Getting started

Requirements: [DDEV](https://ddev.com) with a Docker provider.

```bash
git clone https://github.com/MaksymBerezhnoy/real-estate-platform.git
cd real-estate-platform
cp .env.example .env
ddev start
ddev composer install
ddev wp core install --url="$(ddev exec printenv DDEV_PRIMARY_URL)" --title="Real Estate Platform" \
  --admin_user=admin --admin_email=admin@example.com --prompt=admin_password
```

Replace the `generateme` salts in `.env` with values from https://roots.io/salts.html. The site
opens at the URL printed by `ddev start`; the admin is at `/wp/wp-admin/`.

WordPress core, third-party plugins and themes are Composer dependencies: they are installed
into `web/wp/` and `web/app/` and are not stored in Git. To add a plugin, run
`ddev composer require wp-plugin/<slug>`.

## Code quality

Every pull request runs two required checks in GitHub Actions. The same commands run locally:

```bash
ddev composer lint      # PHPCS with WordPress Coding Standards
ddev composer analyse   # PHPStan level 5 with the WordPress extension
ddev composer check     # both
```

WordPress Coding Standards apply to the project's own plugin and theme in `web/app/`. The
Bedrock skeleton keeps its upstream PSR-12 style and is not checked.

## License

The code is licensed under [GPL-2.0-or-later](LICENSE). Third-party assets (photos,
OpenStreetMap data, fonts) keep their own licenses and are listed with their sources as they
are added.

The project skeleton (`config/`, `web/index.php`, `web/wp-config.php`, the mu-plugin autoloader)
comes from [Bedrock](https://roots.io/bedrock/) by Roots, MIT license:
[licenses/bedrock-LICENSE.md](licenses/bedrock-LICENSE.md).
