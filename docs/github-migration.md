# GitHub Migration

## Current Situation

- The production server checkout has a `.git` directory.
- That Git history is effectively unusable as a source of truth because the live code exists mostly as uncommitted working tree changes.
- The local workspace was imported without the remote `.git` directory on purpose.

## Recommended Source Of Truth

Use this local workspace as the new baseline after review.

Reasons:

- the server working tree had substantial drift
- production-only secrets were removed from local `.env`
- the compromised `public/index.php` was cleaned locally
- local docs now describe the split deployment shape

## Recommended Migration Steps

1. Review the local workspace and docs.
2. Initialize a fresh Git repository locally.
3. Keep `.env` untracked and commit `.env.example` only.
4. Commit the cleaned application state.
5. Create a new GitHub repository or replace the old remote after backup.
6. Add the new `origin` from the local workspace and push.
7. Change the deployment process so production updates come from GitHub or an explicit deploy script, not ad hoc server edits.

## Deployment Considerations

A deploy to Sakura must handle both layers:

- application code into `~/ordermade`
- public files into `~/www/kanri`

Do not assume `~/ordermade/public` alone is the public root in production.

## Suggested Server Layout

Use three distinct concerns on the server:

- `~/ordermade-repo`: clean Git checkout used for `git pull`
- `~/ordermade`: live Laravel application directory
- `~/www/kanri`: live public web root

The repository now includes `deploy/sakura/deploy.sh` to sync from `~/ordermade-repo` into the two live locations.

## Server Cleanup Recommendation

Before switching to the new Git flow:

- back up `~/ordermade`
- back up `~/www/kanri`
- inspect and clean the suspicious `~/ordermade/public/index.php`
- remove or archive the old `.git` metadata on the server only after the new baseline is confirmed

## Future Improvement

Once the new GitHub repository exists, add a small deploy script that:

1. pulls the app repo into `~/ordermade`
2. syncs `public/` into `~/www/kanri`
3. runs any required artisan cache clear/rebuild commands

That will prevent the same Git drift from reappearing.
