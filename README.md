# Certification & Subscription Management System

A Core PHP (No Framework) implementation of a Certification and Subscription management system. This project demonstrates Clean Architecture, SOLID principles, and various design patterns as per the technical requirements.

## 🚀 Features

-   **Certification Management**: Create drafts, add requirement areas (Course Count / Duration), manage courses, and publish certifications.
-   **Subscription Management**: Subscribe users to certifications (Monthly/Yearly), handle state transitions (Active, Paused, Cancelled, Expired).
-   **Automated Renewals**: CLI job to renew eligible subscriptions and expire cancelled/non-renewing ones.
-   **REST API**: Simple JSON endpoints for managing certifications, courses, and subscriptions.
-   **Validation**: Strict domain-level validation using Specification pattern.
-   **Architecture**: Decoupled layers (Domain, Application, Infrastructure, Presentation).

## 🛠️ Technical Stack

-   **Language**: PHP 8.3+
-   **Database**: MySQL (PDO)
-   **Testing**: PHPUnit 10
-   **Dependencies**: Composer (Autoloading only)


## ⚙️ Installation

1.  **Clone the repository**
    ```bash
    git clone https://github.com/mehulkoradiya/cert-sub-management.git
    cd cert-sub-management
    ```

2.  **Install dependencies**
    ```bash
    composer install
    ```

3.  **Configure Database**
    -   Open `config/config.php` and update the database credentials (`db.dsn`, `db.user`, `db.password`).
    -   Ensure your MySQL server is running.

4.  **Initialize Database Schema**
    Run the initialization script to create tables:
    ```bash
    php scripts/init_db.php
    ```

## 🏃 Usage

### REST API
The system provides a simple REST API. Point your web server (Apache/Nginx) to `public/index.php`.

**Base URL**: `http://localhost/cert-sub-management/public` (depending on your setup)

#### Certifications
-   **Create Draft**: `POST /api/certifications`
    ```json
    { "name": "PHP Master", "description": "Expert Level" }
    ```
-   **Get Certification**: `GET /api/certifications/{id}`
-   **Add Requirement Area**: `POST /api/certifications/{id}/areas`
    ```json
    { "name": "Core", "requirement_type": "course_count", "requirement_value": 5 }
    ```
-   **Add Course to Area**: `POST /api/certifications/{id}/areas/{area_name}/courses`
    ```json
    { "course_id": 1 }
    ```
-   **Publish Certification**: `POST /api/certifications/{id}/publish`

#### Courses
-   **Create Course**: `POST /api/courses`
    ```json
    { "title": "OOP Basics", "duration": 10, "category": "Backend" }
    ```

#### Subscriptions
-   **Create Subscription**: `POST /api/subscriptions`
    ```json
    { "user_id": 1, "certification_id": 1, "type": "monthly", "auto_renew": true }
    ```

### CLI Commands

**Subscription Renewal Job**
Finds expiring subscriptions and renews them (if auto-renew is on) or expires them.
```bash
php src/Presentation/CLI/cli.php renewal:run
```

## 🧪 Testing

Run the test suite using PHPUnit:
```bash
vendor/bin/phpunit
```


