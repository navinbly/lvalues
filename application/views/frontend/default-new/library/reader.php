<?php defined('BASEPATH') OR exit('No direct script access allowed');
$chapters=[]; foreach($tree as $node){ if(($node['content_type']??'')==='chapter')$chapters[(int)$node['node_id']]=['node'=>$node,'pages'=>[]]; }
foreach($tree as $node){ if(($node['content_type']??'')==='page' && isset($chapters[(int)$node['parent_id']]))$chapters[(int)$node['parent_id']]['pages'][]=$node; }
$pageCount=count($pages); $progress=$pageCount ? round((($selected_index+1)/$pageCount)*100) : 0;
?>
<style>
.lv-reader{display:grid;grid-template-columns:310px minmax(0,1fr);min-height:75vh;background:#f8fafc}.lv-reader-nav{background:#fff;border-right:1px solid #e2e8f0;padding:22px;position:sticky;top:0;height:100vh;overflow:auto}.lv-reader-main{padding:30px}.lv-reader-paper{max-width:900px;margin:auto;background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:42px;box-shadow:0 18px 50px rgba(15,23,42,.06)}.lv-reader-paper img{max-width:100%;height:auto}.lv-chapter button{width:100%;text-align:left;border:0;background:#f1f5f9;padding:10px;border-radius:9px;font-weight:700}.lv-pages a{display:block;padding:8px 10px;color:#475569}.lv-pages a.active{background:#eef2ff;color:#4338ca;border-radius:8px}.lv-reader.fullscreen{position:fixed;inset:0;z-index:9999;overflow:auto}.lv-reader-progress{height:5px;background:#e2e8f0}.lv-reader-progress span{display:block;height:100%;background:#4f46e5}@media(max-width:900px){.lv-reader{display:block}.lv-reader-nav{position:relative;height:auto;border-right:0}.lv-reader-main{padding:14px}.lv-reader-paper{padding:22px}}
</style>
<div class="lv-reader-progress"><span style="width:<?php echo $progress; ?>%"></span></div>
<section class="lv-reader" id="bookReader">
 <aside class="lv-reader-nav"><a href="<?php echo site_url('books'); ?>">← Library</a><h3 class="mt-3"><?php echo html_escape($book['title']); ?></h3>
  <form method="get" class="mb-3"><input class="form-control" name="q" value="<?php echo html_escape($query); ?>" placeholder="Search this book"></form>
  <?php foreach($chapters as $chapter): ?><div class="lv-chapter mb-2"><button type="button"><?php echo html_escape($chapter['node']['title']); ?></button><div class="lv-pages">
   <?php foreach($chapter['pages'] as $page): ?><a class="<?php echo (int)($selected_page['node_id']??0)===(int)$page['node_id']?'active':''; ?>" href="<?php echo site_url('books/'.$book['slug'].'?page='.(int)$page['node_id']); ?>"><?php echo html_escape($page['title']); ?></a><?php endforeach; ?>
  </div></div><?php endforeach; ?>
 </aside>
 <main class="lv-reader-main"><?php if(!empty($is_preview)): ?><div class="alert alert-warning">Admin preview: this book is not necessarily published.</div><?php endif; ?><div class="d-flex justify-content-between align-items-center mb-3"><small><?php echo $progress; ?>% complete</small><div><button class="btn btn-sm btn-light" id="bookmarkPage">Bookmark</button> <button class="btn btn-sm btn-light" id="fullReader">Full screen</button></div></div>
  <article class="lv-reader-paper">
   <?php if(($book['reader_mode']??'interactive')==='pdf' && !empty($book['source_file'])): ?><iframe title="<?php echo html_escape($book['title']); ?>" src="<?php echo base_url($book['source_file']); ?>" style="width:100%;height:75vh;border:0"></iframe>
   <?php elseif($selected_page): ?><h1><?php echo html_escape($selected_page['title']); ?></h1><?php echo $selected_page['html']; ?>
   <?php else: ?><div class="alert alert-light">No published pages are available.</div><?php endif; ?>
   <hr><div class="d-flex justify-content-between"><?php if($selected_index>0): ?><a class="btn btn-outline-primary" href="?page=<?php echo (int)$pages[$selected_index-1]['node_id']; ?>">Previous Page</a><?php else:?><span></span><?php endif; ?><?php if($selected_index<$pageCount-1): ?><a class="btn btn-primary" href="?page=<?php echo (int)$pages[$selected_index+1]['node_id']; ?>">Next Page</a><?php endif; ?></div>
  </article>
 </main>
</section>
<script>(function(){var reader=document.getElementById('bookReader'),key='lv_book_<?php echo (int)$book['node_id']; ?>',page=<?php echo (int)($selected_page['node_id']??0); ?>,progress=<?php echo (float)$progress; ?>;try{localStorage.setItem(key,JSON.stringify({page:page,progress:progress,at:new Date().toISOString()}));}catch(e){}document.querySelectorAll('.lv-chapter button').forEach(function(b){b.addEventListener('click',function(){var p=b.nextElementSibling;p.style.display=p.style.display==='none'?'block':'none';});});document.getElementById('fullReader').addEventListener('click',function(){reader.classList.toggle('fullscreen');});document.getElementById('bookmarkPage').addEventListener('click',function(){try{localStorage.setItem(key+'_bookmark',String(page));this.textContent='Bookmarked';}catch(e){};var f=new FormData();f.append('book_id','<?php echo (int)$book['node_id']; ?>');f.append('page_id',String(page));f.append('progress',String(progress));f.append('bookmark','1');fetch('<?php echo site_url('books/state'); ?>',{method:'POST',body:f,credentials:'same-origin'});});})();</script>
