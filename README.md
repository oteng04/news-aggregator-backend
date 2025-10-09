# News Aggregator Backend

A production-ready Laravel-based news aggregation system that demonstrates enterprise-level development practices, clean architecture, and modern PHP development.

## 🚀 Features

- **Multi-Source News Aggregation**: Integrates with NewsAPI, The Guardian, and New York Times
- **Clean Architecture**: Repository pattern, service layer, and dependency injection
- **Admin Dashboard**: Filament-powered admin interface with statistics widgets
- **Queue Processing**: Laravel Horizon for background job processing
- **RESTful API**: Comprehensive API endpoints with proper documentation
- **Production Ready**: Docker containerization with nginx, supervisor, and optimized MySQL

## 🛠️ Tech Stack

- **Laravel 11.x** - PHP framework
- **PHP 8.2** - Server-side scripting
- **MySQL 8.0** - Database
- **Redis** - Caching and queues
- **Docker & Docker Compose** - Containerization
- **Nginx** - Web server
- **Supervisor** - Process management
- **Laravel Horizon** - Queue monitoring
- **Filament 3.x** - Admin panel

## 📁 Project Structure

```
├── app/
│   ├── Services/           # News source services
│   ├── Repositories/       # Data access layer
│   ├── Filament/          # Admin panel resources
│   └── Http/Controllers/  # API controllers
├── config/                # Laravel configuration
├── docker/                # Production container configs
├── database/migrations/  # Database schema
├── routes/               # API and web routes
└── tests/               # Test suites
```

## 🏃‍♂️ Quick Start

### Development Environment
```bash
# Clone the repository
git clone https://github.com/oteng04/news-aggregator-backend.git
cd news-aggregator-backend

# Start development environment
docker compose up -d

# Run database migrations
docker compose exec app php artisan migrate

# Create admin user
docker compose exec app php artisan make:filament-user
```

### Production Environment
```bash
# Build and start production containers
docker compose -f docker-compose.prod.yml up -d --build

# Run database migrations
docker compose -f docker-compose.prod.yml exec app php artisan migrate

# Create storage link
docker compose -f docker-compose.prod.yml exec app php artisan storage:link
```

## 🔗 API Endpoints

### Articles
- `GET /api/articles` - List articles with pagination
- `GET /api/articles/{id}` - Get specific article
- `GET /api/articles/search` - Search articles
- `POST /api/articles` - Create article (admin only)
- `PUT /api/articles/{id}` - Update article (admin only)
- `DELETE /api/articles/{id}` - Delete article (admin only)

### Sources & Categories
- `GET /api/sources` - List news sources
- `GET /api/categories` - List categories
- `GET /api/authors` - List authors

## 👨‍💼 Admin Panel

Access the admin dashboard at `/admin` to:
- View dashboard statistics
- Manage articles, sources, categories, and authors
- Monitor queue jobs with Laravel Horizon
- Configure news sources and API keys

## 🧪 Testing

```bash
# Run all tests
docker compose exec app php artisan test

# Run specific test suite
docker compose exec app php artisan test --testsuite=Unit
```

## 🔧 Configuration

### Environment Variables
```env
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-generated-key

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=news_aggregator
DB_USERNAME=news_user
DB_PASSWORD=secure_password

REDIS_HOST=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# News API Keys
NEWSAPI_API_KEY=your_newsapi_key
GUARDIAN_API_KEY=your_guardian_key
NYT_API_KEY=your_nyt_key
```

## 📊 Monitoring

- **Laravel Horizon**: `/horizon` - Queue job monitoring
- **Laravel Telescope**: `/telescope` - Debug and monitoring (development only)
- **Health Checks**: Built-in Laravel health monitoring

## 🐳 Docker Architecture

### Development
- **Laravel Artisan Serve**: Port 8000
- **MySQL**: Port 3306
- **Redis**: Port 6379

### Production
- **Nginx**: Port 80
- **PHP-FPM**: Internal communication
- **Supervisor**: Manages all processes
- **Queue Workers**: Automatic background processing

## 🎯 Key Highlights

### Clean Code Principles
- **SOLID Principles**: Single responsibility, open/closed, etc.
- **Repository Pattern**: Clean data access abstraction
- **Service Layer**: Business logic encapsulation
- **Dependency Injection**: Proper IoC container usage

### Production Readiness
- **Error Handling**: Comprehensive exception handling
- **Logging**: Structured logging with context
- **Caching**: Redis-based caching strategy
- **Queue Processing**: Background job processing
- **Security**: Input validation and sanitization

### Developer Experience
- **Docker Development**: Consistent environment
- **Laravel Horizon**: Visual queue monitoring
- **Filament Admin**: Modern admin interface
- **API Documentation**: Clear endpoint documentation

## 📈 Performance Optimizations

- Database query optimization with eager loading
- Redis caching for frequently accessed data
- Background queue processing for API calls
- MySQL performance tuning
- Composer autoloader optimization

## 🔒 Security Measures

- Input validation on all API endpoints
- SQL injection prevention via Eloquent ORM
- CSRF protection on forms
- Secure environment variable handling
- Rate limiting on API endpoints

---

**Built with Laravel 11.x, PHP 8.2, and modern development practices to demonstrate senior-level backend development skills.**