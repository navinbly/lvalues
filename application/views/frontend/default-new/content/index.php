<?php
/**
 * ---------------------------------------------------------
 * Learn Content Main Layout
 * ---------------------------------------------------------
 * File: content/index.php
 * Purpose:
 * - Acts as container for Learn (book-style) pages
 * - Loads sidebar (tree navigation)
 * - Loads right-side content dynamically
 *
 * Controller: Learn.php
 * Route: /learn/...
 *
 * Status:
 * - Static content (Phase 1)
 * - DB-backed later (Phase 2)
 * ---------------------------------------------------------
 */

// Safety fallback
$course = $course ?? null;
$track  = $track ?? null;
$topic  = $topic ?? null;
$page   = $page ?? null;
?>

<section class="learn-page py-5">
    <div class="container-fluid">
        <div class="row">

            <!-- ========================================= -->
            <!-- LEFT SIDEBAR : CONTENT TREE -->
            <!-- ========================================= -->
            <div class="col-md-3 border-end">
                <?php
                /**
                 * Sidebar:
                 * - Shows course → topic → page tree
                 * - Uses URL based navigation
                 */
                $this->load->view('frontend/default-new/content/content_sidebar', [
                    'active_course' => $course,
                    'active_track'  => $track,
                    'active_topic'  => $topic,
                    'active_page'   => $page
                ]);
                ?>
            </div>

            <!-- ========================================= -->
            <!-- RIGHT CONTENT : PAGE BODY -->
            <!-- ========================================= -->
            <div class="col-md-9">
                <?php
                /**
                 * Content Body:
                 * - Displays actual lesson/page content
                 * - Based on URL parameters
                 */
                $this->load->view('frontend/default-new/content/content_body', [
                    'course' => $course,
                    'track'  => $track,
                    'topic'  => $topic,
                    'page'   => $page
                ]);
                ?>
            </div>

        </div>
    </div>
</section>
