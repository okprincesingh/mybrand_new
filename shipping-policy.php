<?php
$meta = [
    'title' => 'Shipping Policy | Mybrandplease',
    'description' => 'Shipping policy for Mybrandplease orders, sample preparation timelines, courier options, and delivery estimates.',
    'keywords' => 'shipping policy, mybrandplease shipping, worldwide shipping, sample delivery',
    'canonical' => 'shipping-policy.php',
];

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<div class="private-label-page">
  <div class="breadcumb">
    <div class="container rr-container-1895">
      <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
        <h1 class="text-center">Shipping Policy</h1>
        <ul class="breadcumb-wrapper__items">
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="<?php echo url('index.php'); ?>" class="breadcumb-wrapper__items-list-title">Home</a></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="<?php echo url('shipping-policy.php'); ?>" class="breadcumb-wrapper__items-list-title2">Shipping Policy</a></li>
        </ul>
      </div>
    </div>
  </div>

  <section class="private-label-content section-spacing-120">
    <div class="container container-1352">
      <div class="row g-4 g-xl-5 align-items-start">
        <div class="col-lg-8">
          <article class="cms-richtext">
            <h2>Shipping Policy</h2>
            <p>We are delighted to offer worldwide shipping! Please note that all orders placed through our website are exclusive of shipping charges; only the product cost is payable at checkout. Once your order is received, the sample creation process takes approximately 7 to 10 working days.</p>
            <p>Our preferred couriers include UPS, DHL, USPS, and ShipRocket. Once the samples are ready, delivery takes 7 to 10 working days for the US and European regions, and 10 to 15 working days for other areas. If you prefer to use your own courier service, please coordinate with us to arrange for sample or order pickup from our facility.</p>
            <p>For those opting to use our courier services, we will provide a shipping quote once your samples or orders are prepared and ready to ship.</p>
          </article>
        </div>

        <div class="col-lg-4">
          <aside class="private-label-sidebar">
            <div class="private-label-sidebar__social">
              <h3>Follow Us On Social Network</h3>
              <ul class="private-label-sidebar__social-list">
                <li><a href="<?php echo url('contact.php'); ?>" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a></li>
                <li><a href="<?php echo url('contact.php'); ?>" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a></li>
                <li><a href="<?php echo url('contact.php'); ?>" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a></li>
                <li><a href="<?php echo url('contact.php'); ?>" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a></li>
              </ul>
            </div>

            <div class="private-label-sidebar__links">
              <h3>Quick Links</h3>
              <ul>
                <li><a href="<?php echo url('how-it-works.php'); ?>">How it works</a></li>
                <li><a href="<?php echo url('contact.php'); ?>">Additional services</a></li>
                <li><a href="<?php echo url('faq.php'); ?>">FAQs</a></li>
                <li><a href="<?php echo url('blog.php'); ?>">Blog</a></li>
                <li><a href="<?php echo url('about.php'); ?>">About us</a></li>
              </ul>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
