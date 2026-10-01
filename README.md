# ⚡ VanillaSaaS: The Framework-Free Authentication Engine

Welcome to **VanillaSaaS**—the ultimate framework-free, zero-dependency blueprint for launching web applications.

## This boilerplate is engineered for indie hackers, solopreneurs, and builders who value **speed, absolute simplicity, and long-term stability**. Built purely with native **HTML5, CSS3, modern Vanilla JavaScript (ES6+), and clean PHP 8.x**, it bypasses the complexity of modern JavaScript build chains and cloud pipelines.

## 🚀 Quick Start: Up & Running in 30 Seconds

You do not need `npm install`, Docker, or heavy environment configurations.

1. **Unzip** this project folder into your directory of choice.
2. **Launch** the native PHP development web server from your terminal or command prompt:
   ```bash
   php -S localhost:8000
   ```
3. **Open** your browser and go to: `http://localhost:8000/login.html`
   _That’s it._ By default, VanillaSaaS loads up in **SQLite Mode**. A local database file (`backend/database.sqlite`) is auto-generated instantly on your first signup, meaning you can test the entire registration, login, session validation, and logout loop immediately.

---

## 📁 Project Architecture

```text
📁 vanilla-saas-auth/
├── 📄 login.html          # Semantic login form view with accessible validations
├── 📄 register.html       # Sign-up interface built with native HTML5 components
├── 📄 dashboard.html      # Protected user environment workspace layout
├── 📄 AI-PROMPTS.md       # Your master prompt sheets for expanding features with AI
├── 📄 README.md           # This master onboarding documentation file
├── 📁 css/
│   └── 📄 main.css        # Responsive utility-first style layout using CSS variables
├── 📁 js/
│   └── 📄 auth.js         # Asynchronous client-side form behavior & fetch pipelines
└── 📁 backend/
    ├── 📄 config.php      # Main system setting config (Database toggles live here)
    ├── 📄 auth.php        # Secure login routing processor & password verifier
    ├── 📄 register.php    # Secure database insertion router & registration hub
    ├── 📄 session_check.php# Gatekeeper engine validating active tokens
    ├── 📄 logout.php      # Complete server-side memory purging script
    └── 📄 schema.sql      # Production-ready MySQL/MariaDB database migration script
```

---

## 🔧 Production Setup: Switching to MySQL

When you are ready to deploy your app to production (such as a standard, affordable cPanel or shared hosting service), switching from SQLite to MySQL takes seconds:

### Step 1: Initialize the Database

Import the provided `backend/schema.sql` template file into your MySQL database manager (like phpMyAdmin) using the **Import** tab.

### Step 2: Install the PHP Engine (Windows Users)

Because native browsers cannot read backend PHP code without a translator, you need the PHP engine installed locally.

1. Download and run the standard installer from [XAMPP Official](https://apachefriends.org).
2. Once installed, open your **VS Code Settings** (`Ctrl + ,`).
3. Search for `phpserver.phpPath` and paste the location of your new engine:
   `C:\xampp\php\php.exe`

### Step 3: Configure the App switchboard

Open `backend/config.php`, flip the `DB_MODE` flag from `'sqlite'` to `'mysql'`, and insert your hosting database access credentials:

```php
define('DB_MODE', 'mysql'); // Flipped seamlessly from 'sqlite'
define('MYSQL_HOST', 'your_production_host');
define('MYSQL_DB',   'your_database_name');
define('MYSQL_USER', 'your_database_user');
define('MYSQL_PASS', 'your_secure_password');
```

---

## 🛡️ Core Security Architecture

VanillaSaaS does not cut corners on security. Your core codebase includes built-in protective measures designed to safeguard consumer platforms from day one:

- **Session Fixation Prevention:** Regenerates internal session identity signatures (`session_regenerate_id(true)`) instantly upon successful user login.
- **XSS & Cookie Security Flags:** Enforces strict server-side cookie rules (`HttpOnly`, `SameSite=Strict`, `Secure`) to lock out unauthorized third-party JavaScript inspection vectors.
- **SQL Injection Immunity:** Every backend database transaction is handled via **PDO Prepared Statements** to neutralize malicious parameter manipulations.
- **Enterprise-Grade Hashing:** Passwords are processed natively using **Argon2id**, the modern standard for password encryption.

---

## 🤖 Expanding with AI

Want to build a multi-tenant dashboard, integrate Stripe payments, or add user avatars? Open your enclosed **`AI-PROMPTS.md`** file.
Because this entire product is built using pure, native browser standards, AI engines (like Claude, Cursor, or ChatGPT) understand this codebase perfectly. Use the pre-written prompt blueprints in that file to scale your application over a weekend without corrupting your lightweight foundation.

---

_Engineered by [Your Name] — 25 Years of Web Development Legacy & 14 Years of Pedagogical Code Instruction._
# vanillasaas-auth
