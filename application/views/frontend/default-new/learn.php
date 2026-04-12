<?php
/**
 * Learn Page (Book-style content layout)
 * Added on: 2026-01-25
 * Purpose:
 * - Left sidebar: course/tree navigation
 * - Right side: content body
 * - Loaded via Learn controller
 */
?>

<section class="content-page py-5">
    <div class="container-fluid">
        <div class="row">

            <!-- LEFT SIDEBAR -->
            <div class="col-md-3 border-end">
                <?php
                // Course / Topic Tree
                $this->load->view('frontend/default-new/content/content_sidebar');
                ?>
            </div>

            <!-- RIGHT CONTENT -->
            <div class="col-md-9">
                <?php
                // Actual learning content
                $this->load->view('frontend/default-new/content/content_body');
                ?>
            </div>

        </div>
    </div>
</section>
