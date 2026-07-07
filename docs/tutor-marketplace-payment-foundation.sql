-- Lvalues tutor marketplace payment foundation.
-- Apply via CodeIgniter migration 20260605080000_create_tutor_marketplace_payment_foundations.php.
-- Payments are intentionally disabled at this phase. Do not connect a gateway until
-- webhook verification, admin reconciliation, and payout approval workflows are ready.

-- Tables created:
-- 1. tutor_request_payments
--    Stores disabled one-time/monthly payment ledger records tied to tutor_requests.
-- 2. tutor_payment_renewals
--    Stores disabled monthly reminder/renewal placeholders with access blocked.
-- 3. tutor_session_feedback
--    Stores future feedback records tied to request/payment/session.

-- Future enablement checklist:
-- - Add verified payment gateway webhook.
-- - Only webhook/admin confirmation may set payment_status = 'paid'.
-- - Keep student funds held by admin/platform until session/service completion.
-- - Compute commission and tutor payable from immutable ledger records.
-- - Require admin approval before settlement_status moves to payout states.
-- - Unblock paid batch/session access only after payment_status = 'paid'.
