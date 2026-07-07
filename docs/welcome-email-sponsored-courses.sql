-- Lvalues welcome email logs and sponsored course advertisements.
-- Preferred: run CodeIgniter migration 20260605090000_create_welcome_email_and_sponsored_courses.php.
-- Hostinger-compatible table definitions are in that migration.

-- Tables created:
-- email_delivery_logs
-- sponsored_courses

-- Notes:
-- - Welcome emails use the existing Email_model SMTP/mail configuration.
-- - email_delivery_logs records success, failed, and skipped welcome email events.
-- - sponsored_courses powers the admin "Sponsored Courses" module and homepage section.
-- - Homepage shows only status = 'published' records inside active date range.
-- - External registration links open with rel="noopener noreferrer".
