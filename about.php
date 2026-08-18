<?php
require_once __DIR__ . '/includes/cms.php';

$meta = [
  'title' => 'mybrandplease | about',
  'description' => 'mybrandplease - about page',
  'canonical' => 'about.php'
];
include 'includes/head.php';
include 'includes/header.php';

// Fetch dynamic CMS data for About Us
$introHeading = cms_get_setting('about_intro_heading', 'Thank you for your interest in <span class="theme-color-font">mybrandplease.com!</span>');
$blocksLayout = cms_get_setting('about_blocks_layout', 'default');
$aboutBlocks = cms_get_about_blocks();

$certHeading = cms_get_setting('about_certifications_heading', 'Our Trusted Certifications');
$certifications = cms_get_about_certifications();

$privateLabelHeading = cms_get_setting('about_private_label_heading', 'Why Private Label?');
$privateLabelIntro = cms_get_setting('about_private_label_intro', 'Unleash the power of your brand with our exclusive range of private label skin, hair, and body care products, strategically designed to elevate your reputation and drive exceptional profitability. Experience our top-notch quality private label cosmetics with low minimum order quantities (MOQs) and competitive pricing, ensuring customer loyalty, impressive profit margins, and sustainable returns.');
$privateLabelBlockTitle = cms_get_setting('about_private_label_block_title', 'Key Benefits');
$privateLabelImage = cms_get_setting('about_private_label_image', 'assets/imgs/about/Key-Benefits-min-768x466.jpg');
$keyBenefits = cms_get_about_key_benefits();

$accreditationsHeading = cms_get_setting('about_accreditations_heading', 'Accreditations & Associations');
$accreditationsIntro = cms_get_setting('about_accreditations_intro', 'Trusted compliance and industry partnerships that reinforce global quality standards.');
$accreditations = cms_get_about_accreditations();
?>

<div class="breadcumb">
  <div class="container rr-container-1895">
    <div class="breadcumb-wrapper about-breadcumb-banner section-spacing-120 fix" data-bg-src="<?php echo url('uploads/blog/WhatsApp-Image-2025-05-06-at-4.38.02-PM (1).webp'); ?>">
      <div class="breadcumb-wrapper__title">About Us</div>
      <ul class="breadcumb-wrapper__items">
        <li class="breadcumb-wrapper__items-list">
          <i class="fa-regular fa-house"></i>
        </li>
        <li class="breadcumb-wrapper__items-list">
          <i class="fa-regular fa-chevron-right"></i>
        </li>
        <li class="breadcumb-wrapper__items-list">
          <a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a>
        </li>
        <li class="breadcumb-wrapper__items-list">
          <i class="fa-regular fa-chevron-right"></i>
        </li>
        <li class="breadcumb-wrapper__items-list">
          <a href="about.php" class="breadcumb-wrapper__items-list-title2">About Us</a>
        </li>
      </ul>
    </div>
  </div>
</div>

<?php if (!empty($aboutBlocks)): ?>
  <?php foreach ($aboutBlocks as $index => $block): ?>
    <?php
      $blockIdStr = slugify($block['block_title'] ?? ('block-' . ($index + 1)));
      $bgStyle = ($index % 2 === 1) ? 'background-color: #FFF5F8;' : '';
      
      // Determine layout alignment based on $blocksLayout setting & individual block setting
      $rowClass = 'row align-items-center g-5';
      if ($blocksLayout === 'right') {
        $rowClass .= ' flex-row-reverse';
      } elseif ($blocksLayout === 'left') {
        // default rowClass, no flex-row-reverse
      } elseif ($blocksLayout === 'center') {
        // handled separately in center block rendering
      } else {
        // Default mode: alternate per block setting or index
        $isRight = ($block['layout'] ?? '') === 'right' || (($block['layout'] ?? '') !== 'left' && $index % 2 === 1);
        if ($isRight) {
          $rowClass .= ' flex-row-reverse';
        }
      }
    ?>
    <section class="about-info-block py-5 section-spacing-120" style="<?= $bgStyle ?>" id="<?= e($blockIdStr) ?>">
      <div class="container container-1352">
        <?php if ($index === 0 && !empty($introHeading)): ?>
          <div class="text-center mb-5">
            <h2 class="fw-bold"><?= $introHeading ?></h2>
          </div>
        <?php elseif (!empty($block['section_heading'])): ?>
          <div class="text-center mb-5">
            <h2 class="fw-bold mb-3"><?= $block['section_heading'] ?></h2>
            <?php if (!empty($block['section_intro'])): ?>
              <p class="text-muted fs-17"><?= $block['section_intro'] ?></p>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($blocksLayout === 'center'): ?>
          <!-- Centered Stacked Layout -->
          <div class="row justify-content-center text-center g-4">
            <?php if (!empty($block['image_path'])): ?>
              <div class="col-lg-10">
                <div class="about-img-wrapper about-scroll-reveal p-2 rounded-4">
                  <img src="<?= e(url($block['image_path'])) ?>" alt="<?= e($block['image_alt'] ?: $block['block_title']) ?>" class="img-fluid rounded-4" style="width: 100%; max-height: 500px; aspect-ratio: 2048 / 1238; object-fit: cover;">
                </div>
              </div>
            <?php endif; ?>
            <div class="col-lg-10">
              <div class="about-content">
                <h3 class="mb-3 theme-color-font"><?= e($block['block_title']) ?></h3>
                <div class="text-muted lh-base fs-17 word-spacing-6">
                  <?= $block['body'] ?>
                </div>
              </div>
            </div>
          </div>
        <?php else: ?>
          <!-- Side-by-Side Layout (Left / Right / Alternate) -->
          <div class="<?= $rowClass ?>">
            <?php if (!empty($block['image_path'])): ?>
              <div class="col-lg-6">
                <div class="about-img-wrapper about-scroll-reveal p-2 rounded-4">
                  <img src="<?= e(url($block['image_path'])) ?>" alt="<?= e($block['image_alt'] ?: $block['block_title']) ?>" class="img-fluid rounded-4" style="width: 100%; aspect-ratio: 2048 / 1238; object-fit: cover;">
                </div>
              </div>
            <?php endif; ?>
            <div class="col-lg-<?= !empty($block['image_path']) ? '6' : '12' ?>">
              <div class="about-content">
                <h3 class="mb-3 theme-color-font"><?= e($block['block_title']) ?></h3>
                <div class="text-muted lh-base fs-17 word-spacing-6">
                  <?= $block['body'] ?>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>

<!-- Certifications Section -->
<?php if (!empty($certifications)): ?>
  <section class="certifications section-spacing-120">
    <div class="container container-1352">
      <?php if (!empty($certHeading)): ?>
        <div class="text-center mb-5">
          <h3 class="fw-bold mb-2"><?= e($certHeading) ?></h3>
          <div class="cert-divider"></div>
        </div>
      <?php endif; ?>
      <div class="row g-4 text-center cert-grid">
        <?php foreach ($certifications as $cert): ?>
          <div class="col-xl-3 col-md-6">
            <div class="cert-item cert-card about-scroll-reveal h-100">
              <?php if (!empty($cert['icon_path'])): ?>
                <img src="<?= e(url($cert['icon_path'])) ?>" alt="<?= e($cert['title']) ?>" class="cert-icon">
              <?php endif; ?>
              <h5 class="theme-color-font cert-title"><?= e($cert['title']) ?></h5>
              <p class="cert-text mb-0"><?= e($cert['description']) ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<!-- Private Label & Key Benefits Section -->
<section class="private-label section-spacing-120" id="key-benifits">
  <div class="container container-1352">
    <?php if (!empty($privateLabelHeading) || !empty($privateLabelIntro)): ?>
      <div class="text-center private-label__heading">
        <?php if (!empty($privateLabelHeading)): ?>
          <h3 class="fw-bold mb-3 theme-color-font"><?= e($privateLabelHeading) ?></h3>
        <?php endif; ?>
        <?php if (!empty($privateLabelIntro)): ?>
          <p class="mb-0 private-label__intro text-muted lh-base fs-17 word-spacing-6">
            <?= e($privateLabelIntro) ?>
          </p>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div class="row align-items-start g-5 mt-2">
      <?php if (!empty($privateLabelImage)): ?>
        <div class="col-lg-6">
          <div class="private-label__image-wrap about-scroll-reveal">
            <img src="<?= e(url($privateLabelImage)) ?>" alt="<?= e($privateLabelBlockTitle) ?>" class="img-fluid rounded-4" style="width: 100%; aspect-ratio: 768 / 466; object-fit: cover;">
          </div>
        </div>
      <?php endif; ?>
      <div class="col-lg-<?= !empty($privateLabelImage) ? '6' : '12' ?>">
        <div class="private-label__content">
          <?php if (!empty($privateLabelBlockTitle)): ?>
            <h4 class="theme-color-font mb-4"><?= e($privateLabelBlockTitle) ?></h4>
          <?php endif; ?>
          <?php if (!empty($keyBenefits)): ?>
            <?php foreach ($keyBenefits as $bIndex => $benefit): ?>
              <p class="<?= $bIndex === count($keyBenefits) - 1 ? 'mb-0' : '' ?> text-muted lh-base fs-17 word-spacing-6">
                <strong class="theme-color-font"><?= e($benefit['label']) ?></strong> - <?= e($benefit['description']) ?>
              </p>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Accreditations Section -->
<?php if (!empty($accreditations)): ?>
  <section class="accreditations section-spacing-120">
    <div class="container container-1352">
      <?php if (!empty($accreditationsHeading) || !empty($accreditationsIntro)): ?>
        <div class="text-center mb-5">
          <?php if (!empty($accreditationsHeading)): ?>
            <h3 class="fw-bold theme-color-font mb-2"><?= e($accreditationsHeading) ?></h3>
          <?php endif; ?>
          <?php if (!empty($accreditationsIntro)): ?>
            <p class="text-muted lh-base fs-17 mb-0"><?= e($accreditationsIntro) ?></p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <div class="accreditations-grid">
        <?php foreach ($accreditations as $acc): ?>
          <div class="accreditation-card about-scroll-reveal">
            <img src="<?= e(url($acc['image_path'])) ?>" alt="<?= e($acc['alt_text']) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const revealItems = Array.from(document.querySelectorAll('.about-scroll-reveal'));
    if (!revealItems.length) return;

    revealItems.forEach(function (item, index) {
      item.style.setProperty('--about-reveal-delay', String(Math.min(index % 4, 3) * 80) + 'ms');
    });

    if (!('IntersectionObserver' in window)) {
      revealItems.forEach(function (item) {
        item.classList.add('is-visible');
      });
      return;
    }

    const observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      });
    }, { threshold: 0.18, rootMargin: '0px 0px -8% 0px' });

    revealItems.forEach(function (item) {
      observer.observe(item);
    });
  });
</script>
<?php include 'includes/footer.php'; ?>
