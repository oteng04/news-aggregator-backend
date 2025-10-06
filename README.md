   # News Aggregator Backend

   A Laravel-based news aggregation system that fetches articles from multiple news APIs and provides a unified REST API.

   ## Features

   - **Multi-Source News Fetching**: Integrates with News API, The Guardian, and New York Times
   - **RESTful API**: Clean API endpoints for articles, sources, and categories
   - **Admin Panel**: Filament-based admin interface for content management
   - **Queue Processing**: Asynchronous article fetching using Laravel Horizon
   - **Caching**: Redis-based caching for improved performance
   - **Docker Support**: Containerized development environment

   ## Tech Stack

   - **Laravel 11.x** - PHP Framework
   - **PHP 8.2** - Programming Language
   - **MySQL 8.0** - Database
   - **Redis** - Caching & Queues
   - **Docker** - Containerization
   - **Filament 3.x** - Admin Panel

   ## Installation

   ### Prerequisites
   - Docker & Docker Compose
   - Git

   ### Setup
   ```bash
   git clone https://github.com/oteng04/news-aggregator-backend.git
   cd news-aggregator-backend
   cp .env.example .env
   # Add your API keys to .env
   docker compose up -d
   docker compose exec app php artisan migrate
   docker compose exec app php artisan make:filament-user
   ```

   ## API Endpoints

   - `GET /api/articles` - List articles
   - `GET /api/articles/search` - Search articles
   - `GET /api/articles/{id}` - Get single article
   - `GET /api/sources` - List sources
   - `GET /api/categories` - List categories

   ## Commands

   - `php artisan news:fetch` - Manually fetch articles
   - `php artisan horizon` - Start queue worker



  