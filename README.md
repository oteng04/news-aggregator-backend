# News Aggregator Backend

A Laravel-based news aggregation system that integrates multiple news sources and provides a clean, scalable architecture for content management.

## Features

- Multi-source news aggregation (NewsAPI, The Guardian, New York Times)
- RESTful API with comprehensive endpoints
- Admin dashboard with Filament
- Background queue processing with Laravel Horizon
- Docker containerization for development and production
- Clean architecture with repository pattern and service layer

## Tech Stack

- Laravel 11.x
- PHP 8.2
- MySQL 8.0
- Redis
- Docker & Docker Compose
- Nginx (production)
- Supervisor (production)

## Setup Instructions

### Prerequisites

- Docker and Docker Compose
- Git

### Development Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/oteng04/news-aggregator-backend.git
   cd news-aggregator-backend
   ```

2. **Start the development environment**
   ```bash
   docker compose up -d
   ```

3. **Install dependencies and setup database**
   ```bash
   docker compose exec app composer install
   docker compose exec app php artisan migrate
   docker compose exec app php artisan db:seed
   ```

4. **Create admin user**
   ```bash
   docker compose exec app php artisan make:filament-user
   ```

5. **Access the application**
   - Application: http://localhost:8000
   - Admin panel: http://localhost:8000/admin
   - API documentation: http://localhost:8000/api/documentation

### Production Setup

1. **Build and start production containers**
   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

2. **Run database migrations**
   ```bash
   docker compose -f docker-compose.prod.yml exec app php artisan migrate
   ```

3. **Configure storage and caching**
   ```bash
   docker compose -f docker-compose.prod.yml exec app php artisan storage:link
   docker compose -f docker-compose.prod.yml exec app php artisan config:cache
   docker compose -f docker-compose.prod.yml exec app php artisan route:cache
   docker compose -f docker-compose.prod.yml exec app php artisan view:cache
   ```

4. **Access production application**
   - Application: http://localhost
   - Admin panel: http://localhost/admin
   - Queue monitoring: http://localhost/horizon

## API Endpoints

### Articles
- `GET /api/articles` - List articles with pagination and filtering
- `GET /api/articles/{id}` - Get specific article
- `GET /api/articles/search` - Search articles by query
- `POST /api/articles` - Create new article (admin only)
- `PUT /api/articles/{id}` - Update article (admin only)
- `DELETE /api/articles/{id}` - Delete article (admin only)

### Sources & Categories
- `GET /api/sources` - List all news sources
- `GET /api/categories` - List all categories
- `GET /api/authors` - List all authors

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure the following:

```env
APP_NAME="News Aggregator"
APP_ENV=local
APP_KEY=  # Generate with: php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=news_aggregator_db
DB_USERNAME=news_admin
DB_PASSWORD=news_pass_04

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Queue & Cache
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

# News API Keys
NEWSAPI_API_KEY=your_newsapi_key
GUARDIAN_API_KEY=your_guardian_key
NYT_API_KEY=your_nyt_key
```

## Project Structure

```
├── app/
│   ├── Services/           # News source integration services
│   ├── Repositories/       # Data access layer
│   ├── Filament/          # Admin panel components
│   └── Http/Controllers/  # API controllers
├── config/                # Laravel configuration files
├── database/
│   ├── migrations/       # Database schema migrations
│   └── seeders/         # Database seeders
├── docker/               # Production container configurations
├── routes/              # API and web routes
├── tests/              # Test suites
└── docker-compose.yml  # Development environment
```

## Testing

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test types
docker compose exec app php artisan test --testsuite=Unit
docker compose exec app php artisan test --testsuite=Feature
```

## Development Commands

```bash
# Fetch news articles
docker compose exec app php artisan news:fetch

# Clear cache
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear

# Run queue worker (development)
docker compose exec app php artisan queue:work
```

## Architecture Decisions

### Repository Pattern
Used for clean data access abstraction and testability.

### Service Layer
Business logic is encapsulated in service classes, keeping controllers thin.

### Queue Processing
News fetching is handled asynchronously to prevent timeouts and improve performance.

### Docker Containerization
Ensures consistent development and production environments.

## Performance Considerations

- Redis caching for frequently accessed data
- Database query optimization with eager loading
- Background processing for API calls
- MySQL performance tuning for production

## Security

- Input validation on all API endpoints
- Rate limiting on public endpoints
- CSRF protection on admin forms
- Secure environment variable handling
- SQL injection prevention via Eloquent ORM

## Monitoring

- Laravel Horizon for queue monitoring
- Laravel Telescope for debugging (development only)
- Structured logging with context
- Error tracking and reporting

## Contributing

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update documentation as needed
4. Use meaningful commit messages

## License

This project is for educational and portfolio purposes.