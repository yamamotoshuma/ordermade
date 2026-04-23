# GitHub Migration

## Current Situation

- The production server previously had Git metadata, but it was not a reliable source of truth.
- The live code existed mostly as uncommitted working tree changes.
- The local workspace was imported without the production `.git` metadata on purpose.
- This document is sanitized for public repositories and avoids production hostnames, users, and concrete server paths.

## Recommended Source Of Truth

Use this local workspace as the new baseline after review.

Reasons:

- the production working tree had substantial drift
- production-only secrets were removed from local `.env`
- a compromised front controller was cleaned locally
- local docs now describe the split deployment shape without exposing production details

## Recommended Migration Steps

1. Review the local workspace and docs.
2. Initialize or continue from the cleaned Git repository locally.
3. Keep `.env` untracked and commit `.env.example` only.
4. Commit the cleaned application state.
5. Create or update the GitHub repository after any required backup.
6. Add or verify the `origin` from the local workspace and push.
7. Change the deployment process so production updates come from GitHub or an explicit deploy script, not ad hoc server edits.

## Deployment Considerations

Production deployment must handle two layers:

- Laravel application files
- public web root files

Do not assume the Laravel app's internal `public/` directory is directly exposed in production.

## Suggested Server Layout

Use three distinct concerns on the server:

- clean Git checkout used for `git pull`
- live Laravel application directory
- live public web root

Concrete names and paths should be kept in private operations notes or environment variables.

The repository includes a generic deploy script that can sync from a clean checkout into the two live locations when destination paths are provided via environment variables.

Because the production server does not build frontend assets, keep `public/build` committed as part of the deployable tree unless the server build strategy changes.

## Server Cleanup Recommendation

Before switching to the new Git flow:

- back up the live Laravel application directory
- back up the live public web root
- inspect and clean the suspicious front controller
- remove or archive old production Git metadata only after the new baseline is confirmed

## Future Improvement

Keep deployment small and explicit:

1. pull the app repo into a clean checkout
2. sync application files into the live app directory
3. sync `public/` into the live public directory
4. run any required artisan migration and cache-clear commands

That will prevent the same Git drift from reappearing.
