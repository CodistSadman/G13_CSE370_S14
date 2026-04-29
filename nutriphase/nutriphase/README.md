# 🥦 NutriPhase — Setup Guide

## Requirements
- PHP 7.4+ (8.x recommended)
- MySQL / MariaDB (XAMPP, WAMP, Laragon, or native)
- A local server like Apache (comes with XAMPP)

---

## 1. Import the Database

1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **New** → create database named `nutriphase`
3. Click the `nutriphase` database → **Import** tab
4. Upload the provided SQL dump file → click **Go**

---

## 2. Place Project Files

Copy the `nutriphase/` folder into your web server root:

| Server | Web Root |
|--------|----------|
| XAMPP  | `C:\xampp\htdocs\` |
| WAMP   | `C:\wamp64\www\` |
| Laragon| `C:\laragon\www\` |
| Linux  | `/var/www/html/` |

---

## 3. Configure the Database

Open `config/db.php` and update credentials if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // your MySQL password
define('DB_NAME', 'nutriphase');
```

---

## 4. Run the App

Visit: **`http://localhost/nutriphase/`**

You'll be redirected to the login page.

---

## Project Structure

```
nutriphase/
├── index.php                  # Redirects to login
├── config/
│   ├── db.php                 # Database connection
│   └── helpers.php            # Response helpers, session auth
├── api/
│   ├── auth.php               # Register, Login, Logout
│   ├── habits.php             # Habit CRUD
│   ├── metrics.php            # Body metrics CRUD
│   ├── nutritionists.php      # List nutritionists, subscribe
│   ├── predictions.php        # Health predictions
│   └── friends.php            # Friend requests
├── pages/
│   ├── login.html             # Login page
│   ├── register.html          # Registration (patient or nutritionist)
│   ├── dashboard.html         # Home dashboard
│   ├── habits.html            # Log and view habits
│   ├── metrics.html           # Log and view body metrics
│   ├── nutritionists.html     # Browse & subscribe to nutritionists
│   ├── predictions.html       # Health insights & predictions
│   └── friends.html           # Send & manage friend requests
└── assets/
    ├── css/style.css          # Global stylesheet
    └── js/
        ├── app.js             # API client, session, utilities
        └── sidebar.js         # Dynamic sidebar renderer
```

---

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth.php?action=register` | Register user |
| POST | `/api/auth.php?action=login` | Login |
| POST | `/api/auth.php?action=logout` | Logout |
| GET  | `/api/habits.php` | Get patient habits |
| POST | `/api/habits.php` | Log a habit |
| DELETE | `/api/habits.php?id=X` | Delete a habit |
| GET  | `/api/metrics.php` | Get body metrics |
| POST | `/api/metrics.php` | Save body metrics |
| GET  | `/api/nutritionists.php` | List nutritionists |
| POST | `/api/nutritionists.php?action=subscribe` | Subscribe |
| DELETE | `/api/nutritionists.php?action=unsubscribe&N_SSN=X` | Unsubscribe |
| GET  | `/api/predictions.php` | View predictions |
| POST | `/api/predictions.php` | Create prediction (nutritionist) |
| GET  | `/api/friends.php` | View friend requests |
| POST | `/api/friends.php` | Send friend request |
| DELETE | `/api/friends.php?id=X` | Remove/decline request |
