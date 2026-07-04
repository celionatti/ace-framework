# ✨ Ace PHP MVC Framework

A handcrafted, lightweight, zero-dependency custom PHP MVC framework. The core engine is fully separated and ready to be packaged on Packagist, making it easy to create modular PHP applications. It is built to run perfectly both locally in subdirectories (like XAMPP's `/mvc/` or `/mvc/public/`) and on a live production server at a domain root.

---

## 🚀 Key Features

* **Strict MVC Separation**: Clean structures for Controllers, Models, and Views.
* **Core Framework Decoupling**: Core framework classes reside in `src/` under the `Ace\` namespace, making it packagist-ready. User applications reside in `app/` under the `App\` namespace.
* **Zero-Config Active Record ORM & Fluent Query Builder**: Exposes static query methods (`findOne()`, `find()`, `all()`, `delete()`) and simple instance persistence (`save()`). Includes a fluent query builder interface for clean DB interactions.
* **API Resources & JSON Serialization**: Transform database entities and collections into customized, wrapped JSON representations (`Ace\JsonResource`) for APIs, keeping sensitive schema details hidden.
* **Built-in Guard System (Input & Output Protection)**: Automates XSS input sanitization recursively before saving attributes to the DB (excluding `$rawFields`), enforces mass-assignment protection using `$fillable` and `$guarded`, and provides safe HTML output helpers (`$model->safe()` and `e()`).
* **Smart Request & Response Wrappers**: Automates XSS input sanitization on incoming parameters and provides unified JSON and redirection responses.
* **Local Subdirectory Routing**: Base paths are auto-detected and stripped dynamically from the request URI so routes work interchangeably on XAMPP and in production.
* **Controller Middlewares**: Apply route guard filters (e.g. restricting profile dashboards to authenticated sessions via `AuthMiddleware` or auth pages via `GuestMiddleware`).
* **Dependency Injection Container**: Reflection-based constructor auto-wiring. Type-hint any class in your controller constructors and the framework resolves and injects dependencies automatically — including core singletons like `Request`, `Database`, and `Session`.
* **Secure Template Directives**: Automated XSS escaping, safe URL output with `@url()` to protect links while maintaining query strings, and convenience directives: `@csrf`, `@isset`, `@empty`, and `@session`.
* **Paystack, Stripe, and Flutterwave Integration**: Multi-gateway payment clients integrated using native PHP cURL (zero SDK dependencies) to initialize payments, verify transactions, and validate signed webhook signatures.
* **Self-Healing Schema Migrations**: Runs automatically on application boot or via the Ace CLI to build/verify database structures.
* **Premium Glassmorphic Dark UI**: Curated vanilla CSS typography (Outfit Google Font), glowing alerts, responsive grids, and clean dark mode layout structure out-of-the-box.
* **Robust Error Handling**: Registers global handlers to capture errors/exceptions and renders beautiful, snippet-highlighted debugging screens (Development mode) or clean generic error pages (Production mode).

---

## 📂 Directory Layout

```text
mvc/
├── app/                    # User Application code
│   ├── Controllers/        # Custom App Controllers
│   ├── Models/             # Active Record Entities
│   └── Middlewares/        # App route filters (Auth guards)
├── config/                 # Env Config mappings
├── migrations/             # Database migrations
├── public/                 # Document Root / Entry Point
│   ├── .htaccess               # Apache URL rewrite rules for front-controller
│   ├── index.php               # Front-controller entry point
│   └── assets/                 # Static styles and client scripts
├── src/                    # Ace Framework Core Engine (Packagist-Ready)
│   ├── Application.php     # Bootstrap class container
│   ├── Request.php         # Request path, method, and sanitization
│   ├── Response.php        # HTTP response, redirect, and JSON helpers
│   ├── Router.php          # Registers and matches static/dynamic routes
│   ├── Controller.php      # Base controller for views and middlewares
│   ├── Model.php           # Active Record base model & Validator
│   ├── Container.php      # Dependency Injection Container with auto-wiring
│   ├── QueryBuilder.php    # Fluent query builder interface
│   ├── Database.php        # PDO wrapper & self-healing migrations
│   ├── Session.php         # Session values & temporary flash alerts
│   └── helpers.php         # Global template helpers
├── views/                  # View templates (Simple PHP Layout system)
├── .env                    # System variables & API Keys
├── ace                     # Ace CLI Console Tool
└── composer.json           # Composer Autoload mappings
```

---

## 🛠️ Setup & Installation

### 1. Prerequisite Checklist

* PHP 8.0 or higher
* Composer installed
* MySQL (e.g. XAMPP Control Panel running Apache & MySQL)

### 2. Clone/Extract the Project

Ensure the project sits inside your web directory (e.g., `C:/xampp/htdocs/mvc`).

### 3. Install Dependencies (Autoloader Setup)

Open a terminal in the project directory and run:

```bash
composer dump-autoload
```

### 4. Configuration Setup

Duplicate the `.env.example` file to `.env`:

```bash
cp .env.example .env
```

Open `.env` and fill in your database credentials:

```ini
APP_ENV=development
APP_NAME="Ace App"
APP_URL="http://localhost/mvc"

DB_HOST=localhost
DB_PORT=3306
DB_NAME=mvc_db
DB_USER=root
DB_PASS=
```

*Note: The database does not need to exist. The framework will automatically detect if it is missing and attempt to create it (`mvc_db`) and its tables on the first page load.*

### 5. Running the Application

* **Under XAMPP**: Access the framework directly at `http://localhost/mvc/` (url rewriting automatically routes requests to `public/index.php` using the root `.htaccess`).
* **Via built-in PHP server**: Open your terminal in the project folder and run:

  ```bash
  php -S localhost:8000 -t public
  ```

  Then access `http://localhost:8000/`.

---

## 📖 Quick-Start Framework Guide

### 1. Registering Routes

Routes are registered in `routes/web.php` pointing to Controller Actions or closures:

```php
// Static GET route
$router->get('/about', function() {
    return "This is a custom about page!";
});

// GET Route pointing to a Controller Action
$router->get('/profile', [AuthController::class, 'profile']);

// Parameterized Dynamic Route
$router->get('/users/{id}', function($request, $id) {
    return "User Profile details for ID: " . htmlspecialchars($id);
});
```

### 2. Controller-Level Middlewares

Protect specific actions by registering middlewares in your Controller's constructor:

```php
namespace App\Controllers;

use Ace\Controller;
use Ace\Middlewares\AuthMiddleware;

class UserDashboardController extends Controller 
{
    public function __construct() 
    {
        // Require authentication for 'index' and 'editSettings' actions
        $this->registerMiddleware(new AuthMiddleware(['index', 'editSettings']));
    }
    
    public function index($request) {
        return $this->render('dashboard');
    }
}
```

### 3. Dependency Injection (Constructor Auto-wiring)

The framework's DI Container automatically resolves constructor dependencies when instantiating controllers. Simply type-hint what you need:

```php
namespace App\Controllers;

use Ace\Controller;
use Ace\Request;
use Ace\Database;
use App\Services\NotificationService;
use Ace\Middlewares\AuthMiddleware;

class OrderController extends Controller
{
    public function __construct(
        protected Database $db,
        protected NotificationService $notifications
    ) {
        $this->registerMiddleware(new AuthMiddleware());
    }

    public function show(Request $request, $id)
    {
        // $this->db and $this->notifications are already injected!
        return $this->render('orders/show', ['order' => Order::findOrFail($id)]);
    }
}
```

### 4. Fluent Query Builder & Active Record ORM

Models map to database tables seamlessly, supporting both raw Active Record operations and clean, fluent queries.

#### Fluent & Fail-safe Querying

```php
// Fetch published posts ordered by date limit 10
$posts = Post::query()
    ->where('status', 'published')
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Find single user or throw ModelNotFoundException (generates 404 response automatically)
$user = User::findOrFail(2);
$user = User::query()->where('email', 'user@example.com')->firstOrFail();

// Check existence & pluck specific columns
$exists = User::exists(['email' => 'admin@example.com']);
$emails = User::pluck('email');
```

#### Save (INSERT / UPDATE) & Factory Creation

```php
// Option A: Active Record instantiation & save
$user = new User();
$user->name = 'Jane Doe';
$user->email = 'jane@example.com';
$user->password = 'securePass123';
$user->passwordConfirm = 'securePass123'; // Used in validations

if ($user->validate()) {
    $user->save(); // Inserts and populates $user->id
    echo "Saved user: " . $user->id;
} else {
    print_r($user->errors);
}

// Option B: Inline creation (instantiates, populates, and saves)
$user = User::create([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

// Option C: Update-or-Create
$user = User::updateOrCreate(
    ['email' => 'jane@example.com'],
    ['name' => 'Jane Doe Jr.']
);
```

#### Deletion

```php
// Delete individual row instance
$user = User::findOne(['id' => 2]);
if ($user) {
    $user->delete();
}

// Statically delete multiple records by primary key
User::destroy(3, 4, 5);
```

### 5. API Resources & JSON Serialization

API Resources (`Ace\JsonResource`) act as a transform mapping layer between models and API endpoints:

```php
namespace App\Resources;

use Ace\JsonResource;
use Ace\Request;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'joined' => date('Y-m-d', strtotime($this->resource->created_at))
        ];
    }
}
```

Return them directly from controller actions or closures to serve formatted JSON responses automatically:

```php
// Single resource
$router->get('/api/users/{id}', function(Request $request, $id) {
    return new UserResource(User::findOrFail($id));
});

// Collection of resources
$router->get('/api/users', function(Request $request) {
    return UserResource::collectionResponse(User::all());
});
```

### 6. Template Views & Custom Directives

Views support convenient Blade-like template directives:

```html
@extends('layouts/main')

@section('content')
    <!-- Reference CSS/JS assets safely using asset() helper -->
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    
    <h1>Welcome, {{ $user->name }}</h1>

    <!-- Hidden CSRF token field -->
    @csrf

    <!-- Render user uploads safely using upload() helper -->
    <img src="<?= upload($user->avatar_url) ?>" class="avatar">

    @isset($posts)
        @empty($posts)
            <p>No posts available.</p>
        @else
            @foreach($posts as $post)
                <h3>{{ $post->title }}</h3>
            @endforeach
        @endempty
    @endisset

    <!-- Read session flash notifications -->
    @session('success')
        <div class="alert alert-success">{{ $value }}</div>
    @endsession
@endsection
```

Ace Core Framework
Copyright © 2026 Celio Natti
Released under the MIT License.
