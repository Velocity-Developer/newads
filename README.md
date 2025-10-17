# Adsvelo

A modern Laravel + Inertia + Vue + Fortify starter with opinionated defaults: auth ready out of the box, development login prefill, demo user seeding, and a home route that conditionally directs guests to login and authenticated users to the dashboard.

## Stack

- Laravel (Fortify for authentication)
- Inertia + Vue 3 (TypeScript)
- Vite (build/dev)
- Tailwind CSS
- Pest + PHPUnit (testing)
- GitHub Actions (lint + tests)

## Requirements

- PHP 8.2+ and Composer
- Node.js 18+ and npm
- MySQL/MariaDB
- Git

## Quick Start

1) Copy environment config:
- Create `.env` from `.env.example` and adjust values.
- Do not commit `.env` (it’s already ignored).

2) Basic `.env` setup:
- App:
  - `APP_ENV=local`
  - `APP_DEBUG=true`
  - `APP_URL=http://localhost:8000`
- Generate app key:
  ```bash
  php artisan key:generate
  ```
- Database (example for local MySQL):
  - `DB_CONNECTION=mysql`
  - `DB_HOST=127.0.0.1`
  - `DB_PORT=3306`
  - `DB_DATABASE=adsvelo`
  - `DB_USERNAME=root`
  - `DB_PASSWORD=your_db_password`
- Mail (optional, adjust to your SMTP provider):
  - `MAIL_MAILER=smtp`
  - `MAIL_HOST=smtp.yourprovider.com`
  - `MAIL_PORT=587`
  - `MAIL_USERNAME=your_inbox@example.com`
  - `MAIL_PASSWORD=your_mail_app_password`
  - `MAIL_ENCRYPTION=tls`
  - `MAIL_FROM_ADDRESS=your_inbox@example.com`
  - `MAIL_FROM_NAME="${APP_NAME}"`

3) Development login prefill:
- Active only in local environment (`app()->isLocal()`).
- Add to `.env`:
  ```
  DEMO_LOGIN_EMAIL=demo@example.com
  DEMO_LOGIN_PASSWORD=password
  ```

4) Install dependencies:
```bash
composer install
```
```bash
npm install
```

5) Migrate and seed:
- Reload config so `.env` is picked up:
```bash
php artisan config:clear
```
- Run migrations and seeders:
```bash
php artisan migrate --seed
```
- Seeder is idempotent:
  - Creates or updates `test@example.com` and the demo user (`DEMO_LOGIN_EMAIL`), hashes password via model casts, verifies email, and disables 2FA for smooth local login.

6) Run the app:
- Laravel dev server:
```bash
php artisan serve
```
- Vite dev server:
```bash
npm run dev
```
- Visit `http://localhost:8000`.

## Authentication & Prefill

- The login form will prefill `email` and `password` with `DEMO_LOGIN_EMAIL` and `DEMO_LOGIN_PASSWORD` when running locally.
- Demo user is created/updated by the seeder with verified email and 2FA disabled to avoid OTP on local dev.

## Home Routing

- Root `/` uses `auth` middleware and redirects to `/dashboard`.
  - Guests are redirected to Fortify `/login`.
  - Authenticated users go to `/dashboard` (with `verified`).

## NPM Scripts

- `npm run dev` — start Vite in development mode.
- `npm run build` — build assets for production.
- `npm run build:ssr` — build client + SSR.
- `npm run lint` — ESLint with auto-fix.
- `npm run format` — Prettier format for `resources/`.
- `npm run format:check` — Prettier check.

## Testing

- Run tests (Windows):
```bash
vendor\bin\pest.bat
```
or
```bash
vendor\bin\phpunit.bat
```

## CI (GitHub Actions)

- Workflows `lint.yml` and `tests.yml` run automatically on push.
- Never commit `.env`. Use repository `secrets` for CI if environment variables are needed.

## Security & Best Practices

- Keep credentials out of version control. Use `.env.example` to document required variables.
- `DEMO_LOGIN_*` should be used for local development only; disable for staging/production.
- If enabling 2FA in production, remove 2FA disabling from seeder for non-demo accounts.

## Troubleshooting

- Duplicate email during seeding:
  - Seeder is idempotent; re-run `php artisan db:seed` after fixes.
- Email not sending:
  - Check Windows firewall/antivirus, SMTP port (587/465), and whether your provider requires an App Password.
- Assets not loading:
  - Ensure `npm run dev` is running in development or use `npm run build` for production.