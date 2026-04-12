<?php if(get_frontend_settings('blog_visibility_on_the_home_page') == 1): ?>
<?php
$latest_docs = isset($latest_docs) && is_array($latest_docs) ? $latest_docs : [];
?>
<?php if(!empty($latest_docs)): ?>
<section class="courses blog py-5">
    <div class="container">
        <h1 class="text-center pt-0"><span><?php echo site_phrase('Visit our latest blogs')?></span></h1>
        <p class="text-center"><?php echo site_phrase('Visit our valuable articles to get more information.')?></p>

        <div class="courses-card">
            <div class="row">
                <?php foreach($latest_docs as $doc):
                    $author_id    = !empty($doc['author_id']) ? (int)$doc['author_id'] : 0;
                    $author_name  = !empty($doc['author_name']) ? $doc['author_name'] : site_phrase('admin');
                    $published_ts = !empty($doc['published_at']) ? strtotime($doc['published_at']) : time();

                    // Thumbnail
					$thumb = !empty($doc['og_image']) ? trim($doc['og_image']) : '';

					// If relative path, ensure it exists on disk
					if ($thumb && !preg_match('#^https?://#i', $thumb)) {
						$localPath = FCPATH . ltrim($thumb, '/');
						if (!is_file($localPath)) {
							$thumb = '';
						} else {
							$thumb = base_url($thumb);
						}
					}

					if (!$thumb) {
						$thumb = base_url('uploads/blog/thumbnail/placeholder.png');
					}


                    $label = !empty($doc['root_title']) ? $doc['root_title'] : strtoupper((string)$doc['root_key']);
                    $url   = site_url('blog/' . trim($doc['full_path'], '/'));
                ?>
                <div class="col-lg-4 col-md-6 mb-3">
                    <a href="<?php echo $url; ?>" class="courses-card-body">
                        <div class="courses-card-image">
                            <img loading="lazy" src="<?php echo $thumb; ?>" alt="<?php echo html_escape($doc['node_title']); ?>">
                            <div class="courses-card-image-text">
                                <h3><?php echo html_escape($label); ?></h3>
                            </div>
                        </div>

                        <div class="courses-text">
                            <h5><?php echo html_escape($doc['node_title']); ?></h5>
                            <p class="ellipsis-line-2"><?php echo html_escape($doc['excerpt']); ?></p>

                            <div class="courses-price-border">
                                <div class="courses-price">
                                    <div class="courses-price-left">
                                        <img loading="lazy" class="rounded-circle"
                                             src="<?php echo $author_id ? $this->user_model->get_user_image_url($author_id) : base_url('uploads/user_image/placeholder.png'); ?>">
                                        <h5><?php echo html_escape($author_name); ?></h5>
                                    </div>
                                    <div class="courses-price-right">
                                        <p><?php echo get_past_time($published_ts); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>
