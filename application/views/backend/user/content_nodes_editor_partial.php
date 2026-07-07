<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="card"><div class="card-body p-0">
  <div class="lv-editor-shell">
    <div class="lv-editor-header d-flex justify-content-between align-items-start">
      <div>
        <h4 class="header-title mb-1" id="selectedPageTitle">Content Editor</h4>
        <small class="text-muted" id="selectedPageLabel">Select a page or article to write content.</small>
      </div>
      <div class="text-right">
        <span id="selectedPageStatus"><?php echo lv_cp_status_badge('draft'); ?></span><br>
        <span class="autosave-dot" id="saveDot"></span> <small id="saveText" class="text-muted">Not saved</small>
      </div>
    </div>
    <form id="pageForm" method="post" action="<?php echo site_url('user/content_page_save'); ?>" enctype="multipart/form-data" style="display:none;">
      <input type="hidden" name="node_id" id="content_node_id">
      <div class="p-3">
        <textarea name="html" id="editor" class="form-control" rows="12"></textarea>
        <div class="mt-3 d-flex flex-wrap" style="gap:8px;">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="insertTemplate">Insert Template</button>
          <label class="btn btn-outline-secondary btn-sm mb-0">Import DOCX <input type="file" id="docxFile" accept=".docx" style="display:none;"></label>
        </div>
        <div class="publish-panel mt-3">
          <a data-toggle="collapse" href="#seoPanel" class="d-block"><b>Publishing Details</b> - discovery, SEO and social sharing</a>
          <div class="collapse show mt-3" id="seoPanel">
            <div class="row">
              <div class="col-md-6"><label>Category</label><input type="text" name="content_category" id="content_category" class="form-control" maxlength="120" placeholder="Example: Career Guidance"></div>
              <div class="col-md-6"><label>Tags</label><input type="text" name="content_tags" id="content_tags" class="form-control" maxlength="500" placeholder="Example: careers, interview, technology"></div>
              <div class="col-md-6"><label>SEO Title</label><input type="text" name="meta_title" id="content_meta_title" class="form-control"></div>
              <div class="col-md-6"><label>Canonical URL</label><input type="url" name="canonical_url" id="content_canonical_url" class="form-control"></div>
              <div class="col-md-12 mt-2"><label>Meta Description</label><textarea name="meta_description" id="content_meta_description" class="form-control" rows="2"></textarea></div>
              <div class="col-md-8 mt-2"><label>Keywords</label><input type="text" name="meta_keywords" id="content_meta_keywords" class="form-control"></div>
              <div class="col-md-4 mt-2"><label>Featured Image</label><input type="file" name="og_image" class="form-control" accept=".jpg,.jpeg,.png,.webp"><small class="text-muted">JPG, PNG or WebP, maximum 2 MB.</small></div>
            </div>
          </div>
        </div>
      </div>
      <div class="lv-sticky-save d-flex justify-content-between align-items-center flex-wrap">
        <small class="text-muted">Save keeps content as Draft. Use Book/Article Publish to send for admin review.</small>
        <div class="btn-group mt-2 mt-md-0">
          <button type="button" class="btn btn-outline-secondary" id="saveDraftBtn">Save Draft</button>
          <button type="submit" class="btn btn-success" id="saveBtn">Save</button>
          <button type="button" class="btn btn-outline-info" id="previewBtn">Preview</button>
        </div>
      </div>
    </form>
    <div id="emptyEditor" class="text-muted p-5 text-center">Open a page or article to write content. Autosave will start after editing.</div>
  </div>
</div></div>
