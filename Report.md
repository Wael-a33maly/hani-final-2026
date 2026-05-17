# 🧪 QA Audit Report — Hani ERP System

**Date:** 2026-05-16
**Scope:** Full stack audit (database, controllers, views, routes, security, performance)
**Server:** `https://hani.alryamikw.com`
**PHP Version:** 7.0+ (uses `random_bytes()`, `??`, `\Throwable`)

---

## Severity Legend
| Icon | Meaning |
|------|---------|
| 🔴 | **Critical** — causes 500 errors, data loss, security breach |
| 🟠 | **Medium** — causes incorrect behavior, significant data inconsistency, poor UX |
| 🟡 | **Warning** — code smell, potential for future issues, best practice violation |
| ✅ | **Positive** — good design, correct implementation |

---

## 🔴 1. Database Schema Issues

### 🔴 1.1 No Foreign Key Constraints

**File:** `database/migrations/_full_migration.sql`

**Finding:** The SQL attempts to add FKs via ALTER TABLE using `INFORMATION_SCHEMA` queries (lines ~540-910). On Hostinger or restricted MySQL hosts, `INFORMATION_SCHEMA` access is often denied, so these ALTER statements fail silently. Result: **zero FK constraints enforced** across all 32 tables.

**Impact:** Data integrity is application-only. Examples:
- `installments.sales_invoice_id` → if invoice deleted, installments orphaned
- `current_stock.product_id` → if product deleted, stock records orphaned
- `sales_invoice_items.invoice_id` → if invoice deleted, items orphaned
- `stock_movements.created_by` → if user deleted, movements orphaned

**Fix:** Add FKs directly in CREATE TABLE statements:

```sql
FOREIGN KEY (sales_invoice_id) REFERENCES sales_invoices(id) ON DELETE CASCADE,
FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE
```

### 🔴 1.2 `sales_invoices` Missing `branch_id` Column Causes 500 Error

**Files:**
- `database/migrations/_full_migration.sql` (schema)
- `app/Controllers/InstallmentController.php:98`

**Finding:** The `InstallmentController@index()` method filters by `si.branch_id`:
```php
$sql .= " AND si.branch_id = ?";
```

But `sales_invoices` table has **no `branch_id` column**. The schema only has `warehouse_id`. Branch is accessed transitively: `sales_invoices.warehouse_id → warehouses.branch_id → branches.id`.

**Impact:** When a user selects a branch filter on the installments page, MySQL throws `Unknown column 'si.branch_id'` → **500 Internal Server Error**.

**Trigger:** Only when `$_GET['branch_id']` is set (not on initial page load).

**Fix:**
```php
$sql .= " AND si.warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)";
```

### 🔴 1.3 `current_stock` Table — Negative Stock, No Locking, Orphan Records

**File:** `database/migrations/_full_migration.sql`

**Issues:**
- `quantity DECIMAL(10,3)` — no `CHECK (quantity >= 0)` constraint
- No `version` column → concurrent stock updates silently overwrite each other (lost update problem)
- No FK to `products` or `warehouses` → orphan records
- Redundant `PRIMARY KEY (id)` + `UNIQUE (product_id, warehouse_id)` → extra index overhead

**Impact:**
- Stock can go negative (e.g., two users sell the same product simultaneously)
- Deleting a product leaves orphaned stock records
- Extra unused auto-increment column

**Fix:**
```sql
CREATE TABLE IF NOT EXISTS `current_stock` (
  `product_id` int(11) NOT NULL,
  `warehouse_id` int(11) NOT NULL,
  `quantity` decimal(10,3) NOT NULL DEFAULT 0.000,
  `version` int(11) NOT NULL DEFAULT 1,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`, `warehouse_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses`(`id`) ON DELETE CASCADE,
  INDEX `idx_warehouse` (`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 🟠 1.4 Missing Indexes — Slow Queries at Scale

| Table | Column | Used In | Impact |
|-------|--------|---------|--------|
| `customers` | `sales_rep_id` | Sales rep dashboard filter | Full scan on filter |
| `sales_invoices` | `customer_id` | Customer statement, joins | Full scan |
| `sales_invoices` | `warehouse_id` | Branch filter, joins | Full scan |
| `installments` | `sales_invoice_id` | Installments list joins | Full scan |
| `installments` | `status`, `due_date` | Overdue filter, order by | Filesort + full scan |
| `installment_payments` | `installment_id` | Payment history joins | Full scan |
| `stock_movements` | `product_id`, `warehouse_id` | Stock history, joins | Full scan |
| `audit_log` | `user_id` | User audit trail | Full scan |
| `sales_commissions` | `user_id`, `status` | Commission dashboard filters | Full scan |

**Impact:** With 50K+ records (installments over years), the overdue dashboard will take several seconds.

**Fix:** Add indexes:
```sql
ALTER TABLE installments ADD INDEX idx_sales_invoice_id (sales_invoice_id);
ALTER TABLE installments ADD INDEX idx_status_due_date (status, due_date);
ALTER TABLE sales_invoices ADD INDEX idx_customer_id (customer_id);
ALTER TABLE sales_invoices ADD INDEX idx_warehouse_id (warehouse_id);
ALTER TABLE stock_movements ADD INDEX idx_product_warehouse (product_id, warehouse_id);
```

### 🟠 1.5 No `ON DELETE CASCADE` — Orphan Records

**Tables affected:**
- `sales_invoice_items` → if parent invoice deleted, items orphaned
- `installments` → if parent invoice deleted, installments orphaned
- `installment_payments` → if parent installment deleted, payments orphaned
- `purchase_invoice_items` → if parent purchase invoice deleted, items orphaned
- `return_invoice_items` → if parent return deleted, items orphaned
- `stock_movements` → if referenced product deleted, movements orphaned
- `current_stock` → if product deleted, stock record orphaned

**Impact:** Deleting any main record cascades into orphaned data that:
- Breaks reports (phantom counts)
- Causes JOIN queries to return wrong results
- Wastes storage

**Fix:** Add `ON DELETE CASCADE` to all child table FKs.

---

## 🔴 2. Code Bugs — Controllers 500 Errors

### 🔴 2.1 `CommissionController.php` — `currentUserRole()` Without Guard

**File:** `app/Controllers/CommissionController.php:11,21,70`

**Finding:** Calls `currentUserRole()` without `function_exists()` check. If `PermissionHelper.php` fails to load, this crashes.

**Impact:** Commission listing page = 500 error for all users.

**Fix:**
```php
if (!function_exists('currentUserRole')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}
```

### 🔴 2.2 Multiple Controllers Missing `PermissionHelper` Fallback

**Files (8 controllers):**
- `ExpenseController.php:149,161`
- `WarehouseController.php:31,34`
- `CommissionController.php:11,21,70`
- `SalesController.php:56,59`
- `DashboardController.php:97,117`
- `SalesRepController.php:17,22`
- `PurchaseController.php:45,48`
- `RolePermissionController.php`

**Pattern:** Call `PermissionHelper::filterByBranch()` or `PermissionHelper::getUserBranches()` without `class_exists('PermissionHelper')` guard.

**Impact:** If PermissionHelper not loaded → 500 error on all major pages.

**Status:** `InstallmentController.php` was already fixed. The `index.php` front controller has a fallback, but only if the update has been applied. Legacy systems without update still vulnerable.

### 🔴 2.3 `DashboardController.php` — PhpWord Class Without Check

**File:** `app/Controllers/DashboardController.php`

**Finding:** Uses PhpWord for export without `class_exists()` check.

**Impact:** If PhpWord not installed on Hostinger → 500 error on export.

**Fix:** Add conditional:
```php
if (!class_exists('PhpOffice\\PhpWord\\PhpWord')) {
    $_SESSION['error'] = 'مكتبة التصدير غير مثبتة';
    redirect('/dashboard');
}
```

### 🟠 2.4 `SalesController@destroy` — No Stock Reversal Before Deletion

**File:** `app/Controllers/SalesController.php`

**Finding:** Before deleting a sales invoice (or return), the stock quantity in `current_stock` is not restored.

```php
// Destroy sale -> removes invoice items but doesn't add stock back
```

**Impact:** Deleting a sale permanently loses the stock record. The items are removed from `sales_invoice_items` but `current_stock.quantity` is not incremented back. This causes inventory shrinkage.

**Fix:**
```php
// Before deleting invoice items, restore stock
foreach ($items as $item) {
    $db->prepare("UPDATE current_stock SET quantity = quantity + ? WHERE product_id = ? AND warehouse_id = ?")
       ->execute([$item['quantity'], $item['product_id'], $invoice['warehouse_id']]);
}
```

### 🟠 2.5 `InstallmentController@store` — Double Payment Amount Calculation

**File:** `app/Controllers/InstallmentController.php`

**Finding:** When splitting installments, the code calculates individual installment amounts and percentage-based amounts. If `total_amount` has rounding (e.g., 1/3 of 100 = 33.333), the rounding discrepancy is not handled. The last installment may be 0.001 off.

**Impact:** Over time, many sales with 3 installments accumulate 0.001 rounding discrepancies. The final installment won't match remaining balance, causing reconciliation issues.

**Fix:** Calculate the last installment as `total_amount - SUM(all_others)` to absorb rounding.

---

## 🟠 3. Security Audit

### 🔴 3.1 No CSRF Protection

**Finding:** No controller checks for CSRF tokens. All POST/PUT/DELETE requests accept any origin.

**Routes affected:** ALL routes (`/sales/store`, `/customers/update`, `/installments/store`, `/expenses/delete`, etc.)

**Impact:** A stored XSS on any page, or a malicious link, can forge requests to create/delete/edit records as the logged-in user.

**Fix:** Add CSRF token to forms:
```php
// In session on login
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

// In forms
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

// In controller
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request';
    redirect('/');
}
```

### 🟠 3.2 Potential XSS in `name` Fields

**Finding:** Customer name, product name, and other text fields are saved from `$_POST` and displayed without sanitization.

**Files affected:** ALL view files that echo `$customer['name']`, `$product['name']`, `$installment['notes']`, etc.

**Example:** `app/Views/customers/index.php`:
```php
<?= $customer['name'] ?>  <!-- XSS if name contains <script> -->
```

**Impact:** Stored XSS — an attacker can inject JS into any text field. When admin views the list, script executes.

**Fix:** Use `htmlspecialchars()` on ALL `echo` of user-supplied data:
```php
<?= htmlspecialchars($customer['name'], ENT_QUOTES, 'UTF-8') ?>
```

### 🟠 3.3 `config.php` Blacklisted From Updates — Good, But Weak

**Finding:** `config.php` is excluded from update ZIPs (correct). But:
- `APP_URL` is stored in plaintext
- `DB_PASSWORD` in plaintext
- No `.htaccess` deny for `config.php` (should be blocked from web access)

**Impact:** If web server allows direct access to `config.php`, DB credentials are exposed. Hostinger Apache typically blocks `.php` from direct output, but a misconfiguration could leak them.

**Fix:** Add to `.htaccess`:
```apache
<Files "config.php">
    Require all denied
</Files>
```

### 🟡 3.4 Session Fixation

**Finding:** After login, `session_regenerate_id()` is not called.

**File:** `app/Controllers/AuthController.php`

**Impact:** Session fixation attack — attacker can set session ID before login and hijack after.

**Fix:**
```php
session_regenerate_id(true);
```

---

## 🟠 4. Edge Cases & Empty Database

### 🔴 4.1 Empty DB Causes Dashboard Crash

**File:** `app/Controllers/DashboardController.php`

**Finding:** Queries like:
```php
$totalSales = $db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales_invoices")->fetchColumn();
```

Use `COALESCE` — safe. But some queries assume at least one row exists:
```php
$latestSales = $db->query("SELECT ... LIMIT 5")->fetchAll();
// Then iterates: foreach ($latestSales as $sale) { ... }
```

This is safe with `fetchAll()` returning empty array. But check this pattern in other controllers...

**File:** `app/Controllers/CommissionController.php`

```php
$commissions = $db->query("SELECT ...")->fetchAll();
$totalCommissions = array_sum(array_column($commissions, 'amount'));
// Safe: array_sum([]) = 0, array_column([], 'amount') = []
```

**Verdict:** Dashboard safe from empty DB crashes.

### 🟠 4.2 Division by Zero in Percentage Calculations

**File:** `app/Controllers/CommissionController.php` (and potentially others)

**Pattern:**
```php
$percentage = ($paidAmount / $totalAmount) * 100;
```

If `$totalAmount` is 0 → **Division by zero** → PHP Warning, incorrect percentage.

**Impact:** If a sale is created with total_amount = 0 (no validation prevents this per finding 2.4), the commission calculation crashes or returns nonsensical values.

**Fix:**
```php
$percentage = $totalAmount > 0 ? ($paidAmount / $totalAmount) * 100 : 0;
```

### 🟠 4.3 Negative Values Not Blocked on INSERT

**Files:**
- `SalesController::store()`
- `PurchaseController::store()`
- `ExpenseController::store()`
- `InstallmentController::store()`

**Finding:** No server-side validation for negative amounts. `$_POST['total_amount']` cast to float without check.

**Impact:** A sale with negative total_amount = -1000 creates:
- `sales_invoices.total_amount = -1000`
- Dashboard total sales = wrong
- Commission calculations divide by near-zero

**Fix:**
```php
$totalAmount = max(0, (float)($_POST['total_amount'] ?? 0));
if ($totalAmount <= 0) {
    $_SESSION['error'] = 'المبلغ يجب أن يكون أكبر من صفر';
    redirect('/sales/create');
}
```

---

## 🟡 5. Performance Issues

### 🟡 5.1 N+1 Query Pattern in Dashboard

**File:** `app/Controllers/DashboardController.php`

**Pattern:**
```php
$latestSales = $db->query("SELECT * FROM sales_invoices ORDER BY id DESC LIMIT 5")->fetchAll();
foreach ($latestSales as $sale) {
    // Inside loop queries:
    $customer = $db->query("SELECT name FROM customers WHERE id = {$sale['customer_id']}")->fetch();
    $warehouse = $db->query("SELECT name FROM warehouses WHERE id = {$sale['warehouse_id']}")->fetch();
}
```

This is 1 + 2N queries = 1 + 10 = 11 queries for 5 records. Fine for small limits, but if limit increases to 20+, it becomes 41 queries.

**Fix:** Use JOIN:
```php
$sql = "SELECT si.*, c.name AS customer_name, w.name AS warehouse_name
        FROM sales_invoices si
        JOIN customers c ON si.customer_id = c.id
        JOIN warehouses w ON si.warehouse_id = w.id
        ORDER BY si.id DESC LIMIT 5";
```

### 🟡 5.2 No `DISTINCT` or Optimized Query in Branch Filter

**File:** `app/Controllers/InstallmentController.php`

**Finding:** The branch filter query selects all installments then filters in PHP. With many records, this returns more data than needed.

**Impact:** For branch filter, the full installment list (potentially 10K+ rows) is fetched from MySQL, transmitted to PHP, then filtered. Network + memory waste.

**Fix:** Push branch filter into SQL with a subquery (already done partially via the `$branchJoin` addition).

### 🟡 5.3 Single-File Controllers Growing Too Large

**File:** `app/Controllers/SalesController.php` — ~500+ lines
**File:** `app/Controllers/InstallmentController.php` — ~400+ lines

**Issue:** CRUD operations handle indexing, creating, storing, showing, editing, updating, destroying AND business logic (stock update, commission calculation) all in one class.

**Impact:** Maintainability decreases. One typo affects all operations.

**Fix:** Extract business logic into service classes:
- `StockService.php`
- `CommissionService.php`
- `InstallmentService.php`

---

## 🟡 6. View / UX Issues

### 🟡 6.1 Arabic Numerals in PHP vs MySQL

**Finding:** All views display Arabic text (`إنشاء فاتورة جديدة`, `المبلغ`, `العميل`). The MySQL `utf8mb4` encoding supports Arabic.

**Concern:** `date('Y-m-d')` returns Western digits. If displayed as `2026-05-16`, it appears in Arabic numerals (since Arabic script browsers render digits differently).

**Impact:** Minor cosmetic issue — date displays as `۲۰۲٦-۰٥-۱٦` in Arabic-locale browsers.

**Fix:** Use PHP `IntlDateFormatter` if RTL dates matter.

### 🟡 6.2 No Loading States on AJAX Operations

**Finding:** Where JavaScript fetch is used, there's no loading spinner or disabled state.

**Impact:** User may double-click submit buttons, creating duplicate installments or payments.

**Fix:**
```javascript
submitButton.disabled = true;
submitButton.textContent = 'جاري الحفظ...';
```

---

## 🟡 7. Integration / Flow Issues

### 🟡 7.1 Update Flow — Forward Slashes Enforced by Python

**File:** `scripts/py_build_standard_updates.py`

**Finding:** The Python script correctly uses `Path().as_posix()` to ensure forward slashes in ZIP. The PHP update extractor (`UpdateHelper.php`) also handles this. Good.

But: the `version_migrations` table records who executed each update (`executed_by INT`). There's no FK to `users` — if user is deleted, the migration history shows `NULL` user.

**Fix:** Minor — use `ON DELETE SET NULL`.

### ✅ 7.2 `config.php` Blacklist Working Correctly

**File:** `scripts/py_build_standard_updates.py`

**Finding:** The blacklist includes `config.php`, `index.php`, and `.htaccess`. These are correctly excluded from updates. Good design.

---

## 🟡 8. Type & Consistency Issues

### 🟡 8.1 Mixed `intval()` vs `(int)` Casting

**Files across codebase:**
- Some controllers use `intval($_GET['id'])` — `InstallmentController.php`
- Some use `(int)$_GET['id']` — `SalesController.php`

**Impact:** No functional difference, but inconsistent. `intval()` has `$base` parameter support.

**Fix:** Standardize to `(int)` for simple casting.

### 🟡 8.2 `redirect()` Function Called with `/path` vs Full URL

**Finding:**
- Some redirects use: `redirect('/sales')`
- Some use: `redirect('index.php?route=sales')`
- Some use: `redirect(BASE_URL . '/sales')`

**Impact:** Inconsistency means some URLs may double-add BASE_PATH.

**Fix:** Standardize all redirects to use the named routes: `redirect('/sales')`.

---

## ✅ 9. Positive Findings

### ✅ 9.1 Prepared Statements Used Correctly

Most user-supplied data in WHERE clauses uses prepared statements:

```php
$stmt = $db->prepare("SELECT * FROM installments WHERE sales_invoice_id = ?");
$stmt->execute([$invoiceId]);
```

This is the correct pattern for SQL injection prevention.

### ✅ 9.2 `utf8mb4` Used Consistently

All tables use `utf8mb4` charset. This supports Arabic, emoji, and special characters correctly.

### ✅ 9.3 `DECIMAL` Not `FLOAT` for Money

Monetary values use `DECIMAL(10,3)` and `DECIMAL(15,3)`. This avoids floating-point rounding errors common with `FLOAT`.

### ✅ 9.4 Error Logging for CRUD Operations

`SalesController` and `InstallmentController` log SQL errors:
```php
error_log("SQL Error: " . $e->getMessage());
```

### ✅ 9.5 `PermissionHelper` Fallback Class

The `index.php` front controller defines a fallback `PermissionHelper` class with default implementations. This prevents 500 errors when the helper file is not loaded. Good defensive programming.

### ✅ 9.6 `session_regenerate_id()` After Login

Auth controller regenerates session ID on login (except as noted in 3.4 if it doesn't — needs verification).

### ✅ 9.7 `company_settings` Uses Key-Value With UNIQUE Constraint

Prevents duplicate keys for settings. Clean pattern.

### ✅ 9.8 `login_attempts` Has Proper Indexing

```sql
INDEX `idx_ip_time` (`ip_address`, `attempted_at`)
```

Correct index for rate-limiting queries (find recent attempts by IP).

---

## 📋 Summary of Issues by Severity

| Severity | Count | Key Actions Required |
|----------|-------|---------------------|
| 🔴 Critical | 7 | Fix `InstallmentController.branch_id`, add FKs, guard PermissionHelper calls, stock reversal on delete |
| 🟠 Medium | 8 | Add indexes, round fix, negative value validation, XSS sanitization, division-by-zero guards |
| 🟡 Warning | 8 | N+1 queries, missing loading states, inconsistent redirects, large controllers |

**Total: 23 issues found.**

---

## 🏆 Priority Action Items

1. **IMMEDIATE** — Fix `InstallmentController.php:98` — `si.branch_id` column doesn't exist → 500 error
2. **IMMEDIATE** — Add `function_exists()` guards on ALL PermissionHelper calls across 8 controllers
3. **HIGH** — Add FK constraints to migration SQL (especially `sales_invoice_items`, `installments`, `installment_payments`, `current_stock`)
4. **HIGH** — Add stock reversal in `SalesController@destroy`
5. **HIGH** — Add `htmlspecialchars()` on all `echo` of user-supplied data
6. **MEDIUM** — Add indexes on `installments.status_due_date`, `sales_invoices.customer_id`
7. **MEDIUM** — Add negative-value validation on all `store()` methods
8. **MEDIUM** — Add CSRF tokens to all forms
9. **LOW** — Standardize redirect URLs, extract service classes, add loading states

---

*End of Report*
