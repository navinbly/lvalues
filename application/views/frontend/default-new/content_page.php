<?php
/**
 * Page Name  : Content Page
 * Purpose    : Book-style learning content UI
 * Layout     : Uses frontend/default-new/index.php
 * Created On : 2026-01-24
 */
?>

<section class="content-page py-5">
    <div class="container-fluid">
        <div class="row">

            <!-- LEFT SIDEBAR : Content Tree -->
            <div class="col-md-3 border-end">
                <?php
                /**
                 * Sidebar Tree
                 * Shows hierarchical course structure
                 */
                $this->load->view('frontend/default-new/content/content_sidebar');
                ?>
            </div>

            <!-- RIGHT CONTENT : Main Content -->
            <div class="col-md-9">
                <?php
                /**
                 * Content Body
                 * Displays selected node content
                 */
                $this->load->view('frontend/default-new/content/content_body');
                ?>
            </div>

        </div>
    </div>
</section>
