## 2025-03-05 - Missing capability check in AJAX handler
**Vulnerability:** `wp_ajax_` actions using `check_admin_referer()` without `current_user_can()` capability check allow authorization bypass by low-privileged authenticated users.
**Learning:** `check_admin_referer()` only checks CSRF nonces, not user roles/capabilities.
**Prevention:** Always verify `current_user_can()` before executing administrative actions or exporting data in AJAX handlers.
