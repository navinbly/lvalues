<?php
    // course (default) | tutor
    $search_for = isset($search_for) ? $search_for : (isset($_GET['search_for']) ? $_GET['search_for'] : 'course');
    $search_for = strtolower(trim($search_for));
    if (!in_array($search_for, ['course', 'tutor'], true)) {
        $search_for = 'course';
    }

    // Course filters
    $selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';
    $selected_price    = isset($_GET['price']) ? $_GET['price'] : 'all';
    $selected_level    = isset($_GET['level']) ? $_GET['level'] : 'all';
    $selected_language = isset($_GET['language']) ? $_GET['language'] : 'all';
    $selected_rating   = isset($_GET['rating']) ? $_GET['rating'] : 'all';
    $selected_sorting  = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'all';

    // Tutor filters
    $selected_category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
    $selected_class_id    = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
    $selected_subject_id  = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
    $selected_mode        = isset($_GET['mode']) ? $_GET['mode'] : 'all';
    $selected_location    = isset($_GET['location']) ? $_GET['location'] : '';
    $selected_distance_km = isset($_GET['distance_km']) ? (int)$_GET['distance_km'] : 0;
    $selected_min_rating  = isset($_GET['min_rating']) ? (int)$_GET['min_rating'] : 0;
    $selected_fee_min     = isset($_GET['fee_min']) ? $_GET['fee_min'] : '';
    $selected_fee_max     = isset($_GET['fee_max']) ? $_GET['fee_max'] : '';
?>

<?php include "breadcrumb.php"; ?>


<section class="grid-view courses-list-view">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-4 col-12">
                <?php if ($search_for === 'tutor'): ?>
                    <?php include "tutors_page_sidebar.php"; ?>
                <?php else: ?>
                    <?php include "courses_page_sidebar.php"; ?>
                <?php endif; ?>
            </div>
            <div class="col-lg-9 col-md-9 col-sm-8"> 
                 <?php if ($search_for === 'tutor'): ?>
                    <?php include 'tutors_page_list_layout.php'; ?>

                    <?php if (!isset($tutors) || count($tutors) == 0): ?>
                        <div class="not-found w-100 text-center d-flex align-items-center flex-column">
                            <img loading="lazy" width="80px" src="<?php echo base_url('assets/global/image/not-found.svg'); ?>">
                            <h5><?php echo get_phrase('Tutor Not Found'); ?></h5>
                            <p><?php echo get_phrase('Sorry, try adjusting filters or using different keywords.') ?></p>
                        </div>
                    <?php endif; ?>
                 <?php else: ?>
                    <?php include 'courses_page_' . $layout . '_layout.php'; ?>

                    <?php if(count($courses) == 0): ?>
                        <div class="not-found w-100 text-center d-flex align-items-center flex-column">
                            <img loading="lazy" width="80px" src="<?php echo base_url('assets/global/image/not-found.svg'); ?>">
                            <h5><?php echo get_phrase('Course Not Found'); ?></h5>
                            <p><?php echo get_phrase('Sorry, try using more similar words in your search.') ?></p>
                        </div>
                    <?php endif; ?>
                 <?php endif; ?>
            </div>
        </div>
    </div>
</section>


<script type="text/javascript">
    function filterCourse(){
        //sorting value added to the filter form
        var sort_by = $('#sorting_select_input').val();
        $('#sorting_hidden_input').val(sort_by);

        $('#course_filter_form').submit();
    }

    function filterTutor(){
        $('#tutor_filter_form').submit();
    }
</script>