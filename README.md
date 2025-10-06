   # News Aggregator Backend

   A Laravel-based news aggregation system that fetches articles from multiple news APIs and serves them through a RESTful API.

   ## Features
   - Multi-source news aggregation
   - RESTful API endpoints
   - Admin panel with Filament
   - Queue-based processing
   - Docker support

   ## Tech Stack
   - Laravel 10.x
   - PHP 8.1+
   - MySQL
   - Redis
   - Docker

   ## Installation

   ```bash
   git clone https://github.com/oteng04/news-aggregator-backend.git
   cd news-aggregator-backend
   composer install
   cp .env.example .env
   php artisan key:generate
   ```
 