# NL-Tees (Laravel)

A Laravel app for nl-tees.com.

## Requirements
- PHP 8.2+
- Composer 2
- Node.js 18+
- npm or yarn
- Database (MySQL or PostgreSQL)
- Redis (optional, for cache/queue)
- OpenSSL / ext-intl / ext-mbstring / ext-pdo

## Setup
```bash
cp .env.example .env
composer install
php artisan key:generate
npm ci
npm run build    # or: npm run dev
php artisan migrate
php artisan serve
# Vite (if using hot reload)
npm run dev
php artisan cache:clear
php artisan config:clear
php artisan route:list
php artisan migrate --seed
npm ci && npm run build
php artisan optimize
Last updated: 2025-10-07
