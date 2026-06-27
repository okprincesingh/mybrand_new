USE mybrand_cms;

ALTER TABLE pages
  ADD COLUMN IF NOT EXISTS page_group VARCHAR(100) NOT NULL DEFAULT 'general' AFTER status,
  ADD COLUMN IF NOT EXISTS template_key VARCHAR(100) NOT NULL DEFAULT 'default' AFTER page_group;

CREATE INDEX idx_pages_group_template ON pages (page_group, template_key, status, updated_at, id);
