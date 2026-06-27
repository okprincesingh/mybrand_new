USE mybrandplease;

INSERT INTO pages (title, slug, content, status, page_group, template_key)
VALUES (
  'Private Label Skin Care Manufacturer',
  'private-label-skin-care-manufacturer',
  '{"breadcrumb":{"page_title":"Private Label Skin Care Manufacturer"},"accordion":[{"title":"Introduction","body_html":"<p>Mybrandplease supports brands with private label skin care solutions.</p>","open":true}],"sidebar":{"social_title":"Follow Us On Social Network","social_links":[{"icon":"fa-facebook-f","url":"contact.php","label":"Facebook"}],"quick_links_title":"Quick Links","quick_links":[{"label":"How it works","url":"how-it-works.php"}]}}',
  'published',
  'why_choose_us',
  'why_choose_us'
)
ON DUPLICATE KEY UPDATE
  title = VALUES(title),
  status = 'published',
  page_group = 'why_choose_us',
  template_key = 'why_choose_us';
