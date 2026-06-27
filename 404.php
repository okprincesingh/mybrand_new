<?php
$meta = [
  'title' => 'Mybrandplease | 404',
  'description' => 'Mybrandplease - 404 page',
  'canonical' => '404.php'
];
include 'includes/head.php';
include 'includes/header.php';
?>

<div class="breadcumb">
          <div class="container rr-container-1895">
            <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="<?php echo url('assets/imgs/breadcumbBg.jpg'); ?>">
              <div class="breadcumb-wrapper__title">Error _404</div>
              <ul class="breadcumb-wrapper__items">
                <li class="breadcumb-wrapper__items-list">
                  <i class="fa-regular fa-house"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <a href="index.php" class="breadcumb-wrapper__items-list-title">
                    Category
                  </a>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <i class="fa-regular fa-chevron-right"></i>
                </li>
                <li class="breadcumb-wrapper__items-list">
                  <a href="index.php" class="breadcumb-wrapper__items-list-title2">
                    Error _404
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>


        <div class="error section-spacing-120 rr-ov-hidden">
          <div class="container">
            <div class="row justify-content-center">
              <div class="col-lg-7 wow fadeInUp" data-wow-delay=".3s">
                <div class="error-items">
                  <div class="error-items__thumb">
                    <img src="<?php echo url('assets/imgs/404.png'); ?>" alt="img">
                  </div>
                  <h1 class="error-items__title">Oops!</h1>
                  <h2 class="error-items__title2">Error -404 Page Not Found</h2>
                  <p class="error-items__text">Sorry, the page you’re looking for doesn’t exist. Check the URL, return
                    to the homepage, or explore other sections to find what you need.</p>

                  <div class="intro3-content__button">
                    <a href="index.php" class="rr-btn-button">
                      <span class="text">Back To Home</span>
                      <span class="icon">
                        <svg width="16" height="10" viewBox="0 0 16 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M0.600006 4.59998H14.6M14.6 4.59998L10.6 8.59998M14.6 4.59998L10.6 0.599976"
                            stroke="#FFFFFF" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                      </span>
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

<?php include 'includes/footer.php'; ?>

