/* Section-wise Live Preview
 * -------------------------
 * Auto-binds to any admin editor form tagged with
 *   data-section-preview='{"content_type":"home_hero_video","entity_id":12}'
 *
 * Features:
 *   - Right-side collapsible preview panel showing ONLY the edited section
 *   - Debounced auto-save of draft on every input/change (no page reload)
 *   - Instant file uploads (images/videos) via dedicated endpoint
 *   - Preview iframe refreshes instantly after each draft save
 *   - Save Draft / Publish / Discard action buttons
 *   - Draft status badge (Published / Draft / Saving / Error)
 *   - TinyMCE support (listens to editor input/change)
 *   - Visible error toast for AJAX failures (never fails silently)
 *   - "Preview updated" pulse animation on successful save
 *
 * No backend tables are touched on save_draft / discard. Only Publish writes
 * to the real table (via admin/api/section-preview.php).
 */
(function () {
  'use strict';

  var DEBOUNCE_MS = 450;
  var IFRAME_BASE = 'section-preview-iframe.php';
  var API_URL = 'api/section-preview.php';
  var UPLOAD_URL = 'api/section-upload.php';

  // Registry: maps content_type -> upload subdirectory for file fields.
  // Each entry: { subdir: 'home-hero', fileFields: { 'poster_image': 'image', 'desktop_video_file': 'video', ... } }
  var uploadRegistry = {
    home_hero_video: {
      subdir: 'home-hero',
      fileFields: {
        'poster_image': 'image',
        'desktop_video_file': 'video',
        'desktop_light_video_file': 'video',
        'mobile_video_file': 'video'
      }
    },
    home_slide: {
      subdir: 'home-slides',
      fileFields: { 'image': 'image' }
    },
    home_testimonial: {
      subdir: 'testimonials',
      fileFields: { 'image': 'image' }
    },
    home_office: {
      subdir: 'offices',
      fileFields: { 'image': 'image' }
    },
    home_instagram_reel: {
      subdir: 'instagram-reels',
      fileFields: { 'video': 'video' }
    },
    home_working_process: {
      subdir: 'home/working-process',
      fileFields: { 'image_path': 'image' }
    },
    home_getting_started: {
      subdir: 'home/getting-started',
      fileFields: { 'back_image_path': 'image' }
    },
    home_partner_logo: {
      subdir: 'home/partner-logos',
      fileFields: { 'logo_path': 'image' }
    },
    home_certification_logo: {
      subdir: 'home/certification-logos',
      fileFields: { 'logo_path': 'image' }
    },
    home_milestones: {
      subdir: 'home/milestones',
      fileFields: { 'image_path': 'image' }
    },
    home_cta_card: {
      subdir: 'home-cta',
      fileFields: { 'image': 'image' }
    },
    blog: {
      subdir: 'blog',
      fileFields: { 'featured_image': 'image' }
    },
    product: {
      subdir: 'products',
      fileFields: { 'featured_image': 'image' }
    },
    category: {
      subdir: 'categories',
      fileFields: { 'image_path': 'image', 'page_image_path': 'image', 'page_image': 'image' }
    },
    certificate: {
      subdir: 'certificates',
      fileFields: { 'image_path': 'image', 'file_path': 'file' }
    },
    how_it_works_section: {
      subdir: 'how-it-works/sections',
      fileFields: { 'image_path': 'image' }
    }
  };

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  function parseConfig(form) {
    var raw = form.getAttribute('data-section-preview') || '';
    if (!raw) return null;
    try {
      var cfg = JSON.parse(raw);
      if (!cfg || !cfg.content_type) return null;
      cfg.entity_id = parseInt(cfg.entity_id || 0, 10) || 0;
      return cfg;
    } catch (e) {
      return null;
    }
  }

  function getCsrfToken() {
    var input = document.querySelector('input[name="csrf_token"]');
    if (input && input.value) return input.value;
    if (window.mybrandpleaseCsrfToken) return window.mybrandpleaseCsrfToken;
    return '';
  }

  function serializeForm(form) {
    var data = {};
    var elements = form.querySelectorAll('input, textarea, select');
    elements.forEach(function (el) {
      if (!el.name) return;
      if (el.name === 'csrf_token' || el.name === 'action' || el.name === 'draft_action') return;

      var type = (el.type || '').toLowerCase();
      if (type === 'file') return; // files handled via upload endpoint
      if (type === 'checkbox') {
        data[el.name] = el.checked ? '1' : '0';
        return;
      }
      if (type === 'radio') {
        if (el.checked) data[el.name] = el.value;
        return;
      }
      data[el.name] = el.value;
    });

    // TinyMCE: pull content from any editor bound to a textarea in this form
    if (window.tinymce) {
      form.querySelectorAll('textarea.js-editor, textarea[data-editor]').forEach(function (ta) {
        if (!ta.name) return;
        var editor = window.tinymce.get(ta.id);
        if (editor) {
          data[ta.name] = editor.getContent();
        }
      });
    }

    return data;
  }

  function apiCall(payload) {
    return fetch(API_URL, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': getCsrfToken()
      },
      body: JSON.stringify(Object.assign({ csrf_token: getCsrfToken() }, payload))
    }).then(function (r) {
      return r.json().then(function (body) {
        return { ok: r.ok, status: r.status, body: body };
      });
    });
  }

  function uploadFile(file, fieldType, subdir, fileFieldName) {
    var formData = new FormData();
    formData.append('csrf_token', getCsrfToken());
    formData.append('field_type', fieldType);
    formData.append('subdir', subdir);
    formData.append('file_field', fileFieldName);
    // Append the file using the actual input name so PHP $_FILES matches
    formData.append(fileFieldName, file);

    return fetch(UPLOAD_URL, {
      method: 'POST',
      credentials: 'same-origin',
      // Do NOT set Content-Type — the browser must set multipart/form-data with boundary
      headers: { 'X-CSRF-Token': getCsrfToken() },
      body: formData
    }).then(function (r) {
      return r.json().then(function (body) {
        return { ok: r.ok, status: r.status, body: body };
      });
    });
  }

  // Global error toast (never fail silently)
  function showErrorToast(message) {
    var existing = document.getElementById('sectionPreviewToast');
    if (existing) existing.remove();

    var toast = document.createElement('div');
    toast.id = 'sectionPreviewToast';
    toast.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);z-index:10600;background:#dc2626;color:#fff;padding:12px 24px;border-radius:8px;font-size:13px;font-weight:600;font-family:Arial,sans-serif;box-shadow:0 8px 24px rgba(220,38,38,0.4);max-width:90vw;text-align:center;';
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 5000);
  }

  function SectionPreviewController(form, config) {
    this.form = form;
    this.config = config;
    this.panel = null;
    this.iframe = null;
    this.loadingEl = null;
    this.emptyEl = null;
    this.statusBadge = null;
    this.toggleDot = null;
    this.saveBtn = null;
    this.publishBtn = null;
    this.discardBtn = null;
    this.refreshBtn = null;
    this.hasDraft = false;
    this.saveTimer = null;
    this.lastSavedData = null;
    this.isOpen = false;
    this.uploadConfig = uploadRegistry[config.content_type] || null;
  }

  SectionPreviewController.prototype.init = function () {
    this.buildPanel();
    this.buildToggle();
    this.bindFormEvents();
    this.bindFileInputs();
    this.bindActionButtons();
    this.loadStatus();
    // Auto-open on desktop if there's an entity id (editing existing record)
    if (this.config.entity_id > 0 && window.innerWidth >= 992) {
      this.openPanel();
    } else if (this.config.entity_id <= 0) {
      this.showNewRecordNotice();
    }
  };

  SectionPreviewController.prototype.buildPanel = function () {
    var self = this;
    var html =
      '<div class="section-preview-panel" id="sectionPreviewPanel" aria-hidden="true">' +
      '  <div class="section-preview-panel__header">' +
      '    <span class="section-preview-panel__title"><i class="bi bi-eye-fill"></i> Section Live Preview</span>' +
      '    <button type="button" class="section-preview-panel__close" id="sectionPreviewClose" aria-label="Close preview">&times;</button>' +
      '  </div>' +
      '  <div class="section-preview-panel__status">' +
      '    <span class="section-preview-panel__status-badge section-preview-panel__status-badge--published" id="sectionPreviewStatus">Published</span>' +
      '    <span id="sectionPreviewMessage" style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Live site is unchanged until you Publish.</span>' +
      '  </div>' +
      '  <div class="section-preview-panel__actions">' +
      '    <button type="button" class="btn btn-sm btn-warning" id="sectionPreviewSaveBtn" disabled><i class="bi bi-pencil-square"></i> Save Draft</button>' +
      '    <button type="button" class="btn btn-sm btn-success" id="sectionPreviewPublishBtn" disabled><i class="bi bi-rocket-takeoff"></i> Publish</button>' +
      '    <button type="button" class="btn btn-sm btn-outline-danger" id="sectionPreviewDiscardBtn" disabled><i class="bi bi-arrow-counterclockwise"></i> Discard</button>' +
      '    <button type="button" class="btn btn-sm btn-outline-secondary" id="sectionPreviewRefreshBtn" title="Refresh preview"><i class="bi bi-arrow-clockwise"></i></button>' +
      '  </div>' +
      '  <div class="section-preview-panel__viewport">' +
      '    <div class="section-preview-panel__empty" id="sectionPreviewEmpty"><i class="bi bi-eye-slash"></i><span>Open the panel to preview this section.</span></div>' +
      '    <div class="section-preview-panel__loading" id="sectionPreviewLoading"><div class="spinner-border text-primary"></div><span>Saving draft…</span></div>' +
      '    <iframe class="section-preview-panel__iframe" id="sectionPreviewFrame" title="Section preview" loading="eager"></iframe>' +
      '  </div>' +
      '</div>';

    var wrapper = document.createElement('div');
    wrapper.innerHTML = html;
    document.body.appendChild(wrapper.firstElementChild);

    this.panel = document.getElementById('sectionPreviewPanel');
    this.iframe = document.getElementById('sectionPreviewFrame');
    this.loadingEl = document.getElementById('sectionPreviewLoading');
    this.emptyEl = document.getElementById('sectionPreviewEmpty');
    this.statusBadge = document.getElementById('sectionPreviewStatus');
    this.statusMessage = document.getElementById('sectionPreviewMessage');
    this.saveBtn = document.getElementById('sectionPreviewSaveBtn');
    this.publishBtn = document.getElementById('sectionPreviewPublishBtn');
    this.discardBtn = document.getElementById('sectionPreviewDiscardBtn');
    this.refreshBtn = document.getElementById('sectionPreviewRefreshBtn');

    document.getElementById('sectionPreviewClose').addEventListener('click', function () {
      self.closePanel();
    });
  };

  SectionPreviewController.prototype.buildToggle = function () {
    var self = this;
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'section-preview-toggle';
    btn.id = 'sectionPreviewToggle';
    btn.innerHTML =
      '<i class="bi bi-window-sidebar"></i>' +
      '<span>Section Preview</span>' +
      '<span class="section-preview-toggle__dot" id="sectionPreviewToggleDot"></span>';
    document.body.appendChild(btn);
    btn.addEventListener('click', function () {
      self.openPanel();
    });
    this.toggleDot = document.getElementById('sectionPreviewToggleDot');
  };

  SectionPreviewController.prototype.bindFormEvents = function () {
    var self = this;
    var handler = function () {
      self.scheduleDraftSave();
    };

    this.form.addEventListener('input', handler);
    this.form.addEventListener('change', handler);

    // TinyMCE integration
    function bindTinymce() {
      if (!window.tinymce) return;
      self.form.querySelectorAll('textarea.js-editor, textarea[data-editor]').forEach(function (ta) {
        if (!ta.id) return;
        var editor = window.tinymce.get(ta.id);
        if (editor && !editor.__sectionPreviewBound) {
          editor.on('input change undo redo', function () {
            editor.save();
            self.scheduleDraftSave();
          });
          editor.__sectionPreviewBound = true;
        }
      });
    }
    bindTinymce();
    setTimeout(bindTinymce, 1200);
    setTimeout(bindTinymce, 3000);
  };

  // NEW: Bind file input change events for instant upload
  SectionPreviewController.prototype.bindFileInputs = function () {
    var self = this;
    if (!this.uploadConfig) return;

    var fileInputs = this.form.querySelectorAll('input[type="file"]');
    fileInputs.forEach(function (input) {
      if (!input.name) return;
      var fieldType = self.uploadConfig.fileFields[input.name];
      if (!fieldType) return; // not a recognized file field for this section

      input.addEventListener('change', function () {
        if (!input.files || !input.files[0]) return;
        self.handleFileUpload(input, input.files[0], fieldType, input.name);
      });
    });
  };

  // NEW: Handle a file upload via the upload endpoint
  // File uploads work even for new records (entity_id=0) — the file is stored
  // and the hidden field + inline preview are updated. The section preview
  // iframe + draft save only activate once the record is saved (entity_id > 0).
  SectionPreviewController.prototype.handleFileUpload = function (input, file, fieldType, fileFieldName) {
    var self = this;

    this.setStatus('saving', 'Uploading ' + (fieldType === 'image' ? 'image' : 'video') + '…');
    this.showLoading(true);

    uploadFile(file, fieldType, this.uploadConfig.subdir, fileFieldName).then(function (res) {
      self.showLoading(false);
      if (res.ok && res.body && res.body.success) {
        var publicPath = res.body.public_path;

        // Update the hidden existing_* field so the draft save picks it up.
        var hiddenFieldNames = [
          'existing_' + fileFieldName,
          'existing_' + fileFieldName + '_path'
        ];

        var updated = false;
        hiddenFieldNames.forEach(function (hiddenName) {
          var hidden = self.form.querySelector('input[name="' + hiddenName + '"]');
          if (hidden) {
            hidden.value = publicPath;
            updated = true;
          }
        });

        // Also update any inline preview (img/video) next to the file input
        if (fieldType === 'image') {
          var previewImg = input.parentElement.querySelector('img');
          if (previewImg && res.body.file_url) {
            previewImg.src = res.body.file_url;
            previewImg.style.display = '';
          }
        } else if (fieldType === 'video') {
          var previewVideo = input.parentElement.querySelector('video');
          if (previewVideo && res.body.file_url) {
            previewVideo.src = res.body.file_url;
            previewVideo.load();
            previewVideo.style.display = '';
          }
        }

        if (self.config.entity_id > 0) {
          // Existing record: trigger draft save + preview refresh
          if (updated) {
            self.setStatus('saving', 'File uploaded. Saving draft…');
            self.lastSavedData = null;
            self.saveDraft(false);
          } else {
            console.warn('[SectionPreview] No hidden field found for uploaded file:', hiddenFieldNames);
            self.setStatus('error', 'File uploaded but no hidden field found to store the path.');
          }
        } else {
          // New record: file is uploaded and hidden field is updated.
          // The section preview will activate once the record is saved.
          self.setStatus('published', 'File uploaded. Save the record (click Add/Update) to enable section preview.');
          if (updated) {
            // Trigger change so any other JS listeners pick up the new path
            hiddenFieldNames.forEach(function (hiddenName) {
              var hidden = self.form.querySelector('input[name="' + hiddenName + '"]');
              if (hidden) hidden.dispatchEvent(new Event('change', { bubbles: true }));
            });
          }
        }
      } else {
        var msg = (res.body && res.body.message) || 'File upload failed.';
        self.setStatus('error', msg);
        showErrorToast(msg);
        console.error('[SectionPreview] Upload failed:', res);
      }
    }).catch(function (err) {
      self.showLoading(false);
      self.setStatus('error', 'Network error during file upload.');
      showErrorToast('Network error during file upload. Check console for details.');
      console.error('[SectionPreview] Upload network error:', err);
    });
  };

  SectionPreviewController.prototype.bindActionButtons = function () {
    var self = this;
    this.saveBtn.addEventListener('click', function () {
      self.saveDraft(true);
    });
    this.publishBtn.addEventListener('click', function () {
      self.publishDraft();
    });
    this.discardBtn.addEventListener('click', function () {
      self.discardDraft();
    });
    this.refreshBtn.addEventListener('click', function () {
      self.refreshIframe();
    });
  };

  SectionPreviewController.prototype.openPanel = function () {
    this.panel.classList.add('is-open');
    this.panel.setAttribute('aria-hidden', 'false');
    this.isOpen = true;
    if (this.emptyEl) this.emptyEl.style.display = 'none';
    this.refreshIframe();
  };

  SectionPreviewController.prototype.closePanel = function () {
    this.panel.classList.remove('is-open');
    this.panel.setAttribute('aria-hidden', 'true');
    this.isOpen = false;
  };

  // NEW: Show a clear notice for new (unsaved) records
  SectionPreviewController.prototype.showNewRecordNotice = function () {
    if (this.emptyEl) {
      this.emptyEl.innerHTML =
        '<i class="bi bi-info-circle"></i>' +
        '<span><strong>Live preview is disabled for new records.</strong><br>' +
        'Click "Add" or "Update" to save this record first, then return to edit it — the section preview will activate automatically.</span>';
      this.emptyEl.style.gap = '12px';
    }
    this.setStatus('published', 'Save this record first to enable live preview.');
    this.saveBtn.disabled = true;
    this.publishBtn.disabled = true;
    this.discardBtn.disabled = true;
  };

  SectionPreviewController.prototype.setStatus = function (state, message) {
    var states = {
      published: ['section-preview-panel__status-badge--published', 'Published'],
      draft: ['section-preview-panel__status-badge--draft', 'Draft'],
      saving: ['section-preview-panel__status-badge--saving', 'Saving…'],
      error: ['section-preview-panel__status-badge--error', 'Error']
    };
    var cls = states[state] || states.published;
    this.statusBadge.className = 'section-preview-panel__status-badge ' + cls[0];
    this.statusBadge.textContent = cls[1];
    if (message) this.statusMessage.textContent = message;
    this.hasDraft = (state === 'draft');
    if (this.toggleDot) this.toggleDot.classList.toggle('is-visible', this.hasDraft);
    // Enable/disable action buttons
    this.publishBtn.disabled = !this.hasDraft;
    this.discardBtn.disabled = !this.hasDraft;
    this.saveBtn.disabled = (this.config.entity_id <= 0);

    // Pulse animation on successful save
    if (state === 'draft' && this.statusBadge) {
      this.statusBadge.classList.remove('is-pulsing');
      void this.statusBadge.offsetWidth; // force reflow
      this.statusBadge.classList.add('is-pulsing');
    }
  };

  SectionPreviewController.prototype.showLoading = function (visible) {
    if (this.loadingEl) this.loadingEl.classList.toggle('is-visible', visible);
  };

  SectionPreviewController.prototype.scheduleDraftSave = function () {
    var self = this;
    if (this.saveTimer) clearTimeout(this.saveTimer);
    this.saveTimer = setTimeout(function () {
      self.saveDraft(false);
    }, DEBOUNCE_MS);
  };

  SectionPreviewController.prototype.saveDraft = function (showStatus) {
    var self = this;
    if (this.config.entity_id <= 0) {
      if (showStatus) {
        this.setStatus('error', 'Save the record first, then preview drafts.');
        showErrorToast('Save this record first (click Update/Add), then edit it to enable live preview.');
      }
      return;
    }
    var data = serializeForm(this.form);
    var serialized = JSON.stringify(data);
    if (this.lastSavedData === serialized) {
      if (showStatus) this.setStatus(this.hasDraft ? 'draft' : 'published', 'No changes since last save.');
      return;
    }
    this.lastSavedData = serialized;

    this.setStatus('saving', 'Saving draft…');
    this.showLoading(true);

    apiCall({
      action: 'save_draft',
      content_type: this.config.content_type,
      entity_id: this.config.entity_id,
      data: data
    }).then(function (res) {
      self.showLoading(false);
      if (res.ok && res.body && res.body.success) {
        self.setStatus('draft', res.body.message || 'Draft saved. Live site unchanged until Publish.');
        self.refreshIframe();
      } else {
        var msg = (res.body && res.body.message) || 'Failed to save draft.';
        self.setStatus('error', msg);
        if (showStatus) showErrorToast(msg);
        console.error('[SectionPreview] Draft save failed:', res);
      }
    }).catch(function (err) {
      self.showLoading(false);
      self.setStatus('error', 'Network error while saving draft.');
      showErrorToast('Network error while saving draft. Check console for details.');
      console.error('[SectionPreview] Draft save network error:', err);
    });
  };

  SectionPreviewController.prototype.publishDraft = function () {
    var self = this;
    if (this.config.entity_id <= 0) {
      this.setStatus('error', 'Save the record first, then publish.');
      showErrorToast('Save this record first, then publish.');
      return;
    }
    if (!confirm('Publish this draft to the live site? Visitors will see the changes immediately.')) return;

    this.setStatus('saving', 'Publishing…');
    this.showLoading(true);

    apiCall({
      action: 'publish',
      content_type: this.config.content_type,
      entity_id: this.config.entity_id
    }).then(function (res) {
      self.showLoading(false);
      if (res.ok && res.body && res.body.success) {
        self.setStatus('published', res.body.message || 'Draft published to the live site.');
        self.lastSavedData = null;
        self.refreshIframe();
        window.dispatchEvent(new CustomEvent('mybrandplease:preview-reload'));
      } else {
        var msg = (res.body && res.body.message) || 'Publish failed.';
        self.setStatus('error', msg);
        showErrorToast(msg);
        console.error('[SectionPreview] Publish failed:', res);
      }
    }).catch(function (err) {
      self.showLoading(false);
      self.setStatus('error', 'Network error while publishing.');
      showErrorToast('Network error while publishing. Check console for details.');
      console.error('[SectionPreview] Publish network error:', err);
    });
  };

  SectionPreviewController.prototype.discardDraft = function () {
    var self = this;
    if (this.config.entity_id <= 0) return;
    if (!confirm('Discard this draft? The preview will revert to the published state.')) return;

    this.setStatus('saving', 'Discarding…');
    this.showLoading(true);

    apiCall({
      action: 'discard',
      content_type: this.config.content_type,
      entity_id: this.config.entity_id
    }).then(function (res) {
      self.showLoading(false);
      if (res.ok && res.body && res.body.success) {
        self.setStatus('published', res.body.message || 'Draft discarded. Preview reverted to published state.');
        self.lastSavedData = null;
        self.refreshIframe();
        window.dispatchEvent(new CustomEvent('mybrandplease:preview-reload'));
      } else {
        var msg = (res.body && res.body.message) || 'Discard failed.';
        self.setStatus('error', msg);
        showErrorToast(msg);
      }
    }).catch(function (err) {
      self.showLoading(false);
      self.setStatus('error', 'Network error while discarding.');
      showErrorToast('Network error while discarding. Check console for details.');
      console.error('[SectionPreview] Discard network error:', err);
    });
  };

  SectionPreviewController.prototype.refreshIframe = function () {
    if (!this.iframe || !this.isOpen) return;
    var src = IFRAME_BASE +
      '?content_type=' + encodeURIComponent(this.config.content_type) +
      '&entity_id=' + encodeURIComponent(this.config.entity_id) +
      '&_r=' + Date.now();
    this.iframe.src = src;
  };

  SectionPreviewController.prototype.loadStatus = function () {
    var self = this;
    if (this.config.entity_id <= 0) {
      this.showNewRecordNotice();
      return;
    }
    apiCall({
      action: 'status',
      content_type: this.config.content_type,
      entity_id: this.config.entity_id
    }).then(function (res) {
      if (res.ok && res.body && res.body.success) {
        if (res.body.has_draft) {
          self.setStatus('draft', 'A draft exists. Live site shows the published version.');
        } else {
          self.setStatus('published', 'No draft. Live site is up to date.');
        }
      } else {
        console.error('[SectionPreview] Status check failed:', res);
      }
    }).catch(function (err) {
      console.error('[SectionPreview] Status check network error:', err);
    });
  };

  function initFallbackModulePreview() {
    if (document.getElementById('sectionPreviewToggle')) return;

    var previewUrl = window.mybrandpleaseLivePreviewUrl || 'index.php';
    if (previewUrl.indexOf('preview=1') === -1) {
      previewUrl += (previewUrl.indexOf('?') === -1 ? '?' : '&') + 'preview=1';
    }

    var panelHtml =
      '<div class="section-preview-panel" id="sectionPreviewPanel" aria-hidden="true">' +
      '  <div class="section-preview-panel__header">' +
      '    <span class="section-preview-panel__title"><i class="bi bi-eye-fill"></i> Section Live Preview</span>' +
      '    <button type="button" class="section-preview-panel__close" id="sectionPreviewClose" aria-label="Close preview">&times;</button>' +
      '  </div>' +
      '  <div class="section-preview-panel__status">' +
      '    <span class="section-preview-panel__status-badge section-preview-panel__status-badge--published">Live Site</span>' +
      '    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">Showing live site preview.</span>' +
      '  </div>' +
      '  <div class="section-preview-panel__actions">' +
      '    <button type="button" class="btn btn-sm btn-outline-secondary" id="sectionPreviewRefreshBtn" title="Refresh preview"><i class="bi bi-arrow-clockwise"></i> Refresh</button>' +
      '  </div>' +
      '  <div class="section-preview-panel__viewport">' +
      '    <iframe class="section-preview-panel__iframe" id="sectionPreviewFrame" title="Section preview" loading="eager"></iframe>' +
      '  </div>' +
      '</div>';

    var wrapper = document.createElement('div');
    wrapper.innerHTML = panelHtml;
    document.body.appendChild(wrapper.firstElementChild);

    var panel = document.getElementById('sectionPreviewPanel');
    var iframe = document.getElementById('sectionPreviewFrame');
    var closeBtn = document.getElementById('sectionPreviewClose');
    var refreshBtn = document.getElementById('sectionPreviewRefreshBtn');

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'section-preview-toggle';
    btn.id = 'sectionPreviewToggle';
    btn.innerHTML =
      '<i class="bi bi-window-sidebar"></i>' +
      '<span>Section Preview</span>' +
      '<span class="section-preview-toggle__dot"></span>';
    document.body.appendChild(btn);

    function openPanel() {
      panel.classList.add('is-open');
      panel.setAttribute('aria-hidden', 'false');
      if (iframe && !iframe.getAttribute('src')) {
        iframe.src = previewUrl + (previewUrl.indexOf('?') === -1 ? '?_r=' : '&_r=') + Date.now();
      }
    }

    function closePanel() {
      panel.classList.remove('is-open');
      panel.setAttribute('aria-hidden', 'true');
    }

    btn.addEventListener('click', openPanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (refreshBtn) refreshBtn.addEventListener('click', function () {
      if (iframe) iframe.src = previewUrl + (previewUrl.indexOf('?') === -1 ? '?_r=' : '&_r=') + Date.now();
    });
  }

  ready(function () {
    var forms = document.querySelectorAll('form[data-section-preview]');
    var initialized = 0;
    forms.forEach(function (form) {
      var config = parseConfig(form);
      if (!config) return;
      var controller = new SectionPreviewController(form, config);
      controller.init();
      initialized++;
    });

    if (initialized === 0) {
      initFallbackModulePreview();
    }
  });
})();