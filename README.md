# Vintage Books E-Commerce

A Symfony-based e-commerce platform for vintage and rare books.

## Features

- User authentication and role-based access control
- Product catalog with categories
- Shopping cart functionality
- Order management
- Admin dashboard for product management
- Responsive design

## Requirements

- PHP 8.1 or higher
- Composer
- MySQL/MariaDB
- Symfony CLI

## Installation

1. Clone the repository:
```bash
git clone [repository-url]
cd vintage-books-ecommerce
```

2. Install dependencies:
```bash
composer install
```

3. Configure your database in `.env`:
```
DATABASE_URL="mysql://db_user:db_password@127.0.0.1:3306/vintage_books?serverVersion=8.0"
```

4. Create the database:
```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

5. Load fixtures (optional):
```bash
php bin/console doctrine:fixtures:load
```

6. Start the development server:
```bash
symfony server:start
```

## Default Users

- Admin: admin@example.com / password
- User: user@example.com / password

## Project Structure

```
src/
├── Controller/    # Application controllers
├── Entity/        # Doctrine entities
├── Repository/    # Custom repositories
├── Service/       # Business logic services
templates/
├── base.html.twig
├── product/
├── security/
├── cart/
public/
├── assets/        # CSS, JS, and images
```

## License

MIT 