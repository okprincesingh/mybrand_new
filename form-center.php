<?php
$meta = [
    'title' => 'Private Label Cosmetic Formulation Center | Mybrandplease',
    'description' => 'Discover Mybrandplease Form Center offering expert private label cosmetic documentation and compliance support.',
    'canonical' => 'form-center.php',
];

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<div class="private-label-page">
  <div class="breadcumb">
    <div class="container rr-container-1895">
      <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
        <h1 class="text-center">FORM CENTER</h1>
        <ul class="breadcumb-wrapper__items">
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2">Form Center</span></li>
        </ul>
      </div>
    </div>
  </div>

  <section class="private-label-content section-spacing-120">
    <div class="container container-1352">
      <div class="row justify-content-center">
        <div class="col-lg-10">
          <div class="fc-copy cms-richtext">
            <h3 class="fc-heading text-center">Welcome to the mybrandplease.com Form Center.</h3>
            <p>For all compliance documentation, including but not limited to:</p>
            <ul class="fc-list">
              <li>🛡️ <strong>Non-Disclosure Agreements (NDA)</strong></li>
              <li>📦 <strong>MAP (Minimum Advertised Price) Policy</strong></li>
              <li>💲 <strong>MSRP (Manufacturer&apos;s Suggested Retail Price) Policy</strong></li>
              <li>✅ <strong>Private Label Compliance Forms</strong></li>
              <li>🧴 <strong>Product Regulatory Information Requests</strong></li>
            </ul>
            <p>
              to Download <strong class="fc-highlight">MSDS (Material Safety Data Sheet)</strong>, Please
              <a class="fc-link" href="<?php echo htmlspecialchars(url('data-sheets.php'), ENT_QUOTES, 'UTF-8'); ?>">click here</a>.
            </p>
            <p>
              Please send an email to
              <a class="fc-link" href="mailto:info@mybrandplease.com"><strong>info@mybrandplease.com</strong></a>
              with your specific requirements, and our team will respond promptly with the necessary documents and next steps.
            </p>
            <p>We're here to ensure your private label journey is smooth, compliant, and confidential.</p>
            <h4 class="fc-tagline">YOUR VISION | OUR EXPERTISE | YOUR BRAND</h4>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
.fc-copy {
  color: #3d3d3d;
  font-size: 20px;
  line-height: 1.8;
}
.fc-heading {
  color: #ef3d85;
  font-size: 44px;
  font-weight: 700;
  margin-bottom: 22px;
}
.fc-list {
  list-style: none;
  padding: 0;
  margin: 0 0 10px;
}
.fc-list li {
  color: #ef3d85;
  font-size: 19px;
  margin-bottom: 10px;
}
.fc-link,
.fc-highlight {
  color: #ef3d85;
}
.fc-tagline {
  color: #ef3d85;
  font-size: 44px;
  font-weight: 700;
  margin-top: 20px;
}
@media (max-width: 767px) {
  .fc-heading {
    font-size: 32px;
  }
  .fc-copy {
    font-size: 18px;
  }
  .fc-tagline {
    font-size: 29px;
    line-height: 1.4;
  }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
