# Cohort Monitor

A lightweight, production-ready PHP web application for managing and monitoring educational cohorts. Built with a custom MVC architecture — no frameworks, full control.

---

## Architecture Overview

```
cohort-monitor/
│
├── public/                  ← Web server document root
│   ├── index.php            ← Single entry point (front controller)
│   ├── .htaccess            ← Apache URL rewriting
│   └── assets/              ← Static files (CSS, JS, images)
│
├── app/                     ← Application source code
│   ├── Core/                ← Framework core classes
│   │   ├── Router.php       ← URL → Controller dispatcher
│   │   ├── Controller.php   ← Base controller with view/json/redirect
│   │   └── Database.php     ← PDO singleton wrapper
│   │
│   ├── Controllers/         ← HTTP layer (request → response)
│   ├── Services/            ← Business logic layer
│   ├── Repositories/        ← Data access layer (queries)
│   ├── Models/              ← Data entities / value objects
│   │
│   └── Views/               ← Presentation layer
│       ├── layouts/         ← Page layouts (main shell)
│       ├── partials/        ← Reusable UI components
│       ├── dashboard/       ← Dashboard views
│       ├── cohorts/         ← Cohort CRUD views
│       └── errors/          ← Error pages (404, 500)
│
├── bootstrap/
│   └── app.php              ← Autoloader, env loader, helpers
│
├── config/                  ← Configuration files
│   ├── app.php              ← Application settings
│   └── database.php         ← Database connection config
│
├── routes/
│   └── web.php              ← Route definitions
│
├── database/
│   └── schema.sql           ← MySQL schema + seed data
│
├── storage/                 ← Logs, cache, uploads (gitignored)
│   ├── logs/
│   └── cache/
│
├── .env                     ← Environment variables (not committed)
├── .env.example             ← Template for .env
├── .gitignore
├── composer.json
└── README.md
```

---

## Design Principles

| Layer          | Responsibility                           | Example                     |
|----------------|------------------------------------------|-----------------------------|
| **Controller** | Handle HTTP request/response             | `CohortController`          |
| **Service**    | Business logic, validation, orchestration| `CohortService`             |
| **Repository** | Database queries (SQL/PDO)               | `CohortRepository`          |
| **Model**      | Data structures / entities               | `Cohort`, `Student`         |
| **View**       | HTML presentation only                   | `cohorts/index.php`         |

### Rules
- Controllers **never** access the database directly.
- Services **never** output HTML or handle HTTP.
- Repositories contain **only** SQL queries.
- Views contain **no** business logic.
- Models are plain data objects — no persistence.

---

## Tech Stack

| Technology   | Version | Purpose                     |
|--------------|---------|-----------------------------|
| PHP          | 8.1+    | Backend language             |
| MySQL        | 8.0+    | Relational database          |
| Bootstrap    | 5.3     | Responsive CSS framework     |
| JavaScript   | ES6+    | Client-side interactions     |
| HTML5        | —       | Semantic markup              |
| CSS3         | —       | Custom styles                |

---

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- MySQL 8.0 or higher
- Apache with `mod_rewrite` or PHP built-in server

### 1. Clone the repository

```bash
git clone https://github.com/your-org/cohort-monitor.git
cd cohort-monitor
```

### 2. Configure environment

```bash
cp .env.example .env
# Edit .env with your database credentials
```

### 3. Create the database

```bash
mysql -u root -p < database/schema.sql
```

### 4. Start the development server

```bash
php -S localhost:8000 -t public
```

### 5. Open in browser

```
http://localhost:8000
```

---

## Routing

Routes are defined in `routes/web.php`:

```php
$router->get('/',                [DashboardController::class, 'index']);
$router->get('/cohorts',         [CohortController::class,    'index']);
$router->get('/cohorts/{id}',    [CohortController::class,    'show']);
$router->post('/cohorts',        [CohortController::class,    'store']);
$router->put('/cohorts/{id}',    [CohortController::class,    'update']);
$router->delete('/cohorts/{id}', [CohortController::class,    'destroy']);
```

Supported methods: `GET`, `POST`, `PUT`, `DELETE`
PUT/DELETE are supported via `_method` hidden field in forms.

---

## API Expansion (Future)

The architecture is ready for REST API routes:

```
routes/
├── web.php        ← Web UI routes
└── api.php        ← Future API routes (JSON responses)
```

Controllers already support JSON output via `$this->json($data)`.

---

## Namespace Map (PSR-4)

| Namespace             | Directory             |
|-----------------------|-----------------------|
| `App\Core\`           | `app/Core/`           |
| `App\Controllers\`    | `app/Controllers/`    |
| `App\Services\`       | `app/Services/`       |
| `App\Repositories\`   | `app/Repositories/`   |
| `App\Models\`         | `app/Models/`         |

---

## License

MIT License — see [LICENSE](LICENSE) for details.
