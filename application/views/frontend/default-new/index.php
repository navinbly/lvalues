<?php
$language_dir = 'ltr';
$language_dirs = get_settings('language_dirs');
if($language_dirs){
	$current_language = $this->session->userdata('language');
	$language_dirs_arr = json_decode($language_dirs, true);
	if(array_key_exists($current_language, $language_dirs_arr)){
		$language_dir = $language_dirs_arr[$current_language];
	}
}

?>
<!DOCTYPE html>
<html lang="<?php echo getIsoCode('english'); ?>" dir="<?php echo $language_dir; ?>">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5.0, minimum-scale=0.86">
	<meta name="author" content="<?php echo get_settings('author') ?>" />

	<?php
	$site_name = get_settings('system_name');
	$default_description = trim(strip_tags((string)get_settings('website_description')));
	$default_description = $default_description !== '' ? $default_description : 'Lvalues helps students, parents, tutors, and professionals discover courses, tutors, live classes, and skill training.';
	$seo_title = trim(ucwords((string)$page_title)) . ' | ' . $site_name;
	$seo_description = $default_description;
	$seo_keywords = (string)get_settings('website_keywords');
	$seo_image = base_url('uploads/system/' . get_current_banner('banner_image'));
	$seo_type = 'website';
	$seo_canonical = current_url();
	$seo_json_ld = null;

	if ($page_name == 'course_page') {
		$course_details = $this->crud_model->get_course_by_id($course_id)->row_array();
		$seo_title = trim((string)($course_details['title'] ?? $page_title)) . ' | ' . $site_name;
		$seo_description = trim((string)($course_details['meta_description'] ?? ''));
		if ($seo_description === '') {
			$seo_description = strip_tags(htmlspecialchars_decode_($course_details['description'] ?? $default_description));
		}
		$seo_keywords = (string)($course_details['meta_keywords'] ?? $seo_keywords);
		$seo_image = $this->crud_model->get_course_thumbnail_url($course_id);
		$seo_type = 'article';
		$seo_json_ld = [
			'@context' => 'https://schema.org',
			'@type' => 'Course',
			'name' => $course_details['title'] ?? $page_title,
			'description' => ellipsis(trim(strip_tags($seo_description)), 155),
			'provider' => [
				'@type' => 'Organization',
				'name' => $site_name,
				'sameAs' => site_url(),
			],
			'url' => $seo_canonical,
		];
	} elseif ($page_name == 'blog_details') {
		$seo_title = trim((string)($blog_details['title'] ?? $page_title)) . ' | ' . $site_name;
		$seo_keywords = (string)($blog_details['keywords'] ?? $seo_keywords);
		$seo_description = strip_tags(htmlspecialchars_decode_($blog_details['description'] ?? $default_description));
		$blog_banner = 'uploads/blog/banner/' . ($blog_details['banner'] ?? '');
		if (!file_exists($blog_banner) || !is_file($blog_banner)) {
			$blog_banner = 'uploads/blog/banner/placeholder.png';
		}
		$seo_image = base_url($blog_banner);
		$seo_type = 'article';
	} elseif ($page_name == 'blogs') {
		$seo_title = trim((string)get_frontend_settings('blog_page_title')) . ' | ' . $site_name;
		$seo_description = trim((string)get_frontend_settings('blog_page_subtitle'));
		$seo_image = site_url('uploads/blog/page-banner/' . get_frontend_settings('blog_page_banner'));
	} elseif ($page_name == 'home' || $page_name == 'home_1' || $page_name == 'index') {
		$seo_title = 'Lvalues Online Learning, Tutors, Courses and Career Skills';
		$seo_description = 'Discover verified tutors, school support, IT training, online courses, live classes, certificates, and corporate learning programs with Lvalues.';
	}

	// Page-level SEO override support for modern public modules such as Mock Tests.
	if (!empty($seo_title_override)) {
		$seo_title = (string)$seo_title_override;
	}
	if (!empty($seo_description_override)) {
		$seo_description = (string)$seo_description_override;
	}
	if (!empty($seo_keywords_override)) {
		$seo_keywords = (string)$seo_keywords_override;
	}
	if (!empty($seo_image_override)) {
		$seo_image = (string)$seo_image_override;
	}
	if (!empty($seo_type_override)) {
		$seo_type = (string)$seo_type_override;
	}
	if (!empty($seo_canonical_override)) {
		$seo_canonical = (string)$seo_canonical_override;
	}
	if (!empty($seo_json_ld_override)) {
		$seo_json_ld = $seo_json_ld_override;
	}

	$seo_description = trim(strip_tags((string)$seo_description));
	$seo_description = $seo_description !== '' ? ellipsis($seo_description, 155) : ellipsis($default_description, 155);
	?>
	<title><?php echo html_escape($seo_title); ?></title>
	<meta name="keywords" content="<?php echo html_escape($seo_keywords); ?>"/>
	<meta name="description" content="<?php echo html_escape($seo_description); ?>" />
	<meta name="robots" content="index, follow, max-image-preview:large" />
	<link rel="canonical" href="<?php echo html_escape($seo_canonical); ?>" />

	<meta property="og:site_name" content="<?php echo html_escape($site_name); ?>" />
	<meta property="og:title" content="<?php echo html_escape($seo_title); ?>" />
	<meta property="og:description" content="<?php echo html_escape($seo_description); ?>" />
	<meta property="og:image" content="<?php echo html_escape($seo_image); ?>">
	<meta property="og:url" content="<?php echo html_escape($seo_canonical); ?>" />
	<meta property="og:type" content="<?php echo html_escape($seo_type); ?>" />
	<meta name="twitter:card" content="summary_large_image" />
	<meta name="twitter:title" content="<?php echo html_escape($seo_title); ?>" />
	<meta name="twitter:description" content="<?php echo html_escape($seo_description); ?>" />
	<meta name="twitter:image" content="<?php echo html_escape($seo_image); ?>" />
	<?php if (!empty($seo_json_ld)): ?>
		<script type="application/ld+json"><?php echo json_encode($seo_json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
	<?php endif; ?>

	<link rel="icon" href="<?php echo base_url('uploads/system/'.get_frontend_settings('favicon')); ?>" type="image/x-icon">
	<link rel="apple-touch-icon" sizes="180x180" href="<?php echo base_url('uploads/system/'.get_frontend_settings('favicon')); ?>">

	<?php include 'includes_top.php';?>

	<style type="text/css">
		<?php echo get_frontend_settings('custom_css'); ?>
	</style>

</head>
<body class="<?php echo $this->session->userdata('theme_mode'); ?>">
	<?php
	//user wishlist items
    $my_wishlist_items = array();
    if($user_id = $this->session->userdata('user_id')){
        $wishlist = $this->user_model->get_all_user($user_id)->row('wishlist');
        if($wishlist != ''){
            $my_wishlist_items = json_decode($wishlist, true);
        }
    }
    
	if($this->session->userdata('app_url')):
		include "go_back_to_mobile_app.php";
	endif;
	
	include 'header.php';

	if(get_frontend_settings('cookie_status') == 'active'):
    	include 'eu-cookie.php';
  	endif;
  	
  	if($page_name === null){
  		include $path;
  	}else{
		include $page_name.'.php';
	}
	include 'footer.php';
	include 'includes_bottom.php';
	include 'modal.php';
	include 'auth_modal.php';
	include 'chatbot_widget.php';
	include 'common_scripts.php';
	include 'init.php';
	?>

	<?php echo get_frontend_settings('embed_code'); ?>
	<script>
	(function () {
		function readableFromUrl(value) {
			var text = String(value || '').split(/[?#]/)[0].split('/').filter(Boolean).pop() || '';
			text = text.replace(/\.[a-z0-9]+$/i, '').replace(/[-_]+/g, ' ').trim();
			return text || 'Lvalues interface item';
		}

		document.querySelectorAll('img:not([alt]), img[alt=""]').forEach(function (img) {
			img.setAttribute('alt', readableFromUrl(img.getAttribute('src')));
		});

		document.querySelectorAll('button:not([aria-label])').forEach(function (button) {
			if (button.textContent.trim() || button.getAttribute('title')) {
				return;
			}
			button.setAttribute('aria-label', button.classList.contains('close') ? 'Close' : 'Action');
		});

		document.querySelectorAll('a:not([aria-label])').forEach(function (link) {
			if (link.textContent.trim() || link.getAttribute('title')) {
				return;
			}
			link.setAttribute('aria-label', readableFromUrl(link.getAttribute('href')));
		});
	})();
	</script>
</body>
</html>
