<?php
$meta = [
  'title' => 'mybrandplease | Services',
  'description' => 'mybrandplease - services page',
  'canonical' => 'services.php'
];
include 'includes/head.php';
include 'includes/header.php';
require_once 'includes/cms.php';

$layoutSetting = cms_get_services_layout();
$heroTitle = cms_get_setting('services_hero_title', 'Unleash Your Brand\'s Potential With Our Perfect Solution.');
$heroDescription = cms_get_setting('services_hero_description', 'Embrace complete customization, meticulously tailoring your product line to seamlessly harmonize with your brand and visionary essence.');
$sections = cms_get_services_sections(false);
$accordionItems = cms_get_services_accordions(false);

$hasDefaultOpen = false;
foreach ($accordionItems as $acc) {
    if (!empty($acc['is_open_default'])) {
        $hasDefaultOpen = true;
        break;
    }
}
?>

<div class="how-works-page">
  <div class="breadcumb">
    <div class="container rr-container-1895">
      <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
        <h1 class="text-center">Services</h1>
        <ul class="breadcumb-wrapper__items">
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list">
            <a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a>
          </li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list">
            <a href="services.php" class="breadcumb-wrapper__items-list-title2">Services</a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <section class="how-works-hero section-spacing-120" id="build-your-own-brand">
    <div class="container container-1352">
      <div class="text-center how-works-hero__head">
        <h2 class="theme-color-font"><?= e($heroTitle) ?></h2>
        <div class="how-works-hero__line"></div>
        <?php if (!empty($heroDescription)): ?>
          <div class="how-works-hero__desc text-muted lh-base fs-17 word-spacing-6">
            <?php if (str_contains($heroDescription, '<p') || str_contains($heroDescription, '<div')): ?>
              <?= $heroDescription ?>
            <?php else: ?>
              <p class="text-muted lh-base fs-17 word-spacing-6 mb-0">
                <?= nl2br($heroDescription) ?>
              </p>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="text-center how-works-hero__subhead">
        <h3>Get Started by Customizing Your Vision</h3>
        <div class="how-works-hero__subline"></div>
      </div>

      <div class="how-works-steps" id="product-offering-development">
        <?php foreach ($sections as $index => $sec): ?>
          <?php
            // Determine reverse / center class based on global layout setting
            $isReverse = false;
            if ($layoutSetting === 'default') {
                $isReverse = ($index % 2 !== 0);
            } elseif ($layoutSetting === 'right') {
                $isReverse = true;
            } elseif ($layoutSetting === 'left') {
                $isReverse = false;
            }

            // Anchor slug logic
            $titleLower = strtolower($sec['title']);
            $anchorId = preg_replace('/[^a-z0-9]+/', '-', $titleLower);
            if (str_contains($titleLower, 'components')) {
                $anchorId = 'product-components';
            } elseif (str_contains($titleLower, 'offerings')) {
                $anchorId = 'define-offerings';
            } elseif (str_contains($titleLower, 'label') || str_contains($titleLower, 'printing')) {
                $anchorId = 'design-and-printing';
            } elseif (str_contains($titleLower, 'finishing')) {
                $anchorId = 'finishing-touches';
            }

            $renderBody1 = str_contains($sec['body_1'], '<') ? $sec['body_1'] : '<p class="text-muted lh-base fs-17 word-spacing-6">' . nl2br(e($sec['body_1'])) . '</p>';
            $renderBody2 = !empty($sec['body_2']) ? (str_contains($sec['body_2'], '<') ? $sec['body_2'] : '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0 mt-2">' . nl2br(e($sec['body_2'])) . '</p>') : '';
          ?>

          <?php if ($layoutSetting === 'center'): ?>
            <div class="how-works-hero__feature-card how-works-hero__feature-card--center" id="<?= e($anchorId) ?>">
              <div class="how-works-hero__image-wrap">
                <img src="<?= e(url($sec['image_path'])) ?>" alt="<?= e($sec['title']) ?>" class="img-fluid" loading="lazy">
              </div>
              <div class="how-works-hero__content">
                <h4 class="theme-color-font"><?= e($sec['title']) ?></h4>
                <div class="how-works-hero__body text-muted lh-base fs-17 word-spacing-6">
                  <?= $renderBody1 ?>
                  <?php if ($renderBody2): ?>
                    <div class="mt-2"><?= $renderBody2 ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="how-works-hero__feature-card <?= $isReverse ? 'how-works-hero__feature-card--reverse' : '' ?>" id="<?= e($anchorId) ?>">
              <div class="row align-items-center g-0">
                <div class="col-lg-5">
                  <div class="how-works-hero__image-wrap">
                    <img src="<?= e(url($sec['image_path'])) ?>" alt="<?= e($sec['title']) ?>" class="img-fluid" loading="lazy">
                  </div>
                </div>
                <div class="col-lg-7">
                  <div class="how-works-hero__content">
                    <h4 class="theme-color-font"><?= e($sec['title']) ?></h4>
                    <div class="how-works-hero__body text-muted lh-base fs-17 word-spacing-6">
                      <?= $renderBody1 ?>
                      <?php if ($renderBody2): ?>
                        <div class="mt-2"><?= $renderBody2 ?></div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <section class="order-process section-spacing-120" id="logistics-support">
        <div class="order-process__head text-center">
          <p class="text-muted lh-base fs-17 word-spacing-6 mb-2">
            Once you have decided on the details of your product(s), you can place your order and begin the process of bringing your vision to life!
          </p>
          <h3>Here's What Your Order Process Will Look Like:</h3>
        </div>

        <div class="order-accordion" id="orderAccordion">
          <?php foreach ($accordionItems as $index => $acc): ?>
            <?php
              $isOpen = !empty($acc['is_open_default']) || (!$hasDefaultOpen && $index === 0);
            ?>
            <article class="order-accordion__item <?= $isOpen ? 'is-open' : '' ?>">
              <button class="order-accordion__btn" type="button">
                <span class="order-accordion__icon" aria-hidden="true"></span>
                <span class="order-accordion__title"><?= e($acc['title']) ?></span>
              </button>
              <div class="order-accordion__panel">
                <div class="order-accordion__body">
                  <?= $acc['body'] ?>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </section>
</div>

<script>
  (function () {
    const root = document.getElementById('orderAccordion');
    if (!root) return;
    const items = Array.from(root.querySelectorAll('.order-accordion__item'));

    function closeAll(exceptItem) {
      items.forEach((item) => {
        if (item === exceptItem) return;
        item.classList.remove('is-open');
      });
    }

    items.forEach((item) => {
      const btn = item.querySelector('.order-accordion__btn');
      if (!btn) return;
      btn.addEventListener('click', function () {
        const willOpen = !item.classList.contains('is-open');
        closeAll(item);
        item.classList.toggle('is-open', willOpen);
      });
    });
  })();
</script>

<?php include 'includes/footer.php'; ?>
