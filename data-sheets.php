<?php
require_once __DIR__ . '/includes/resource-library.php';

$meta = [
    'title' => 'Data Sheets | Mybrandplease',
    'description' => 'Download the Material Safety Data Sheet by clicking on the product of your choice.',
    'canonical' => 'data-sheets.php',
];

function ds_display_title(string $name): string
{
    $title = pathinfo($name, PATHINFO_FILENAME);
    $title = preg_replace('/^\d+[-_]?/', '', $title);
    $title = str_replace(['_', '-'], ' ', (string) $title);
    $title = preg_replace('/\s+/', ' ', (string) $title);
    return trim((string) $title);
}

function ds_push(array &$groups, string $group, string $title, string $url): void
{
    if (!isset($groups[$group])) {
        $groups[$group] = [];
    }
    $groups[$group][] = ['title' => $title, 'url' => $url];
}

function ds_push_sub(array &$groups, string $group, string $sub, string $title, string $url): void
{
    if (!isset($groups[$group])) {
        $groups[$group] = [];
    }
    if (!isset($groups[$group][$sub])) {
        $groups[$group][$sub] = [];
    }
    $groups[$group][$sub][] = ['title' => $title, 'url' => $url];
}

$files = resource_library_scan_folder('uploads/resources/form-center', static fn(array $item): bool => true, ['pdf', 'doc', 'docx']);

$mainGroups = [
    'Cleansers & Facial Exfoliants' => [],
    'Toners & Mists' => [],
    'Serums & Facial Oils' => [],
    'Moisturizers' => [],
    'Eye Products' => [],
    'Masques' => [],
    'Bath & Body' => [],
    'Hair Care' => [],
    'For Men' => [],
    'Beauty Bathing Soaps' => [],
];

foreach ($files as $file) {
    $title = ds_display_title((string) $file['name']);
    $lower = strtolower($title);

    if (str_contains($lower, 'catalogue') || str_contains($lower, 'catalouge') || str_contains($lower, 'export media')) {
        continue;
    }

    if (str_contains($lower, 'soap')) {
        ds_push($mainGroups, 'Beauty Bathing Soaps', $title, (string) $file['url']);
        continue;
    }

    if (
        str_contains($lower, 'beard') ||
        str_contains($lower, 'aftershave') ||
        str_contains($lower, 'all in one wash') ||
        str_contains($lower, 'shaving')
    ) {
        ds_push($mainGroups, 'For Men', $title, (string) $file['url']);
    }

    if (str_contains($lower, 'toner') || str_contains($lower, 'mist') || str_contains($lower, 'essence')) {
        ds_push($mainGroups, 'Toners & Mists', $title, (string) $file['url']);
        continue;
    }
    if (str_contains($lower, 'serum') || str_contains($lower, 'oil')) {
        ds_push($mainGroups, 'Serums & Facial Oils', $title, (string) $file['url']);
        continue;
    }
    if (str_contains($lower, 'eye')) {
        ds_push($mainGroups, 'Eye Products', $title, (string) $file['url']);
        continue;
    }
    if (str_contains($lower, 'masque') || str_contains($lower, 'peel') || str_contains($lower, 'gommage')) {
        ds_push($mainGroups, 'Masques', $title, (string) $file['url']);
        continue;
    }
    if (
        str_contains($lower, 'cleanser') ||
        str_contains($lower, 'scrub') ||
        str_contains($lower, 'wash') ||
        str_contains($lower, 'foam')
    ) {
        ds_push($mainGroups, 'Cleansers & Facial Exfoliants', $title, (string) $file['url']);
        continue;
    }
    if (str_contains($lower, 'shampoo') || str_contains($lower, 'conditioner') || str_contains($lower, 'styling') || str_contains($lower, 'spray') || str_contains($lower, 'hair') || str_contains($lower, 'bar')) {
        if (str_contains($lower, 'shampoo')) {
            ds_push_sub($mainGroups, 'Hair Care', 'Shampoos', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'conditioner')) {
            ds_push_sub($mainGroups, 'Hair Care', 'Conditioners', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'styling') || str_contains($lower, 'spray') || str_contains($lower, 'hold')) {
            ds_push_sub($mainGroups, 'Hair Care', 'Styling Products', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'bar')) {
            ds_push_sub($mainGroups, 'Hair Care', 'Bars', $title, (string) $file['url']);
        } else {
            ds_push_sub($mainGroups, 'Hair Care', 'Treatment Products', $title, (string) $file['url']);
        }
        continue;
    }
    if (
        str_contains($lower, 'body') ||
        str_contains($lower, 'bath') ||
        str_contains($lower, 'salt') ||
        str_contains($lower, 'soak') ||
        str_contains($lower, 'lotion') ||
        str_contains($lower, 'butter') ||
        str_contains($lower, 'pedi') ||
        str_contains($lower, 'mani') ||
        str_contains($lower, 'foot') ||
        str_contains($lower, 'hand') ||
        str_contains($lower, 'balm')
    ) {
        if (str_contains($lower, 'lotion')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Lotions', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'wash') || str_contains($lower, 'shower') || str_contains($lower, 'gel')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Shower & Bath Gels', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'salt') || str_contains($lower, 'soak')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Salts & Soaks', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'scrub')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Body Scrubs', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'lip')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Lip Balms & Scrubs', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'foot') || str_contains($lower, 'hand') || str_contains($lower, 'cuticle')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Mani/Pedi', $title, (string) $file['url']);
        } elseif (str_contains($lower, 'creme') || str_contains($lower, 'butter') || str_contains($lower, 'balm')) {
            ds_push_sub($mainGroups, 'Bath & Body', 'Body Butters & Crèmes', $title, (string) $file['url']);
        } else {
            ds_push_sub($mainGroups, 'Bath & Body', 'Specialty Products', $title, (string) $file['url']);
        }
        continue;
    }

    ds_push($mainGroups, 'Moisturizers', $title, (string) $file['url']);
}

foreach ($mainGroups as &$group) {
    if (array_values($group) === $group) {
        usort($group, static fn($a, $b) => strcasecmp($a['title'], $b['title']));
        continue;
    }
    foreach ($group as &$subItems) {
        usort($subItems, static fn($a, $b) => strcasecmp($a['title'], $b['title']));
    }
}
unset($group);

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>
<div class="private-label-page">
  <div class="breadcumb">
    <div class="container rr-container-1895">
      <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
        <h1 class="text-center">Data Sheets</h1>
        <ul class="breadcumb-wrapper__items">
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="<?php echo htmlspecialchars(url('data-sheets.php'), ENT_QUOTES, 'UTF-8'); ?>" class="breadcumb-wrapper__items-list-title2">Data Sheets</a></li>
        </ul>
      </div>
    </div>
  </div>

  <section class=" section-spacing-120">
    <div class="container container-1352">
      <div class="row g-4 g-xl-5 align-items-start">
        <div class="col-lg-8">
          <div class="mb-4 cms-richtext">
            <h3 style="color:#ef3d85;">Download the Material Safety Data Sheet by clicking on the product of your choice.</h3>
            <p>We have conveniently provided the <strong>MSDS</strong> (Material Safety Data Sheet) files in Microsoft Word/PDF format so that you can modify as needed with your brand's information.</p>
          </div>

          <div class="private-label-intro-accordion" id="privateLabelIntroAccordion">
            <?php $index = 0; foreach ($mainGroups as $groupName => $items): ?>
              <?php if (empty($items)) { continue; } ?>
              <article class="private-label-intro-accordion__item<?php echo $index === 0 ? ' is-open' : ''; ?>">
                <button class="private-label-intro-accordion__btn" type="button">
                  <span class="private-label-intro-accordion__icon" aria-hidden="true"></span>
                  <span class="private-label-intro-accordion__title"><?php echo htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8'); ?></span>
                </button>
                <div class="private-label-intro-accordion__panel">
                  <div class="private-label-intro-accordion__body">
                    <?php if (array_values($items) === $items): ?>
                      <ul class="ds-links">
                        <?php foreach ($items as $item): ?>
                          <li><a href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <?php foreach ($items as $subName => $subItems): ?>
                        <h5 class="ds-subheading">~<?php echo htmlspecialchars($subName, ENT_QUOTES, 'UTF-8'); ?>~</h5>
                        <ul class="ds-links">
                          <?php foreach ($subItems as $item): ?>
                            <li><a href="<?php echo htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></a></li>
                          <?php endforeach; ?>
                        </ul>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                </div>
              </article>
            <?php $index++; endforeach; ?>
          </div>
        </div>

        <div class="col-lg-4">
          <aside class="private-label-sidebar">
            <div class="private-label-sidebar__social">
              <h3>Follow Us On Social Network</h3>
              <ul class="private-label-sidebar__social-list">
                <li><a href="contact.php" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a></li>
                <li><a href="contact.php" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                <li><a href="contact.php" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a></li>
                <li><a href="contact.php" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a></li>
                <li><a href="contact.php" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a></li>
                <li><a href="contact.php" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a></li>
              </ul>
            </div>

            <div class="private-label-sidebar__links">
              <h3>Quick Links</h3>
              <ul>
                <li><a href="how-it-works.php">How it works</a></li>
                <li><a href="contact.php">Additional services</a></li>
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

<style>
.ds-links { list-style: none; padding-left: 0; margin: 0 0 8px; }
.ds-links li { margin: 0 0 8px; }
.ds-links a { color: #000000; text-decoration: none; font-size: 15px; line-height: 1.5; }
.ds-links a:hover { text-decoration: underline; }
.ds-subheading { color: #ef3d85; font-weight: 700; margin: 18px 0 10px; text-transform: uppercase; }
@media (max-width: 768px) { .ds-links a { font-size: 20px; } }
</style>

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

<?php include __DIR__ . '/includes/footer.php'; ?>
