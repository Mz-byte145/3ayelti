# AI Instructions for Mon Projet Codebase

## Project Overview
**Mon Projet** is a family finance and task management system built in PHP/MySQL. It enables parents to manage household budgets, assign chores to children with point rewards, and allow children to redeem points for rewards.

**Key Architecture**: Session-based authentication → Budget/Children management → Task assignment → Point tracking → Reward redemption

## Core Concepts

### 1. Authentication & Session Management
- **Pattern**: Session-based (`session_start()` in every PHP file)
- **Files**: [login.php](login.php), [registre.php](registre.php), [connexion.php](connexion.php)
- **Key flows**:
  - Login: Email + `password_verify()` check against hashed password in `user` table
  - Registration: `password_hash(PASSWORD_DEFAULT)` before inserting user
  - Session check: `if (!isset($_SESSION['user_id'])) die()` guards protected pages
  - Always include [connexion.php](connexion.php) first for MySQLi connection

### 2. Database Relationships
- **user** ← parent accounts
- **enfants** ← children (linked to `user_id`)
- **point** ← completed tasks (linked to `enfant_id`, stores `points_gagnes` and `tache`)
- **recompenses** ← reward redemptions (linked to `enfant_id`, tracks `points_utilises`)
- **budget_familial** ← household budget (salary, child count per user)
- **facture** ← expenses to pay from salary

### 3. Data Flow Patterns

**Task Management** ([tache.php](tache.php)):
- Parent adds custom tasks with point values for a child
- Child completes task → AJAX POST to [tache.php](tache.php) saves to `point` table
- Points calculation: `SUM(points_gagnes) - SUM(points_utilises)` per child

**Budget & Payments**:
- Parent sets salary in `budget_familial`
- [facture.php](facture.php): Mark invoices as paid, deduct from salary
- [payer.php](payer.php): AJAX payment processing (delete invoice, update salary)

**Rewards** ([recompense.php](recompense.php)):
- Hardcoded reward list with point costs (e.g., "1h jeux vidéo" = 20 points)
- Child redeems if points available → Insert into `recompenses` table
- Points deducted via calculation: `points_dispo = SUM(earned) - SUM(used)`

## Critical Security Patterns

- **Always use prepared statements**: `mysqli_prepare()` + `bind_param()` + `execute()` (✓ implemented)
- **Password hashing**: `password_hash()` for registration, `password_verify()` for login
- **User isolation**: Query results filtered by `user_id` to prevent cross-user access
- **Input sanitization**: `intval()`, `floatval()` for numeric values; `trim()` for strings

## Development Conventions

### 1. New Features
- Create new `.php` file in root
- Include [connexion.php](connexion.php) after `session_start()`
- Add `if (!isset($_SESSION['user_id'])) die()` guard at top
- Use `mysqli_prepare()` + `bind_param()` for all DB queries (no string concatenation)
- Follow existing naming: French terms (enfant, tache, recompense), snake_case for DB columns

### 2. Frontend
- HTML forms POST to same file or to [tache.php](tache.php)/[payer.php](payer.php) (AJAX via fetch)
- [script.js](script.js): Client-side validation (email format check, password length, alphabetic name)
- [style.css](style.css): Gradient background (#3b4371 to #f3904f), 'Bangers' font, glassmorphism containers
- Forms use inline styling within HTML files (no external CSS links except style.css)

### 3. Common AJAX Pattern
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enfant_id'], $_POST['points'], $_POST['tache'])) {
        // Process request
        echo "Success message";
        exit;
    }
}
```

## File Purpose Reference
- **Login/Registration**: [formulaire.html](formulaire.html), [inscription.html](inscription.html), [login.php](login.php), [registre.php](registre.php)
- **Task Management**: [tache.php](tache.php) (view + AJAX for add/log tasks)
- **Rewards**: [recompense.php](recompense.php) (view + redeem logic)
- **Budget/Invoices**: [facture.php](facture.php), [payer.php](payer.php) (payment processing)
- **Reports**: [salaireFacture.php](salaireFacture.php) (salary/invoice summary), [performance.php](performance.php) (child performance)
- **Utility**: [commentaire.php](commentaire.php), [tirage.php](tirage.php), [help.html](help.html)

## Testing & Debugging Notes
- Database: `inscription` DB on localhost, user `root`, no password (WAMP default)
- Error reporting enabled in [login.php](login.php): `error_reporting(E_ALL)` for debugging
- AJAX responses are plain text (e.g., "Points enregistrés!") — read console logs to debug
- Always verify parent-child ownership: `user_id` + `enfant_id` checks prevent unauthorized access
