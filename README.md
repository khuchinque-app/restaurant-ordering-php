# Restaurant Ordering System — PHP Edition

Pure PHP + HTML/CSS/JS rewrite of the original Next.js project. No framework, no Composer. SQLite database shared with the original backend.

**Live URL:** `http://187.127.178.20:8080`

---

## Quick Start

```bash
# From the php/ directory
php -S 0.0.0.0:8080

# First time only — seed the database
curl http://localhost:8080/setup.php
```

---

## Folder Structure

```
php/
├── index.php                  # Login gate (routes by role after sign-in)
├── menu.php                   # Customer menu page
├── cart.php                   # Cart & checkout
├── orders.php                 # Order tracking (guest or logged-in)
├── login.php / logout.php     # Auth pages
├── register.php               # Disabled — redirects to index.php
├── setup.php                  # DB seeder (run once)
│
├── admin/                     # Admin panel (ADMIN / MANAGER)
│   ├── index.php              # Dashboard — stats, recent orders, low stock
│   ├── orders.php             # Order management + status updates
│   ├── menu.php               # Category & item CRUD
│   ├── stock.php              # Stock viewer (read-only for admin)
│   └── chat.php               # Staff group chat
│
├── superadmin/                # Superadmin panel (SUPERADMIN only)
│   ├── index.php              # System dashboard + recent activity widget
│   ├── restaurants.php        # Create / enable / disable restaurants
│   ├── users.php              # Create accounts (any role) + manage users
│   ├── reports.php            # Revenue & order reports
│   ├── stock.php              # Stock management — full edit (SET / +▲ / -▼)
│   ├── chat.php               # Staff group chat (same channel as admin)
│   └── activity.php           # Full admin activity log with filters
│
├── api/                       # JSON API (used by JS on the same pages)
│   ├── auth/                  # login, me
│   ├── menu/                  # categories, items, restaurant_info
│   ├── orders/                # index (CRUD), track (guest session)
│   ├── admin/
│   │   ├── dashboard.php      # Stats for one restaurant
│   │   └── stock.php          # Stock adjust — POST restricted to SUPERADMIN
│   ├── staff/
│   │   └── chat.php           # GET (poll since=) / POST (send message)
│   └── superadmin/
│       ├── create_user.php    # Create any-role account
│       ├── restaurants.php    # Restaurant CRUD
│       └── users.php          # Enable / disable users
│
├── includes/
│   ├── header.php             # Customer site nav
│   ├── footer.php
│   ├── admin_header.php       # Admin sidebar + unread chat badge (polls /15s)
│   ├── admin_footer.php
│   ├── superadmin_header.php  # Superadmin sidebar
│   ├── superadmin_footer.php
│   └── activity.php           # log_activity() helper — never throws
│
├── demo_restaurant/           # Customer SPA — Demo Restaurant
│   ├── index.html
│   └── assets/ (style.css, app.js)
│
├── grill_house/               # Customer SPA — Grill House
│   ├── index.html
│   └── assets/ (style.css, app.js)
│
├── assets/
│   ├── css/ (style.css, admin.css, superadmin.css)
│   └── js/  (app.js, admin.js, superadmin.js)
│
├── config.php                 # DB path, JWT secret, APP_URL, TAX_RATE
├── db.php                     # PDO singleton, helpers (db_query, db_fetch, …)
└── auth.php                   # JWT (manual HMAC-SHA256), sessions, guards
```

---

## Roles & Access

| Role        | Where they land after login       | Can do                                      |
|-------------|-----------------------------------|---------------------------------------------|
| SUPERADMIN  | `superadmin/index.php`            | Everything + stock edits + create accounts  |
| ADMIN       | `admin/index.php`                 | Orders, menu CRUD, stock view, staff chat   |
| MANAGER     | `admin/index.php`                 | Same as ADMIN                               |
| CUSTOMER    | `menu.php`                        | Browse menu, place orders (no login needed) |

Customers never need to register. Cart is stored in `localStorage`; orders are tracked via PHP session (`$_SESSION['order_ids']`).

---

## Configuration (`config.php`)

| Constant                  | Default                            | Notes                          |
|---------------------------|------------------------------------|--------------------------------|
| `DB_PATH`                 | `../backend/prisma/dev.db`         | SQLite file path               |
| `JWT_SECRET`              | `dev-secret-key-change-in-prod`    | **Change before going live**   |
| `APP_URL`                 | `` (empty)                         | Set to full URL for prod       |
| `TAX_RATE`                | `0.10`                             | 10%                            |
| `DEFAULT_RESTAURANT_SLUG` | `demo`                             |                                |

---

## Multi-Restaurant

Each restaurant folder (`demo_restaurant/`, `grill_house/`) is a standalone customer SPA. To add a new one:

1. Create the restaurant in **Superadmin → Restaurants**.
2. Duplicate any existing folder, rename it to the new slug.
3. In `assets/app.js` change `RESTAURANT_SLUG = 'your_slug'`.
4. That's it — the API uses the slug to scope all data.

---

## Staff Chat

Single group channel. All staff (ADMIN, MANAGER, SUPERADMIN) share the same chat room. Client polls `/api/staff/chat.php?since=<timestamp>` every 5 seconds. The sidebar unread badge polls every 15 seconds.

---

## Activity Tracking

Every significant admin action is logged to the `AdminActivity` table via `log_activity()` in `includes/activity.php`. Tracked events: LOGIN, ORDER_STATUS changes, CREATE/UPDATE/DELETE menu items, STOCK_ADJUST. View the full log at **Superadmin → Activity** with filters by user, action type, restaurant, and date range.

---

## Stock Permissions

- **SUPERADMIN** — full edit (Set, +Add, −Remove) via `superadmin/stock.php`
- **ADMIN / MANAGER** — read-only view via `admin/stock.php`
- The API endpoint `POST /api/admin/stock.php` returns 403 if caller is not SUPERADMIN

---

## Database Notes

- SQLite file at `../backend/prisma/dev.db` (relative to `php/`)
- `"Order"` must always be double-quoted in SQL — it is a reserved keyword in SQLite
- WAL mode enabled, foreign keys ON
- Tables `AdminActivity` and `StaffChat` are created by `setup.php` if they don't exist
