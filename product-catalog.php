<?php
require_once __DIR__ . '/includes/url.php';

$meta = [
    'title' => 'Product Catalogue | Mybrandplease',
    'description' => 'Unveil a world of captivating possibilities with our extraordinary range of products.',
    'canonical' => 'product-catalog.php',
];

$catalogLink = url('uploads/resources/product-catalog/E-Catalouge_mybrandplease.com_-1.pdf');
$coverImage = url('uploads/resources/product-catalog/Mybrand_front-page-min.webp');

include __DIR__ . '/includes/head.php';
include __DIR__ . '/includes/header.php';
?>

<div class="private-label-page">
  <div class="breadcumb">
    <div class="container rr-container-1895">
      <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
        <h1 class="text-center">PRODUCT CATALOGUE</h1>
        <ul class="breadcumb-wrapper__items">
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><a href="index.php" class="breadcumb-wrapper__items-list-title">Home</a></li>
          <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
          <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2">Product Catalogue</span></li>
        </ul>
      </div>
    </div>
  </div>

  <section class=" section-spacing-120">
    <div class="container container-1352">
      <div class="row g-4 g-xl-5 align-items-start">
        <div class="col-lg-8">
          <div class="pc-copy cms-richtext">
            <h3 class="pc-kicker">PRODUCT CATALOGUE</h3>
            <p>
              Unveil a world of captivating possibilities with our extraordinary range of products. Explore an extensive selection of captivating offerings that will elevate your brand to new heights. Immerse yourself in the essence of luxury and innovation as you browse through our comprehensive catalogue. Ignite your imagination and discover the perfect fit for your vision. Embrace the power of choice and embark on a transformative journey to shape your brand's destiny.
            </p>

            <h2 class="pc-main">Discover an array of our captivating offerings</h2>
            <p class="pc-sub">~ Click Below To Download Our ~</p>
            <p class="pc-link-wrap">
              <a class="pc-link" href="<?php echo htmlspecialchars($catalogLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">PRODUCT CATALOGUE</a>
            </p>
          </div>
        </div>

        <div class="col-lg-4">
          <a class="pc-cover" href="<?php echo htmlspecialchars($catalogLink, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">
            <img src="<?php echo htmlspecialchars($coverImage, ENT_QUOTES, 'UTF-8'); ?>" alt="Product Catalogue Cover">
          </a>
        </div>
      </div>
    </div>
  </section>
</div>

<style>
.pc-copy {
  color: #3c3c3c;
}
.pc-kicker {
  color: #ef3d85;
  font-size: 44px;
  font-weight: 700;
  margin-bottom: 24px;
}
.pc-copy p {
  font-size: 18px;
  line-height: 1.7;
}
.pc-main {
  font-size: 62px;
  margin: 34px 0 14px;
  color: #2b2b2b;
  font-weight: 700;
}
.pc-sub {
  text-align: center;
  color: #ef3d85;
  font-size: 45px;
  font-weight: 700;
  margin: 0 0 8px;
}
.pc-link-wrap {
  text-align: center;
  margin: 0;
}
.pc-link {
  color: #2f2f2f;
  font-size: 30px;
  font-weight: 700;
  text-decoration: none;
}
.pc-link:hover {
  color: #ef3d85;
}
.pc-cover {
  display: block;
  border-radius: 24px;
  overflow: hidden;
  box-shadow: 0 10px 24px rgba(0,0,0,.18);
}
.pc-cover img {
  width: 100%;
  display: block;
}
@media (max-width: 991px) {
  .pc-kicker {
    font-size: 34px;
  }
  .pc-copy p {
    font-size: 18px;
  }
  .pc-main {
    font-size: 40px;
  }
  .pc-sub {
    font-size: 30px;
  }
  .pc-link {
    font-size: 34px;
  }
}
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>
