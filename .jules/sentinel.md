## 2025-05-18 - Missing Capability Checks on Custom AJAX Endpoints
**Vulnerability:** AJAX endpoints registered with `wp_ajax_*` verified CSRF via `check_admin_referer()`, but lacked capability checks using `current_user_can()`.
**Learning:** `check_admin_referer()` only checks nonce validity for CSRF defense; it does not restrict access by user role. Any logged-in user (e.g. Subscriber) could invoke the AJAX action if nonces are accessible.
**Prevention:** Always combine `check_admin_referer()` / `check_ajax_referer()` with an explicit `current_user_can()` check for sensitive AJAX handlers.
