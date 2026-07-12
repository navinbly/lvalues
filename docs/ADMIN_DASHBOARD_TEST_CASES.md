# LVALUES Admin Dashboard — Test Cases

Scope: admin dashboard, sidebar navigation, and the real-time publish flow implemented in Phase 0/Phase 1 (July 2026).
Environment: `http://localhost/lvalues` (XAMPP) or production equivalent.
Test account: any admin user (local: `admin@gmail.com` / `1234` — never reuse these on production).

Automated version: run `docs\test_admin_dashboard.ps1` in PowerShell — it executes TC-01..TC-16 automatically.

---

## A. Login & access

| ID | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| TC-01 | Admin login | Open `/login`, submit admin email + password | Redirects into the app; `/admin/dashboard` loads with HTTP 200 |
| TC-02 | Dashboard blocked when logged out | Open `/admin/dashboard` in a private window | Redirected to `/login`, no dashboard content leaks |
| TC-03 | CSRF protection | POST to `/login/validate_login` without `lvalues_csrf_token` | Request rejected ("action not allowed"), no login happens |

## B. Dashboard content (Operations command center)

| ID | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| TC-04 | Command center renders | Load `/admin/dashboard` | "Operations command center" section visible with metric tiles: Tutor applications, Course approvals, Books/articles review, Payout exceptions, Question bank, Exam/mock tests, Students, Verified tutors |
| TC-05 | Metric tiles are accurate | Compare each tile number with its target page (e.g. Course approvals tile vs `/admin/courses?status=pending` row count) | Numbers match the underlying lists |
| TC-06 | Tiles are clickable | Click each metric tile | Each opens the correct management page (applications, pending courses, content review, payouts, question bank, exam list, student success, tutor performance) |
| TC-07 | Open-tasks pill | Note the "N open admin tasks" pill; approve/clear one pending item; reload dashboard | Count decreases accordingly (allow up to 60 s for the sidebar badge cache) |
| TC-08 | Dashboard performance | Measure `/admin/dashboard` load (browser devtools or script) with a large `enrol`/`lesson`/`users` table | Page returns in similar time regardless of table row counts (counts now use COUNT(*), not full fetches) |

## C. Sidebar navigation (restructured)

| ID | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| TC-09 | New groups present | Inspect sidebar | Groups appear in order: Dashboard, Users, Video Courses, **Content Studio**, **Exams & Mock Tests**, Learning Operations, Marketplace, Finance, Support, Analytics, Settings |
| TC-10 | Old mega-group removed | Search sidebar | No "Publish Books" group; no "Blogs" item (old dead redirect); no SEO/Growth/Certificates/Cohorts/Complaints/Escalations/Availability/Commission-Rules alias items |
| TC-11 | Every link is honest | Click every sidebar item once | Each page's title matches the menu label — no item opens a differently-named page |
| TC-12 | Badge counts | Create a pending item (e.g. tutor application), wait ≤ 60 s, reload any admin page | Verification Queue / Tutor Applications badge increments; badge queries are cached (no per-page slowdown) |

## D. Exam / mock test real-time publishing (Phase 0 core)

| ID | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| TC-13 | Publish Now from builder | Exams & Mock Tests → Exam Pattern Builder → Create: fill title, duration, instructions, one section with a question-bank pool, set Workflow = **Publish Now (live immediately)**, save | Success message "…live on the mock tests page now"; exam appears on public `/mock-tests` and its detail page opens anonymously |
| TC-14 | Edit stays live (regression) | Edit the published exam (change title/duration), Workflow stays "Publish Now", save | Exam **remains published**; public URL (slug) unchanged; changes visible on the live page immediately |
| TC-15 | Unpublish / re-publish buttons | On the exam list, click Unpublish → confirm; then Publish → confirm | After unpublish: status Draft, public URL returns 404. After publish: live again. Draft with no sections/questions refuses to publish with a clear message |
| TC-16 | Draft preview | On a draft exam, click "Preview Draft" | Opens `/mock-tests/{slug}?preview=1` with a yellow "Admin preview — NOT visible to students" banner; same URL without `?preview=1` in a private window returns 404 |
| TC-17 | Review workflow still works | Save an exam as "Submit for Review", open Admin Review Center, approve it | Approval publishes the exam (unchanged moderation path) |
| TC-18 | AJAX question pool | In the builder, choose Question Bank Exam + Category, click "Apply Pool Filter" | Pool loads via `admin/question_bank_pool` (check Network tab); page source does NOT contain the whole question bank; count shows "N active question(s) in pool" |

## E. Data integrity & audit

| ID | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| TC-19 | Publish is audited | Publish or unpublish an exam, open Admin Review Center → recent events (or `content_moderation_events` table) | An approve/unpublish event is recorded with the admin as actor |
| TC-20 | Workflow token enforced | Re-send a publish POST without `workflow_action_token` (or with a wrong one) | Request rejected with "could not be verified" (403) |

## F. Regression sweep (after any change)

| ID | Test case | Steps | Expected result |
|----|-----------|-------|-----------------|
| TC-21 | All admin pages render | Load: dashboard, users, instructors, content_nodes, content_nodes_pending, question_bank, content_public_exams, exam_pattern_builder, exam_analytics, moderation_center, system_settings | All HTTP 200, no PHP error/warning text in the page body |
| TC-22 | Public site unaffected | Load `/`, `/mock-tests`, `/books`, `/blog`, `/home/courses` anonymously | All HTTP 200; only published exams are listed |

---

## Known environment notes

- Local DB contains seeded test data created during verification: question-bank master "Test Exam Master" → section "General Aptitude" (the 4 active legacy questions were tagged with it), and a draft exam "Publish Flow Test Mock v3" you can use for TC-13..TC-16.
- Exam #1 ("GCS - Exam Exam") is a legacy node-based exam with no pattern sections — the new Publish button intentionally refuses it ("must contain at least one section"); it is published via the old flow and remains live.
- Sidebar badges refresh at most every 60 seconds (file cache `application/cache/lv_admin_nav_counts`). Delete that file to force refresh during testing.
