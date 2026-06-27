<?php
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/content-loader.php';

function why_page_fetch_accordions(int $pageId): array
{
    $pdo = db();
    if (!$pdo || $pageId <= 0) {
        return [];
    }
    return db_fetch_all($pdo, 'SELECT title, body_html, is_open FROM why_page_accordions WHERE page_id = :pid AND is_active = 1 ORDER BY sort_order ASC, id ASC', [':pid' => $pageId]);
}

function render_why_choose_page(string $slug): void
{
    $page = get_page_by_slug($slug);
    if (!$page) {
        http_response_code(404);
        require __DIR__ . '/../404.php';
        return;
    }

    $accordions = why_page_fetch_accordions((int)$page['id']);

    $topContentRaw = (string) ($page['content'] ?? '');
    $topContentHtml = $topContentRaw !== '' && !str_starts_with(trim($topContentRaw), '{')
      ? sanitize_rich_html(html_entity_decode($topContentRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8'))
      : '';

    // Compatibility fallback for old JSON content until re-saved from admin.
    if (!$accordions && !empty($page['content']) && str_starts_with(trim((string)$page['content']), '{')) {
        $decoded = json_decode((string)$page['content'], true);
        if (is_array($decoded) && !empty($decoded['accordion']) && is_array($decoded['accordion'])) {
            foreach ($decoded['accordion'] as $acc) {
                if (!is_array($acc)) continue;
                $accordions[] = [
                    'title' => (string)($acc['title'] ?? ''),
                    'body_html' => (string)($acc['body_html'] ?? ''),
                    'is_open' => !empty($acc['open']) ? 1 : 0,
                ];
            }
        }
    }

    if (!empty($accordions)) {
        foreach ($accordions as $index => &$accordionItem) {
            $accordionItem['is_open'] = $index === 0 ? 1 : 0;
        }
        unset($accordionItem);
    }

    $meta = [
      'title' => (string) (($page['meta_title'] ?? '') !== '' ? $page['meta_title'] : ($page['title'] ?? 'Mybrandplease')),
      'description' => (string) ($page['meta_description'] ?? ''),
      'keywords' => (string) ($page['meta_keywords'] ?? ''),
      'canonical' => (string) (($page['canonical_url'] ?? '') !== '' ? $page['canonical_url'] : $slug),
    ];

    include __DIR__ . '/head.php';
    include __DIR__ . '/header.php';
    ?>
    <div class="private-label-page">
      <div class="breadcumb">
        <div class="container rr-container-1895">
          <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
            <h1 class="text-center"><?php echo esc_html((string)($page['title'] ?? 'Why Choose Us')); ?></h1>
            <ul class="breadcumb-wrapper__items">
              <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
              <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
              <li class="breadcumb-wrapper__items-list"><a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a></li>
              <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
              <li class="breadcumb-wrapper__items-list"><a href="<?php echo esc_html(why_page_url($slug)); ?>" class="breadcumb-wrapper__items-list-title2"><?php echo esc_html((string)($page['title'] ?? 'Why Choose Us')); ?></a></li>
            </ul>
          </div>
        </div>
      </div>

      <section class="private-label-content section-spacing-120">
        <div class="container container-1352">
          <div class="row g-4 g-xl-5 align-items-start">
            <div class="col-lg-8">
              <?php if ($topContentHtml !== ''): ?>
                <div class="mb-4 cms-richtext"><?php echo $topContentHtml; ?></div>
              <?php endif; ?>

              <div class="private-label-intro-accordion" id="privateLabelIntroAccordion">
                <?php foreach ($accordions as $item):
                  $title = (string)($item['title'] ?? '');
                  $bodyRaw = (string)($item['body_html'] ?? '');
                  $bodyHtml = $bodyRaw !== '' ? sanitize_rich_html(html_entity_decode($bodyRaw, ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
                  $isOpen = !empty($item['is_open']);
                  if ($title === '') continue;
                ?>
                <article class="private-label-intro-accordion__item<?php echo $isOpen ? ' is-open' : ''; ?>">
                  <button class="private-label-intro-accordion__btn" type="button">
                    <span class="private-label-intro-accordion__icon" aria-hidden="true"></span>
                    <span class="private-label-intro-accordion__title"><?php echo esc_html($title); ?></span>
                  </button>
                  <div class="private-label-intro-accordion__panel">
                    <div class="private-label-intro-accordion__body cms-richtext"><?php echo $bodyHtml; ?></div>
                  </div>
                </article>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="col-lg-4">
              <aside class="private-label-sidebar">
                <div class="private-label-sidebar__social">
                  <h3>~Follow Us On Social Network~</h3>
                  <ul class="private-label-sidebar__social-list">
                    <li><a href="https://www.facebook.com/mybrandpleaseindia/" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a></li>
                    <li><a href="https://www.instagram.com/mybrandpleaseindia/" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                    <li><a href="https://www.linkedin.com/company/mybrandplease/" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a></li>
                    <li><a href="https://in.pinterest.com/mybrandplease/" target="_blank" rel="noopener noreferrer" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a></li>
                    <li><a href="https://x.com/mybrandplease" target="_blank" rel="noopener noreferrer" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a></li>
                    <li><a href="https://www.youtube.com/@mybrandplease" target="_blank" rel="noopener noreferrer" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a></li>
                  </ul>
                </div>

                <div class="private-label-sidebar__links">
                  <h3>Quick Links</h3>
                  <ul>
                    <li><a href="how-it-works.php">How it works</a></li>
                    <li><a href="services.php">Additional services</a></li>
                    <li><a href="faq.php">Faqs</a></li>
                    <li><a href="blog.php">Blog</a></li>
                    <li><a href="about.php">About us</a></li>
                  </ul>
                </div>
              </aside>
            </div>
          </div>
        </div>
      </section>
    </div>

    <script>
      (function () {
        const root = document.getElementById('privateLabelIntroAccordion');
        if (!root) return;
        const items = Array.from(root.querySelectorAll('.private-label-intro-accordion__item'));
        function closeAll(exceptItem) {
          items.forEach((item) => { if (item !== exceptItem) item.classList.remove('is-open'); });
        }
        items.forEach((item) => {
          const button = item.querySelector('.private-label-intro-accordion__btn');
          if (!button) return;
          button.addEventListener('click', function () {
            const willOpen = !item.classList.contains('is-open');
            closeAll(item);
            item.classList.toggle('is-open', willOpen);
          });
        });
      })();
    </script>
    <?php
    include __DIR__ . '/footer.php';
}
