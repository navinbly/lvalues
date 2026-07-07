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

// blog catch-all (unlimited)
$route['blog/(.+)'] = 'blog/content/$1';

// plural pages (if you use them)
$route['blogs'] = "blog/blogs";
$route['blogs/(:any)'] = "blog/blogs/$1";
//End blog

// ===================== Content Exams (MCQ) =====================
// Frontend exam flow (by content node id)
$route['exam/(:num)']                  = 'exam/intro/$1';
$route['exam/start/(:num)']            = 'exam/start/$1';
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
// DOCX IMPORT (CKEditor) ROUTES
// Added on: 2026-02-07
// Purpose: CKEditor "Import DOCX" calls this endpoint and expects JSON
// Controller: AdminDocImport.php
// ----------------------------------------------------
$route['admin/doc-import/upload-docx'] = 'AdminDocImport/upload_docx';



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

$route['notifications'] = 'notifications/index';
$route['notifications/read/(:num)'] = 'notifications/read/$1';
$route['notifications/mark_all_read'] = 'notifications/mark_all_read';
