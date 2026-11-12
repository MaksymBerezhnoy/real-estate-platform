# Coding conventions - real-estate-platform

A real estate sales catalog for Kyiv built on WordPress as an engineering platform: Bedrock,
a Composer plugin with PSR-4 (`web/app/plugins/mb-real-estate`), a block theme
(`web/app/themes/mb-real-estate-theme`), Gutenberg blocks, a REST search endpoint backed by an
index table, and CI with linters and tests.

The project plan is `docs/project-plan.md`: stack, architecture, stages, day-by-day tasks and
the definition of done. Read it before changing architecture. The design prototype is in
`docs/prototype/` (four screens); theme markup follows it.

These rules are mandatory for all code written in this repository.

## The plan is a guide, not a fixed spec

- Small deviations (step order inside a task, class names, folder layout): decide, then mention
  them in the PR description.
- Large deviations (another technology, dropping a feature, a change to architecture or the data
  model): explain what changes, why and what it risks, then wait for the owner's confirmation.
- Whenever we deviate, update `docs/project-plan.md` in the same PR so the plan never drifts
  from the code.
- Day estimates are approximate. If a task takes noticeably longer, say so instead of cutting
  corners.

## Decisions already made

Changing any of these is a large deviation.

- Sales only, no rentals. Property types: apartment and house.
- Storage: post meta via `register_post_meta` is the source of truth; the index table is a
  derived copy for search. No ACF.
- Block theme with the design system in `theme.json`. No Timber, Blade, Sage, Vite, WPML,
  headless.
- Three stages: MVP, then map and AI search, then Multisite with an aggregator.
- Data comes from a WP-CLI generator and must be plausible (plan section on data and
  measurements). Agencies, phones and properties are fictional.

## Names and prefixes

Fixed before the first commit: the CPT key and meta keys are stored in the database.

| What | Name |
| --- | --- |
| Plugin, theme | `mb-real-estate`, `mb-real-estate-theme` |
| PHP namespace | `MB\RealEstate` |
| CPT | `mbre_property` (permalink base `/property/`) |
| Meta keys | `mbre_price`, `mbre_area`, ... |
| REST namespace | `mbre/v1` |
| Index table | `{$wpdb->prefix}mbre_property_index` |
| Blocks | `mbre/property-card`, `mbre/property-grid`, ... |
| Hooks, options | `mbre_*` |

- The `mbre` prefix goes only on identifiers that live in the global WordPress space. Inside
  the `MB\RealEstate` namespace classes and functions have no prefix.
- Never use the `mb_` prefix: it belongs to PHP mbstring functions.

## Language

- English only in every repository file: comments, identifiers, log messages, commit messages,
  PR descriptions, README, config files.
- Exception: `docs/project-plan.md` is in Russian.
- Site content and UI are Ukrainian and English (Polylang). User-facing strings in code are
  English source strings wrapped in translation functions.
- Chat with the owner is in Russian.

## PHP

- PHP 8.x, PSR-4 autoloading, WordPress Coding Standards (PHPCS) and PHPStan level 5.
- Use `array()` syntax, not `[]`.
- Use braces `{}`, never the alternative control syntax (`if: ... endif;`).
- Place `add_action()` and `add_filter()` calls after the function definition. For class
  methods, register hooks in a dedicated method called from the plugin bootstrap.
- PHPDoc descriptions are short: a few words or one sentence. Keep `@param` / `@return` tags.
  Design rationale belongs in the README or the plan, not in docblocks.
- No `_ts` suffix for timestamp variables; use the full `_timestamp` suffix.
- Security on every input and output: sanitize on input, escape on output, nonces and
  capability checks on every state-changing request, `permission_callback` on every REST route.

## PHPStan

- Fix errors in code; do not silence them. An ignore or a baseline entry is allowed only with a
  comment explaining why there is no other way.
- Never lower the level to pass the check.

## JavaScript

- `const` by default, `let` only when the variable is reassigned, never `var`.
- Build through `@wordpress/scripts` only.
- In React components reach DOM nodes through refs; state and props decide what is rendered.

## CSS / SCSS

- Blocks take colors, fonts and spacing only from `theme.json` through WordPress CSS variables.
- A class used as a JavaScript hook is separate from styling classes and has the `__js` suffix
  (`snake_case`, e.g. `filter_panel__js`). Never style a `__js` class, never select by a
  styling class.

## Git and pull requests

- Branches from `main`: `feature/...`, `fix/...`, `chore/...`. No direct pushes to `main`.
- One task is one branch and one PR of roughly 100-300 lines. Squash merge on green checks.
- Commit types in full uppercase: `FEAT:`, `FIX:`, `REFACTOR:`, `CHORE:`, `DOCS:`, `TEST:`.
- Commit and PR titles are written for a reviewer: no "wip", "fix", "update".
- PR description: what was done, why, how to verify. Flag debatable places without waiting to
  be asked.
- Do not add `Co-Authored-By` trailers or the "Generated with Claude Code" footer.

## CI

- Required on every PR: PHPCS (WPCS) and PHPStan level 5 with `szepeviktor/phpstan-wordpress`.
- Config lives in the repository (`phpcs.xml.dist`, `phpstan.neon.dist`); the same commands run
  locally through composer scripts.
- Added later per the plan: PHPUnit, Playwright, k6, Lighthouse CI.

## The repository is public

- No secrets in history. `.env` is never committed, only `.env.example`. CI keys live in GitHub
  Secrets. The language model API key (stage 2) never reaches code, logs or the browser.
- No code, data or client names from commercial work.
- No real personal data.
- Every third-party asset has a recorded source and license: photos, OpenStreetMap data, fonts.
  Generated property descriptions are marked as generated.
- Never commit anything from `docs/private/`.
