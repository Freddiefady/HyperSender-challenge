# Transportation Management System

A comprehensive transportation management application built with Laravel 12 and Filament 4, featuring fleet management,
driver scheduling, and trip operations with advanced conflict detection.

## Features

### Core Functionality

- **Company Management**: Multi-company support with separate fleet management
- **Driver Management**: Driver profiles with license tracking and availability management
- **Vehicle Management**: Fleet tracking with specifications and utilization monitoring
- **Trip Operations**: Comprehensive trip scheduling with conflict prevention
- **KPI Dashboard**: Real-time analytics and performance metrics

### Advanced Features

- **Double Booking Prevention**: Automatic detection and prevention of driver/vehicle conflicts
- **License Management**: Automated tracking of driver license expiration with alerts
- **Availability Checking**: Real-time availability queries for drivers and vehicles
- **Performance Analytics**: Fuel efficiency tracking, utilization rates, and completion metrics
- **Validation Service**: Comprehensive business logic validation with warnings

## Technical Stack

- **Framework**: Laravel 12
- **Admin Panel**: Filament 4
- **Testing**: Pest
- **Database**: MySQL/PostgreSQL
- **PHP Version**: 8.2+

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- MySQL/PostgreSQL

### Setup Instructions

1. **Clone the repository**

```bash
git clone https://github.com/Freddiefady/HyperSender-challenge.git
cd HyperSender-challenge
```

2. **Install dependencies**

```bash
composer install
```

3. **Environment setup**

```bash
cp .env.example .env
php artisan key:generate
```

4. **Database configuration**
   Edit `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=transportation_mgmt
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Run migrations and seeders**

```bash
php artisan migrate
php artisan db:seed
```

7. **Create admin user**

```bash
php artisan make:filament-user
```

8. **Start development server**

```bash
php artisan serve
```

Visit `http://localhost:8000/admin` to access the application.

## Database Schema

### Core Tables

#### Companies

- Basic company information and registration details
- Active status tracking
- One-to-many relationships with drivers, vehicles, and trips

#### Drivers

- Personal and contact information
- License management with expiration tracking
- Company association and active status
- Availability tracking through trip relationships

#### Vehicles

- Vehicle specifications and registration details
- Type categorization (truck, van, trailer, car)
- Capacity and fuel specifications
- Company association and active status

#### Trips

- Complete trip lifecycle management
- Scheduled vs actual timing tracking
- Status workflow (scheduled → in_progress → completed/cancelled)
- Performance metrics (distance, fuel consumption)
- Comprehensive relationships with company, driver, and vehicle

### Key Relationships

- Company has many drivers, vehicles, and trips
- Driver belongs to company, has many trips
- Vehicle belongs to company, has many trips
- Trip belongs to company, driver, and vehicle

## License

This project is licensed under the MIT License. See the LICENSE file for details.
