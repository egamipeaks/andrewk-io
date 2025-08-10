# Andrew's Personal Website - Project Context

## Overview
This is Andrew's personal website built with Laravel 11, featuring a blog, portfolio/work showcase, and an admin panel for client and invoice management using Filament.

## Tech Stack
- **Backend**: Laravel 11 (PHP 8.2+)
- **Admin Panel**: Filament 3.0
- **Frontend**: Blade templates with Tailwind CSS
- **Build Tools**: Vite
- **Database**: MySQL/PostgreSQL (Laravel migrations)
- **Package Management**: Composer (PHP), NPM (JavaScript)
- **Additional Tools**: 
  - Pizzazz (page caching package)
  - Laravel Ray (debugging)
  - Laravel Sanctum (API authentication)

## Project Structure
```
website/
├── app/                  # Laravel application code
│   ├── Filament/        # Admin panel resources (Clients, Invoices)
│   ├── Http/            # Controllers and middleware
│   ├── Mail/            # Email templates (Invoice emails)
│   └── Models/          # Eloquent models (Client, Invoice, InvoiceLine, etc.)
├── resources/
│   ├── blog/            # Markdown blog posts
│   ├── views/           # Blade templates
│   └── css/js/          # Frontend assets
├── routes/              # Application routes
├── database/            # Migrations and seeders
└── public/              # Public assets
```

## Key Features
1. **Public Website**
   - Homepage (welcome.blade.php)
   - Work/Portfolio page (work.blade.php)
   - Blog system with Markdown support (BlogController)
   - Dark mode support
   - Page caching via Pizzazz

2. **Admin Panel (Filament)**
   - Client management (CRUD)
   - Invoice management with line items
   - Invoice email sending functionality
   - Relationship management between clients and invoices

## Development Commands
```bash
# Install dependencies
composer install
npm install

# Development server
php artisan serve
npm run dev

# Build assets
npm run build

# Run migrations
php artisan migrate

# Code quality
./vendor/bin/pint  # Laravel Pint for PHP formatting

# Testing
php artisan test
```

## Database Schema
- **users**: Authentication
- **clients**: Customer records
- **invoices**: Invoice headers linked to clients
- **invoice_lines**: Individual invoice items
- **invoice_email_sends**: Email send history

## Recent Updates
- Fixed text visibility in dark mode on homepage
- Updated pizzazz page caching configuration
- Added page caching with Ray debugging integration
- Moved social links to footer
- Added work/portfolio page

## Environment Setup
- Uses Laravel Herd for local development
- Git repository on master branch
- Vite for asset compilation and hot reload

## Notes for AI Assistants
- Always check existing code patterns before implementing new features
- Follow Laravel conventions and best practices
- Use Filament components for admin panel modifications
- Maintain consistent Tailwind CSS styling
- Test dark mode compatibility for any UI changes
- Use existing mail templates and components when possible
- Pizzazz handles page caching - check config/pizzazz.php for settings