<?php
// Add these routes to application/config/routes.php if your server does not resolve manage section URLs automatically.
$route['tutor_batch/manage/(\d+)/(:any)'] = 'tutor_batch/manage/$1/$2';
$route['tutor_batch/manage/(\d+)'] = 'tutor_batch/manage/$1/overview';
