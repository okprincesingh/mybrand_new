<?php
/**
 * Order Process Accordion Component
 * Fetches dynamic order process accordions from database and renders accordion items.
 */
require_once __DIR__ . '/cms.php';

$accordionItems = cms_get_how_it_works_accordions(false);

$hasDefaultOpen = false;
foreach ($accordionItems as $acc) {
    if (!empty($acc['is_open_default'])) {
        $hasDefaultOpen = true;
        break;
    }
}
?>
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
