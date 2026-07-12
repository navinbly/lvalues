<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['default_controller'] = 'home';
$route['dashboard'] = 'home/smart_dashboard';
$route['404_override'] = 'home/page_not_found';
$route['sitemap.xml'] = 'seo/sitemap';
$route['robots.txt'] = 'seo/robots';
$route['certificate/(:any)']        = "addons/certificate/generate_certificate/$1";

//course bundles
$route['course_bundles/(:any)']                                = "addons/course_bundles/index/$1";
$route['course_bundles']                                    = "addons/course_bundles";
$route['course_bundles/search/(:any)']                        = "addons/course_bundles/search/$1";
$route['course_bundles/search/(:any)/(:any)']                = "addons/course_bundles/search/$1/$1";
$route['bundle_details/(:any)/(:any)']                      = "addons/course_bundles/bundle_details/$1";
$route['bundle_details/(:any)']                              = "addons/course_bundles/bundle_details/$1/$1";
$route['course_bundles/buy/(:any)']                          = "addons/course_bundles/buy/$1";
$route['home/my_bundles']                                      = "addons/course_bundles/my_bundles";
$route['home/bundle_invoice/(:any)']                          = "addons/course_bundles/invoice/$1";
//end course bundles

//ebook
$route['ebook/ebook_details/(:any)/(:any)'] = "addons/ebook/ebook_details/$1/$2";
$route['ebook'] = "addons/ebook/ebooks";
$route['ebook_manager/all_ebooks'] = "addons/ebook_manager/all_ebooks";
$route['ebook_manager/add_ebook'] = "addons/ebook_manager/add_ebook";
$route['ebook_manager/payment_history'] = "addons/ebook_manager/payment_history";
$route['ebook_manager/category'] = "addons/ebook_manager/category";
$route['ebook/buy/(:any)'] = "addons/ebook/buy/$1";
$route['home/my_ebooks'] = "addons/ebook/my_ebooks";
//end ebook

// Docs UI should open on /blog
$route['blog'] = "blog/content";

// exam pretty (must be before blog catch-all)
$route['blog/(.+)-exam'] = 'exam/by_pretty/$1';

// legacy blog details (must be before blog catch-all)
$route['blog/details/(:any)/(:num)'] = "blog/details/$1/$2";

// blog catch-all (unlimited)
$route['blog/(.+)'] = 'blog/content/$1';

// plural pages (if you use them)
$route['blogs'] = "blog/blogs";
$route['blogs/(:any)'] = "blog/blogs/$1";
//End blog

// ===================== Content Exams (MCQ) =====================

// Public content exams / mock tests
$route['books'] = 'library/index';
$route['books/state'] = 'library/state';
$route['books/(:any)'] = 'library/book/$1';
$route['mock-tests'] = 'exam/public_list';
$route['mock-tests/(:any)'] = 'exam/detail/$1';
$route['tutor-verification'] = 'home/tutor_verification';
$route['parents'] = 'home/parents';
$route['tutor-earnings'] = 'home/tutor_earnings';
// Backward-compatible old public exam URLs
$route['public-exams'] = 'exam/public_list';
$route['public-exams/(:any)'] = 'exam/detail/$1';
$route['exam/start/(:any)'] = 'exam/start/$1';

// Frontend exam flow (by content node id)
$route['exam/(:num)']                  = 'exam/intro/$1';
$route['exam/start/(:num)']            = 'exam/start/$1';
$route['exam/attempt/(:num)/(:any)/q/(:num)'] = 'exam/question/$1/$3/$2';
$route['exam/attempt/(:num)/(:any)/autosave'] = 'exam/autosave/$1/$2';
$route['exam/result/(:num)/(:any)']    = 'exam/result/$1/$2';
$route['exam/attempt/(:num)/q/(:num)'] = 'exam/question/$1/$2';
$route['exam/result/(:num)']           = 'exam/result/$1';

// Admin builder shortcut
$route['admin/content-exam/(:num)']    = 'admin/content_exam_builder/$1';

// ----------------------------------------------------
// LEARN (CONTENT / BOOK-STYLE BLOG) ROUTES
// Added on: 2026-01-25
// Purpose:
// - Enable clean, SEO-friendly URLs for learning content
// - Support hierarchical structure:
//   /learn/course/track/topic/page
// - Static for now, DB-backed later
// Controller: Learn.php
// ----------------------------------------------------

$route['learn'] = 'learn/index';
$route['learn/(:any)'] = 'learn/index/$1';
$route['learn/(:any)/(:any)'] = 'learn/index/$1/$2';
$route['learn/(:any)/(:any)/(:any)'] = 'learn/index/$1/$2/$3';
$route['learn/(:any)/(:any)/(:any)/(:any)'] = 'learn/index/$1/$2/$3/$4';

// ----------------------------------------------------


//Custom page
$route['page/(:any)'] = "page/index/$1";
//End Custom page

//tutor booking ..... tutor_booking/tutors
$route['tutors'] = "home/tutors";
$route['tutors/(:any)'] = "addons/tutor_booking/list_of_tuitions/$1";
$route['tutor/filter'] = "addons/tutor_booking/list_of_tuitions_after_filter";
$route['schedules_bookings/(:any)'] = "addons/tutor_booking/tutor_details/$1";
$route['my_bookings'] = "addons/tutor_booking/booked_schedules_student";
//End tutor booking

$route['translate_uri_dashes'] = FALSE;

// ----------------------------------------------------
// ----------------------------------------------------
// DOCX IMPORT (CKEditor) ROUTES
// Added on: 2026-02-07
// Purpose: CKEditor "Import DOCX" calls this endpoint and expects JSON
// Controller: AdminDocImport.php
// ----------------------------------------------------
$route['admin/doc-import/upload-docx'] = 'AdminDocImport/upload_docx';

// ----------------------------------------------------
// SECURITY ALERTS (Suspicious Login)
// ----------------------------------------------------
$route['security/confirm/yes/(:any)'] = 'Security_login/confirm_yes/$1';
$route['security/confirm/no/(:any)']  = 'Security_login/confirm_no/$1';



// Tutor signup hierarchy

// External OAuth/OIDC login
$route['login/oauth/(:any)'] = 'login/oauth/$1';
$route['login/oauth-callback/(:any)'] = 'login/oauth_callback/$1';
$route['sign_up'] = 'Sign_up/index';
$route['sign_up/verification_code'] = 'Sign_up/verification_code';
$route['tutor-api/registration-tree'] = 'Tutor_api/registration_tree';
$route['tutor-api/classes-by-category'] = 'Tutor_api/classes_by_category';
$route['tutor-api/subjects-by-class'] = 'Tutor_api/subjects_by_class';


$route['user/tutor_teaching_profile'] = 'User/tutor_teaching_profile';
$route['user/update_tutor_teaching_profile'] = 'User/update_tutor_teaching_profile';
$route['user/tutor_batches'] = 'tutor_batch/index';
$route['user/payment_settings'] = 'User/payout_settings';
$route['home/student_batches'] = 'student_batch/my_batches';
$route['home/notifications'] = 'notifications/index';

$route['student_batch/submit_assignment/(:num)'] = 'student_batch/submit_assignment/$1';

$route['tutor_batch/evaluate_assignment/(:num)'] = 'tutor_batch/evaluate_assignment/$1';

$route['tutor_batch/create_test/(:num)'] = 'tutor_batch/create_test/$1';
$route['tutor_batch/add_test_question/(:num)'] = 'tutor_batch/add_test_question/$1';
$route['tutor_batch/publish_test/(:num)'] = 'tutor_batch/publish_test/$1';

$route['student_batch/start_test/(:num)'] = 'student_batch/start_test/$1';
$route['student_batch/submit_test/(:num)'] = 'student_batch/submit_test/$1';

$route['tutor_batch/attendance/(:num)'] = 'tutor_batch/attendance/$1';
$route['tutor_batch/save_attendance/(:num)'] = 'tutor_batch/save_attendance/$1';

$route['tutor_batch/update_session_links/(:num)'] = 'tutor_batch/update_session_links/$1';
$route['student_batch/join_session/(:num)'] = 'student_batch/join_session/$1';
$route['student_batch/request_enrollment/(:num)'] = 'student_batch/request_enrollment/$1';

$route['notifications'] = 'notifications/index';
$route['notifications/read/(:num)'] = 'notifications/read/$1';
$route['notifications/mark_all_read'] = 'notifications/mark_all_read';

// Communication automation and bulk messaging
$route['admin/communication-center'] = 'communication/admin';
$route['user/student-communication'] = 'communication/tutor';
$route['communication/preview/(:any)'] = 'communication/preview/$1';
$route['communication/send/(:any)'] = 'communication/send/$1';
$route['communication/retry/(:num)'] = 'communication/retry/$1';
$route['communication/preferences'] = 'communication/preferences';

// Teacher workflow and provider-neutral live learning
$route['teacher-workspace'] = 'teacher_workflow/workspace';
$route['teacher-workflow/calendar'] = 'teacher_workflow/calendar';
$route['teacher-workflow/complete/(:num)'] = 'teacher_workflow/complete_item/$1';
$route['teacher-workflow/autosave'] = 'teacher_workflow/autosave';
$route['teacher-workflow/versions'] = 'teacher_workflow/versions';
$route['teacher-workflow/template/(:num)'] = 'teacher_workflow/create_template/$1';
$route['teacher-workflow/use-template/(:num)'] = 'teacher_workflow/use_template/$1';
$route['teacher-workflow/duplicate/(:num)'] = 'teacher_workflow/duplicate_batch/$1';
$route['teacher-workflow/reschedule/(:num)'] = 'teacher_workflow/reschedule_session/$1';
$route['teacher-workflow/enrollment/(:num)/(:any)'] = 'teacher_workflow/review_enrollment/$1/$2';
$route['teacher-workflow/health/(:num)'] = 'teacher_workflow/refresh_health/$1';
$route['live-learning/providers'] = 'live_learning/providers';
$route['live-learning/connection-test'] = 'live_learning/connection_test';
$route['live-learning/join/(:num)'] = 'live_learning/join/$1';
$route['live-learning/consent/(:num)'] = 'live_learning/consent/$1';
$route['live-learning/incident/(:num)'] = 'live_learning/incident/$1';
$route['live-learning/playback/(:num)'] = 'live_learning/playback/$1';
$route['live-learning/sync-attendance/(:num)'] = 'live_learning/sync_attendance/$1';
$route['live-learning/analytics/(:num)'] = 'live_learning/analytics/$1';
$route['course-workflow/snapshot/(:num)'] = 'course_workflow/snapshot/$1';
$route['course-workflow/restore/(:num)'] = 'course_workflow/restore/$1';
$route['course-workflow/duplicate/(:num)'] = 'course_workflow/duplicate/$1';
$route['course-workflow/transition/(:num)/(:any)'] = 'course_workflow/transition/$1/$2';
$route['course-workflow/schedule/(:num)'] = 'course_workflow/schedule/$1';
$route['course-workflow/accessibility/(:num)'] = 'course_workflow/accessibility/$1';
$route['course-workflow/library/save/(:num)'] = 'course_workflow/save_lesson/$1';
$route['course-workflow/library/use/(:num)'] = 'course_workflow/use_lesson/$1';
$route['assessment-center'] = 'assessment_workflow/index';
$route['assessment-center/rubric'] = 'assessment_workflow/rubric';
$route['assessment-center/feedback'] = 'assessment_workflow/feedback';
$route['assessment-center/bulk-grade'] = 'assessment_workflow/bulk_grade';
$route['assessment-center/plagiarism/(:num)'] = 'assessment_workflow/plagiarism/$1';
$route['assessment-center/moderate-attempt/(:num)'] = 'assessment_workflow/moderate_attempt/$1';
$route['assessment-center/rebuild-mastery'] = 'assessment_workflow/rebuild_mastery';
$route['assessment-center/parent-summary/(:num)'] = 'assessment_workflow/parent_summary/$1';
$route['teacher-analytics'] = 'analytics_quality/teacher';
$route['teacher-analytics/intervention'] = 'analytics_quality/intervention';
$route['teacher-analytics/intervention/(:num)/(:any)'] = 'analytics_quality/intervention_status/$1/$2';
$route['teacher-analytics/progress/(:num)/(:num)'] = 'analytics_quality/progress_pdf/$1/$2';
$route['teacher-analytics/parent/(:num)/(:num)'] = 'analytics_quality/parent_pdf/$1/$2';
