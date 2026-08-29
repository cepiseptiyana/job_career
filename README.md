# JobCareer

JobCareer is a web-based job portal application built with Laravel.  
The application allows users to browse job vacancies, manage their profiles, apply for jobs, and post/manage job vacancies.

## Features

-   User authentication
-   User profile management
-   Profile picture
-   Browse job vacancies
-   Search and find jobs
-   Job detail
-   Post a job
-   Manage posted jobs
-   Apply for jobs
-   View applied jobs
-   Save jobs
-   Job categories
-   Job types
-   Role-based user access
-   Job application management

---

## Tech Stack

-   PHP
-   Laravel
-   MySQL
-   Blade
-   JavaScript
-   Vite
-   Bootstrap / CSS
-   XAMPP (for local development)

---

## Requirements

Make sure the following software is installed:

-   PHP >= 8.1
-   Composer
-   Node.js and npm
-   MySQL
-   Git

You can use XAMPP to run Apache and MySQL.

Check your installed versions:

```bash
php -v
composer -V
node -v
npm -v
```

# Installation

1. Clone Repository

2. Install PHP Dependencies
   composer install

3. Install Frontend Dependencies
   npm install

4. Create Environment File
   copy .env.example .env

5. Generate Application Key
   php artisan key:generate

## Database Configuration

If you are using XAMPP.
Create a new MySQL database:

1. job_portal

2. Then open the .env file and configure the database:
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=job_portal
   DB_USERNAME=root
   DB_PASSWORD=

## Run Database Migration

1. php artisan migrate

## Seed Sample Data

1. php artisan db:seed

## Run the Application

1. php artisan serve
