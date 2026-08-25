## 2025-02-24 - CSV Formula Injection Escape Bypass via Control Chars and Space Prefixing
**Vulnerability:** CSV export escaping (`esc_csv`) only checked the first character of the raw string for `=`, `+`, `-`, and `@`. Attackers could bypass formula injection protection using control characters like tab (`\t`) or carriage return (`\r`), or space-padded formulas (e.g., ` =SUM(...)`).
**Learning:** Naively using `ltrim()` to clean input prior to trigger checks strips control characters like `\t` and `\r`, which are themselves valid CSV formula triggers in spreadsheet programs (e.g. Excel).
**Prevention:** Check for control character formula triggers on the raw string while trimming spaces specifically (`ltrim($string, ' ')`) to detect space-padded formula injection vectors without destroying control character triggers.

## 2025-03-05 - Missing capability check in AJAX handler
**Vulnerability:** `wp_ajax_` actions using `check_admin_referer()` without `current_user_can()` capability check allow authorization bypass by low-privileged authenticated users.
**Learning:** `check_admin_referer()` only checks CSRF nonces, not user roles/capabilities.
**Prevention:** Always verify `current_user_can()` before executing administrative actions or exporting data in AJAX handlers.

## 2025-03-10 - Unescaped CSV Column Headers Vulnerable to Formula Injection
**Vulnerability:** CSV export escaping (`esc_csv`) was applied to data rows, but header column names (`$fields = array_keys($post_datas)`) were output raw via `fputcsv()`. User-supplied form parameter keys starting with formula triggers (`=`, `+`, `-`, `@`, etc.) resulted in active formula injection in exported CSV column headers.
**Learning:** In dynamic form exports where column headers are derived from user-submitted form parameter keys, column header names themselves must be sanitized and passed through CSV formula escaping prior to output.
**Prevention:** Always apply `esc_csv()` to both the header row array and data row arrays when streaming CSV output.
