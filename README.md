# Ordermade Kanri

Laravel-based web application for the `ordermade.sakura.ne.jp/kanri` management area.

## What Is In This Repo

- Laravel 10 application code
- Breeze authentication scaffolding
- Tailwind + Vite frontend assets
- Laravel Sail-based local development setup
- PWA assets used by the `/kanri` deployment

## Main Functional Areas

- User registration and profile management
- Payment management
- Expense category and disbursement management
- Game management
- Batting order, batting results, and batting stats
- Pitching stats
- Steal tracking
- Contact form and email templates

## Quick Start

1. Start Docker Desktop.
2. Bring up Sail:
   `./vendor/bin/sail up -d`
3. Run migrations:
   `./vendor/bin/sail artisan migrate`
4. Install frontend dependencies:
   `npm install`
5. Start Vite:
   `npm run dev`
6. Open:
   `http://localhost:8080`

Auxiliary services:

- phpMyAdmin: `http://localhost:8081`
- Mailpit: `http://localhost:8026`

## Important Notes

- The production server deployment is split between `~/ordermade` and `~/www/kanri`.
- The server checkout had significant Git drift and a compromised `public/index.php`; this local workspace has already been cleaned for development.
- `.env` in this workspace is local-only. Use `.env.example` as the committed template.

## Documentation

- [AGENTS.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/AGENTS.md)
- [docs/server-analysis.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/server-analysis.md)
- [docs/local-setup.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/local-setup.md)
- [docs/github-migration.md](/Users/yamamotoshuma/work/kusayakyu/ordermade/docs/github-migration.md)

## Sakura Deploy

After cloning this repository onto the server into a source directory such as `~/ordermade-repo`, deploy with:

`./deploy/sakura/deploy.sh`
