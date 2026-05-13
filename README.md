# Borrowing and Returning Monitoring System with SMS Notification

A simple, beginner-friendly BSIT 2nd-year final project built with **HTML, CSS, Bootstrap 5, JavaScript, PHP, and MySQL**, running on **XAMPP**, with **Semaphore SMS API** integration.

---

## 1. Introduction

The **Borrowing and Returning Monitoring System (BRMS)** is a web-based application that helps schools, libraries, or small offices keep track of items being borrowed and returned. The system records each borrow transaction, monitors due dates, automatically flags overdue items, computes simple penalties, and sends SMS notifications to borrowers using the Semaphore SMS API. It is designed to be simple enough to be implemented and defended by 2nd-year BSIT students within 2–3 weeks.

---

## 2. System Architecture (3-Tier)

```
+------------------------------------------+
| Presentation Layer (UI)                  |
|  HTML, CSS, Bootstrap 5, JavaScript      |
|  (Login, Dashboard, Forms, Tables)       |
+------------------------------------------+
                  |
                  v
+------------------------------------------+
| Application / Business Logic Layer       |
|  PHP (procedural)                        |
|  - Authentication (auth.php)             |
|  - CRUD for users, items, records        |
|  - Borrow / return processing            |
|  - Overdue + penalty computation         |
|  - SMS sending (Semaphore API)           |
+------------------------------------------+
                  |
                  v
+------------------------------------------+
| Data Layer                               |
|  MySQL (XAMPP)                           |
|  Tables: users, items, borrow_records,   |
|          sms_logs                        |
+------------------------------------------+
```

**Data flow (borrow transaction example):**
1. Admin opens *Borrow* form (Presentation Layer).
2. Form submits to `admin/borrow.php` (Business Logic Layer).
3. PHP validates input, checks stock, prevents duplicate borrowing.
4. PHP inserts a record into `borrow_records` and decrements `items.quantity` (Data Layer).
5. PHP calls `send_sms()` which posts to Semaphore API and logs into `sms_logs`.
6. A receipt is rendered back to the admin.

---

## 3. Database Design (ERD description)

**Tables and relationships:**

- `users (user_id PK, full_name, username UNIQUE, password, contact_number, role, created_at)`
- `items (item_id PK, item_code UNIQUE, item_name, category, quantity, description, created_at)`
- `borrow_records (record_id PK, user_id FK→users, item_id FK→items, borrow_date, due_date, return_date, status, penalty)`
- `sms_logs (sms_id PK, user_id FK→users, contact_number, message, sms_type, status, sent_at)`

```
users (1) ─────< (M) borrow_records (M) >───── (1) items
users (1) ─────< (M) sms_logs
```

Normalization: each entity has a single responsibility (3NF). Foreign keys enforce referential integrity, and `ON DELETE CASCADE` cleans up dependent rows when a parent is removed.

---

## 4. System Features

- User login / logout with role-based access (admin, borrower)
- Admin dashboard with statistics cards (borrowers, stock, borrowed, overdue)
- CRUD for **users**, **items**, and **borrow records**
- Borrow module with:
  - Stock validation (cannot borrow if quantity = 0)
  - Duplicate-borrow prevention (same user + same item still active)
  - Auto-generated **borrow receipt** (printable)
  - SMS notification to the borrower
- Return module with:
  - Auto-computed **penalty** (₱5 / day overdue)
  - Return confirmation + SMS
- Status monitoring: `borrowed`, `returned`, `overdue` (auto-flagged)
- Search and filter on Users, Items, and Records
- Reports page with date range and **Print** option
- User-side dashboard + borrow history
- SMS logs stored in `sms_logs` for auditing
- `sms/send_overdue.php` – run manually or via cron to remind overdue borrowers

---

## 5. Screenshots (placeholders)

Replace these with screenshots once the system is running on XAMPP:

- `docs/screenshots/01-login.png` – Login page
- `docs/screenshots/02-admin-dashboard.png` – Admin dashboard with stat cards
- `docs/screenshots/03-items.png` – Items CRUD
- `docs/screenshots/04-borrow.png` – Borrow form + receipt
- `docs/screenshots/05-return.png` – Return form + confirmation
- `docs/screenshots/06-records.png` – Records with filter
- `docs/screenshots/07-reports.png` – Printable report
- `docs/screenshots/08-user-dashboard.png` – Borrower view

---

## 6. Setup Guide (XAMPP)

1. **Install XAMPP** (PHP 8+ recommended) and start **Apache** and **MySQL**.
2. Copy the entire `brms/` folder into `C:\xampp\htdocs\` (final path: `C:\xampp\htdocs\brms`).
3. Open <http://localhost/phpmyadmin>, click **Import**, choose `database/brms.sql`, and click **Go**. This creates the `brms_db` database with sample data.
4. (Optional) Open `includes/db.php` and update the MySQL credentials if yours are different.
5. (Optional SMS) Sign up at <https://semaphore.co>, get an API key, then either:
   - edit `sms/semaphore.php` and replace `YOUR_SEMAPHORE_API_KEY`, or
   - set the `SEMAPHORE_API_KEY` environment variable.
   Without an API key, SMS calls run in **demo mode** (still logged into `sms_logs`).
6. Visit <http://localhost/brms/login.php>.
7. Login with the demo accounts:
   - **admin / admin123**
   - **juan / user123** (borrower)

To send overdue reminders manually: open <http://localhost/brms/sms/send_overdue.php> in the browser, or schedule it with Windows Task Scheduler / cron.

---

## 7. Folder Structure

```
brms/
├── admin/         # Admin pages (dashboard, users, items, borrow, return, records)
├── user/          # Borrower pages (dashboard, history)
├── assets/        # CSS / JS / images
│   ├── css/
│   └── js/
├── database/      # brms.sql (schema + sample data)
├── includes/      # db.php, auth.php, functions.php, header.php, footer.php
├── sms/           # semaphore.php (API), send_overdue.php (reminders)
├── reports/       # reports.php (date-range printable report)
├── docs/          # documentation, screenshots
├── index.php      # redirects to login
├── login.php
├── logout.php
└── README.md
```

---

## 8. Example JOIN Queries

```sql
-- Recent borrowings with borrower + item details
SELECT br.record_id, u.full_name, i.item_name, br.borrow_date, br.due_date, br.status
FROM borrow_records br
JOIN users u ON u.user_id = br.user_id
JOIN items i ON i.item_id = br.item_id
ORDER BY br.record_id DESC;

-- All currently overdue items
SELECT u.full_name, u.contact_number, i.item_name, br.due_date
FROM borrow_records br
JOIN users u ON u.user_id = br.user_id
JOIN items i ON i.item_id = br.item_id
WHERE br.status = 'overdue';

-- Per-user borrow count
SELECT u.full_name, COUNT(br.record_id) AS total_borrows
FROM users u
LEFT JOIN borrow_records br ON br.user_id = u.user_id
GROUP BY u.user_id, u.full_name
ORDER BY total_borrows DESC;
```

## 9. Example Search & Filter Queries

```sql
-- Search items by name or code
SELECT * FROM items WHERE item_name LIKE '%lap%' OR item_code LIKE '%lap%';

-- Filter records by status and date range
SELECT * FROM borrow_records
WHERE status = 'borrowed'
  AND borrow_date BETWEEN '2025-01-01' AND '2025-12-31';

-- Search borrower history
SELECT br.*, i.item_name FROM borrow_records br
JOIN items i ON i.item_id = br.item_id
WHERE br.user_id = 2 AND i.item_name LIKE '%book%';
```

---

## 10. Sample SMS API Integration (Semaphore)

See `sms/semaphore.php`. Core call:

```php
$ch = curl_init('https://api.semaphore.co/api/v4/messages');
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => http_build_query([
    'apikey'     => 'YOUR_SEMAPHORE_API_KEY',
    'number'     => '09171234567',
    'message'    => 'Hi Juan, you borrowed Laptop Acer. Due: 2025-05-20.',
    'sendername' => 'BRMS',
  ]),
]);
$response = curl_exec($ch);
curl_close($ch);
```

Every send is also recorded in `sms_logs` for traceability.

---

## 11. Validation Highlights

- HTML5 `required`, `pattern`, `min` attributes on all forms (Bootstrap styling).
- Server-side checks in `admin/borrow.php`:
  - Prevents borrowing when `items.quantity <= 0`.
  - Prevents the same user from borrowing the same item twice while it is still active.
- Passwords stored using `password_hash()` (bcrypt).
- Prepared statements (`mysqli_stmt`) used throughout to prevent SQL injection.

---

## 12. Conclusion

The Borrowing and Returning Monitoring System demonstrates a complete, working 3-tier web application built with widely-taught beginner technologies (HTML/CSS/Bootstrap, PHP, MySQL, XAMPP). It covers the full transactional cycle—borrow, monitor, return—with real-world touches like SMS notifications, overdue tracking, and printable reports. The codebase is intentionally compact and well-commented so that a 2nd-year BSIT team can confidently present and defend it as a final project.
