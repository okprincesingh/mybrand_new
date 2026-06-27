</main>
<?php
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/cms.php';
require_once __DIR__ . '/catalog.php';
$footerSections = cms_get_footer_sections();
$quickLinks = [];
$legalLinks = [];

if (isset($footerSections[0]['links']) && is_array($footerSections[0]['links'])) {
    $quickLinks = $footerSections[0]['links'];
}

if (isset($footerSections[1]['links']) && is_array($footerSections[1]['links'])) {
    $legalLinks = $footerSections[1]['links'];
}

?>

            <footer class="pl-footer pl-footer--v2 rr-ov-hidden">
        <div class="container rr-container-1350">
          <div class="row g-5 pb-5">
            <div class="col-lg-4">
                <div class="plf-brand">
                  <a href="<?php echo url('index.php'); ?>" class="plf-logo d-inline-block mb-3">
                    <img src="<?php echo url('assets/imgs/logo/footerlogo.webp'); ?>" alt="mybrandplease">
                  </a>
                <p class="plf-lead">Get in touch with us however is most convenient for you.</p>
                <p class="plf-contact"><span>Call / WhatsApp:</span> +91 (971) 700 4615</p>
                <p class="plf-contact"><span>Email:</span> info@mybrandplease.com</p>

                  <div class="plf-follow mt-4">
                    <a href="<?php echo url('contact.php'); ?>" class="plf-follow-btn">FOLLOW US <i class="fa-solid fa-user"></i></a>
                  <div class="plf-social mt-3">
                    <a href="<?php echo url('https://www.youtube.com/@mybrandplease'); ?>" aria-label="Youtube"><i class="fa-brands fa-youtube"></i></a>
                    <a href="<?php echo url('https://www.facebook.com/mybrandplease'); ?>" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="<?php echo url('https://www.instagram.com/mybrandplease_/'); ?>" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@mybrandplease.com" target="_blank" rel="noopener noreferrer" aria-label="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                    <a href="<?php echo url('https://x.com/mybrandplease'); ?>" aria-label="X"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="<?php echo url('https://www.linkedin.com/in/mybrandplease'); ?>" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="<?php echo url('https://in.pinterest.com/mybrandplease/'); ?>" aria-label="Pinterest"><i class="fa-brands fa-pinterest-p"></i></a>
                  </div>
                </div>
                <div class="plf-review mt-4">
                  <a href="https://g.co/kgs/YgaRfY" target="_blank"><img src="<?php echo url('assets/imgs/home/footer/google-Reviews_mybrand.webp'); ?>" alt="Google Reviews"></a>
                  <a href="https://www.trustpilot.com/review/mybrandplease.com?utm_medium=trustbox&utm_source=TrustBoxReviewCollector" target="_blank"><img src="<?php echo url('assets/imgs/home/footer/Trust-Pilot-Reviews_mybrand.webp'); ?>" alt="Trustpilot Reviews"></a>
                </div>
              </div>
            </div>

            <div class="col-sm-6 col-lg-2">
              <h4 class="plf-title">QUICK LINKS</h4>
              <ul class="plf-list list-unstyled mb-4">
                <li><a href="<?php echo htmlspecialchars(catalog_shop_link('skin-care'), ENT_QUOTES, 'UTF-8'); ?>">Skin Care</a></li>
                <li><a href="<?php echo htmlspecialchars(catalog_shop_link('body-care'), ENT_QUOTES, 'UTF-8'); ?>">Body Care</a></li>
                <li><a href="<?php echo htmlspecialchars(catalog_shop_link('hair-care'), ENT_QUOTES, 'UTF-8'); ?>">Hair Care</a></li>
                <li><a href="<?php echo htmlspecialchars(catalog_shop_link('bathing-soaps'), ENT_QUOTES, 'UTF-8'); ?>">Bathing Soaps</a></li>
                <li><a href="<?php echo htmlspecialchars(catalog_shop_link('especially-for-men'), ENT_QUOTES, 'UTF-8'); ?>">For Men</a></li>
                <li><a href="<?php echo htmlspecialchars(catalog_shop_link('fragrances'), ENT_QUOTES, 'UTF-8'); ?>">Fragrance</a></li>
                <li><a href="<?php echo htmlspecialchars(url('product-catalog.php'), ENT_QUOTES, 'UTF-8'); ?>">Our Product Catalogue</a></li>
                <li><a href="<?php echo htmlspecialchars(url('https://mybrandplease.trustpass.alibaba.com/'), ENT_QUOTES, 'UTF-8'); ?>">mybrandplease@alibaba.com</a></li>
              </ul>

            </div>

            <div class="col-sm-6 col-lg-3">
              <h4 class="plf-title">COMPLIANCES</h4>
              <ul class="plf-list list-unstyled">
                <li><a href="<?php echo url('https://www.fda.gov/'); ?>">FDA Registered</a></li>
                <li><a href="<?php echo url('https://www.iso.org/standard/36437.html'); ?>">ISO 22716 Certified</a></li>
                <li><a href="<?php echo url('https://ec.europa.eu/growth/tools-databases/cosing/reference/annexes'); ?>">Compliant to EU CosIng</a></li>
                <li><a href="<?php echo url('https://www.fda.gov/cosmetics/cosmetics-laws-regulations/modernization-cosmetics-regulation-act-2022-mocra'); ?>">MoCRA Compliant</a></li>
                <li><a href="<?php echo url('https://www.ewg.org/ewgverified/'); ?>">EWG Verified®</a></li>
                <li><a href="<?php echo url('https://credobeauty.com/pages/the-credo-clean-standard-1'); ?>">Credo Clean Standard</a></li>
                <li><a href="<?php echo url('https://madesafe.org/collections/cosmetics'); ?>">MADE SAFE®</a></li>
                <li><a href="<?php echo url('https://cleanlabelproject.org/clean-label-project-certification/'); ?>">Clean Label Project</a></li>
                <li><a href="<?php echo url('https://www.crueltyfreeinternational.org/for-brands/our-approval-programme/'); ?>">Cruelty-Free Compliant</a></li>
                <li><a href="<?php echo url('https://biorius.com/cosmetics-certifications/vegan-certification/'); ?>">Vegan Certified</a></li>
              </ul>
            </div>

            <div class="col-sm-6 col-lg-3">
              <h4 class="plf-title">LEGAL DISCLAIMERS</h4>
              <ul class="plf-list list-unstyled mb-4">
                <!-- CMS legal links intentionally not rendered here; static links below are used instead. -->
                 <li><a href="<?php echo url('contact.php'); ?>">Terms of Service</a></li>
                  <li><a href="<?php echo url('privacy.php'); ?>">Privacy Policy</a></li>
                  <li><a href="<?php echo url('contact.php'); ?>">Refund Policy</a></li>
                  <li><a href="<?php echo url('contact.php'); ?>">Shipping Policy</a></li>
                  <li><a href="<?php echo url('contact.php'); ?>">Form Center</a></li>
              </ul>

              <div class="plf-badges">
                <img src="<?php echo url('assets/imgs/home/footer/fei.webp'); ?>" alt="FEI">
                <img src="<?php echo url('assets/imgs/home/footer/duns.webp'); ?>" alt="Duns">
                <img src="<?php echo url('assets/imgs/home/footer/CPNP-Registered.webp'); ?>" alt="CPNP Registered">
              </div>

              <div class="plf-pay mt-4">
                <p class="mb-2">Secure Payment</p>
                <img class="plf-pay__img" src="<?php echo url('assets/imgs/home/footer/PG-500x151.webp'); ?>" alt="Secure payment gateways">
              </div>
            </div>
          </div>

          <div class="plf-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <p class="mb-0">&copy; 2005-2026 NIMISHA IMPEX WORLDWIDE (P) LIMITED | All rights reserved</p>
            <p class="mb-0">Celebrating 21 Years of Private Labelling Excellence</p>
          </div>
        </div>
      </footer>

      <div class="enquiry-modal" id="enquiry-modal" aria-hidden="true">
        <div class="enquiry-modal__backdrop" data-enquiry-close></div>
        <div class="enquiry-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="enquiry-modal-title">
          <button type="button" class="enquiry-modal__close" data-enquiry-close aria-label="Close enquiry form">&times;</button>
          <h3 class="enquiry-modal__title" id="enquiry-modal-title">Request a Free Consultation</h3>
          <form class="enquiry-modal__form" method="post" action="<?php echo htmlspecialchars(url('contact.php'), ENT_QUOTES, 'UTF-8'); ?>">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="enquiry-modal__field">
                  <span>Name</span>
                  <input type="text" name="name" class="form-control" required>
                </label>
              </div>
              <div class="col-md-6">
                <label class="enquiry-modal__field">
                  <span>Phone</span>
                  <input type="text" name="phone" class="form-control" required>
                </label>
              </div>
            </div>
            
            <div class="row g-3">
              <div class="col-md-6">
                <label class="enquiry-modal__field">
                  <span>Email</span>
                  <input type="email" name="email" class="form-control" required>
                </label>
              </div>
              <div class="col-md-6">
                <label class="enquiry-modal__field">
                  <span>Phone</span>
                  <input type="tel" name="phone" class="form-control" required>
                </label>
              </div>
            </div>

            <label class="enquiry-modal__field">
              <span>Address</span>
              <textarea name="address" class="form-control" rows="3" required></textarea>
            </label>

            <label class="enquiry-modal__field">
              <span>Requirements</span>
              <textarea name="requirements" class="form-control" rows="4" placeholder="Please describe your requirements in detail..." required></textarea>
            </label>
            
            <div class="text-center">
              <button type="submit" class="enquiry-modal__submit">Submit Request</button>
            </div>
          </form>
        </div>
      </div>

      <div class="site-visit-popup" id="site-visit-popup" aria-hidden="true">
        <div class="site-visit-popup__backdrop" data-site-popup-close></div>
        <div class="site-visit-popup__dialog" role="dialog" aria-modal="true" aria-labelledby="site-visit-popup-title">
          <button type="button" class="site-visit-popup__close" data-site-popup-close aria-label="Close popup">&times;</button>
          <span class="site-visit-popup__eyebrow">Private Label Cosmetics</span>
          <h3 class="site-visit-popup__title" id="site-visit-popup-title">Build Your Brand With Mybrandplease</h3>
          <p class="site-visit-popup__text">
            We help salons, spas, retailers, hotels, and growing brands create premium skin care, hair care, body care, and personal care products with low MOQ and expert private label support.
          </p>
          <div class="site-visit-popup__points">
            <span><i class="fa-solid fa-check"></i> Custom formulations</span>
            <span><i class="fa-solid fa-check"></i> Label design support</span>
            <span><i class="fa-solid fa-check"></i> Worldwide enquiries</span>
          </div>
          <button type="button" class="site-visit-popup__button" id="site-popup-enquiry-btn">
            Get in Touch
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <a
        href="https://wa.me/919717004615"
        class="whatsapp-chat-button"
        id="whatsapp-chat-button"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat with us on WhatsApp">
        <i class="fa-brands fa-whatsapp" aria-hidden="true"></i>
      </a>

      <style>
        /* CTA Section Styles */
        .cta-section {
          background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
          position: relative;
          overflow: hidden;
        }
        
        .cta-wrapper {
          position: relative;
          z-index: 1;
        }
        
        .cta-content {
          text-align: center;
          padding: 40px 20px;
        }
        
        .cta-title {
          font-size: 2.5rem;
          font-weight: 800;
          color: #2d3436;
          margin-bottom: 15px;
          line-height: 1.2;
        }
        
        .cta-subtitle {
          font-size: 1.1rem;
          color: #636e72;
          margin-bottom: 30px;
          line-height: 1.6;
        }
        
        .cta-stats {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
          gap: 20px;
          margin-top: 30px;
        }
        
        .cta-stat {
          background: white;
          padding: 20px;
          border-radius: 12px;
          box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
          text-align: center;
          transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .cta-stat:hover {
          transform: translateY(-5px);
          box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15);
        }
        
        .cta-stat-number {
          display: block;
          font-size: 2rem;
          font-weight: 800;
          color: #ee2d7a;
          margin-bottom: 5px;
        }
        
        .cta-stat-label {
          font-size: 0.9rem;
          color: #636e72;
          font-weight: 600;
          text-transform: uppercase;
          letter-spacing: 1px;
        }
        
        .cta-form {
          padding: 40px 20px;
        }
        
        .cta-form-card {
          background: white;
          padding: 40px;
          border-radius: 16px;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
          text-align: center;
          max-width: 500px;
          margin: 0 auto;
        }
        
        .cta-form-title {
          font-size: 1.8rem;
          font-weight: 700;
          color: #2d3436;
          margin-bottom: 10px;
        }
        
        .cta-form-subtitle {
          color: #636e72;
          margin-bottom: 30px;
          line-height: 1.6;
        }
        
        .cta-enquiry-btn {
          background: linear-gradient(135deg, #ee2d7a 0%, #ff6b6b 100%);
          color: white;
          border: none;
          padding: 16px 32px;
          border-radius: 50px;
          font-size: 1.1rem;
          font-weight: 700;
          cursor: pointer;
          transition: all 0.3s ease;
          display: inline-flex;
          align-items: center;
          gap: 12px;
          box-shadow: 0 4px 15px rgba(238, 45, 122, 0.4);
          text-transform: uppercase;
          letter-spacing: 1px;
        }
        
        .cta-enquiry-btn:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(238, 45, 122, 0.6);
          background: linear-gradient(135deg, #ff6b6b 0%, #ee2d7a 100%);
        }
        
        .cta-btn-icon {
          transition: transform 0.3s ease;
        }
        
        .cta-enquiry-btn:hover .cta-btn-icon {
          transform: translateX(4px);
        }

        /* Enquiry Modal Styles */
        .enquiry-modal {
          position: fixed;
          inset: 0;
          z-index: 11000;
          display: none;
          align-items: center;
          justify-content: center;
          padding: 16px;
        }
        .enquiry-modal.is-open {
          display: flex;
        }
        .enquiry-modal__backdrop {
          position: absolute;
          inset: 0;
          background: rgba(12, 12, 12, 0.56);
          backdrop-filter: blur(4px);
        }
        .enquiry-modal__dialog {
          position: relative;
          width: min(600px, 100%);
          max-height: 90vh;
          overflow: auto;
          border-radius: 16px;
          background: #fff;
          padding: 30px;
          box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
          animation: modalSlideIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        @keyframes modalSlideIn {
          from {
            opacity: 0;
            transform: translateY(-30px) scale(0.95);
          }
          to {
            opacity: 1;
            transform: translateY(0) scale(1);
          }
        }
        
        .enquiry-modal__title {
          margin: 0 0 20px;
          font-size: 28px;
          font-weight: 800;
          color: #2d3436;
          text-align: center;
        }
        .enquiry-modal__close {
          position: absolute;
          top: 15px;
          right: 20px;
          border: 0;
          background: transparent;
          font-size: 32px;
          color: #636e72;
          line-height: 1;
          cursor: pointer;
          transition: color 0.2s ease, transform 0.2s ease;
        }
        
        .enquiry-modal__close:hover {
          color: #ee2d7a;
          transform: rotate(90deg);
        }
        
        .enquiry-modal__form {
          display: grid;
          gap: 16px;
        }
        .enquiry-modal__field {
          display: grid;
          gap: 8px;
          font-size: 14px;
          font-weight: 600;
          color: #334155;
        }
        
        .enquiry-modal__field label {
          display: block;
          margin-bottom: 4px;
          color: #2d3436;
          font-weight: 700;
          font-size: 12px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        
        .enquiry-modal__field input,
        .enquiry-modal__field textarea,
        .enquiry-modal__field select {
          width: 100%;
          border: 2px solid #e9ecef;
          border-radius: 12px;
          padding: 14px 16px;
          font-size: 16px;
          color: #2d3436;
          transition: all 0.3s ease;
          background: #fafafa;
        }
        
        .enquiry-modal__field input:focus,
        .enquiry-modal__field textarea:focus,
        .enquiry-modal__field select:focus {
          outline: none;
          border-color: #ee2d7a;
          background: white;
          box-shadow: 0 0 0 3px rgba(238, 45, 122, 0.1), inset 0 1px 3px rgba(0,0,0,0.1);
          transform: translateY(-1px);
        }
        
        .enquiry-modal__field textarea {
          min-height: 100px;
          resize: vertical;
        }
        
        /* Bootstrap form control styling override */
        .form-control, .form-select {
          border-radius: 12px !important;
          border: 2px solid #e9ecef !important;
          padding: 8px 16px !important;
          font-size: 16px !important;
          color: #2d3436 !important;
          background: #fafafa !important;
          transition: all 0.3s ease !important;
        }
        
        .form-control:focus, .form-select:focus {
          border-color: #ee2d7a !important;
          background: white !important;
          transform: translateY(-1px) !important;
        }
        
        /* Fix for select dropdown z-index and positioning */
        .enquiry-modal__dialog .form-select {
          position: relative;
          z-index: 1;
        }
        
        .enquiry-modal__dialog .form-select:focus {
          z-index: 2;
        }
        
        .enquiry-modal__submit {
          width: 100%;
          border: 0;
          border-radius: 50px;
          padding: 14px 24px;
          background: linear-gradient(135deg, #ee2d7a 0%, #ff6b6b 100%);
          color: #fff;
          font-weight: 800;
          font-size: 16px;
          cursor: pointer;
          transition: all 0.3s ease;
          box-shadow: 0 4px 15px rgba(238, 45, 122, 0.4);
          text-transform: uppercase;
          letter-spacing: 1px;
        }
        
        .enquiry-modal__submit:hover {
          transform: translateY(-2px);
          box-shadow: 0 6px 20px rgba(238, 45, 122, 0.6);
          background: linear-gradient(135deg, #ff6b6b 0%, #ee2d7a 100%);
        }
        
        .enquiry-modal__submit:active {
          transform: translateY(0);
        }

        /* Site Visit Popup */
        .site-visit-popup {
          position: fixed;
          inset: 0;
          z-index: 10950;
          display: none;
          align-items: center;
          justify-content: center;
          padding: 18px;
        }
        .site-visit-popup.is-open {
          display: flex;
        }
        .site-visit-popup__backdrop {
          position: absolute;
          inset: 0;
          background: rgba(12, 12, 12, 0.58);
          backdrop-filter: blur(5px);
        }
        .site-visit-popup__dialog {
          position: relative;
          width: min(520px, 100%);
          border-radius: 18px;
          background: #fff;
          padding: 34px 30px 30px;
          box-shadow: 0 24px 70px rgba(0, 0, 0, 0.28);
          animation: modalSlideIn 0.3s ease;
          overflow: hidden;
        }
        .site-visit-popup__dialog::before {
          content: "";
          position: absolute;
          inset: 0 0 auto;
          height: 6px;
          background: linear-gradient(90deg, #ee2d7a, #ff8067);
        }
        .site-visit-popup__close {
          position: absolute;
          top: 12px;
          right: 16px;
          border: 0;
          background: transparent;
          color: #5f6368;
          cursor: pointer;
          font-size: 30px;
          line-height: 1;
          transition: color 0.2s ease, transform 0.2s ease;
        }
        .site-visit-popup__close:hover {
          color: #ee2d7a;
          transform: rotate(90deg);
        }
        .site-visit-popup__eyebrow {
          display: inline-flex;
          margin-bottom: 12px;
          color: #ee2d7a;
          font-size: 13px;
          font-weight: 800;
          letter-spacing: 1px;
          text-transform: uppercase;
        }
        .site-visit-popup__title {
          margin: 0 28px 14px 0;
          color: #202124;
          font-size: 30px;
          line-height: 1.18;
          font-weight: 800;
        }
        .site-visit-popup__text {
          margin: 0;
          color: #555b61;
          font-size: 16px;
          line-height: 1.7;
        }
        .site-visit-popup__points {
          display: grid;
          gap: 10px;
          margin: 22px 0 26px;
        }
        .site-visit-popup__points span {
          display: flex;
          align-items: center;
          gap: 10px;
          color: #2d3436;
          font-weight: 700;
        }
        .site-visit-popup__points i {
          width: 22px;
          height: 22px;
          border-radius: 50%;
          background: #ffeaf3;
          color: #ee2d7a;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-size: 12px;
          flex: 0 0 auto;
        }
        .site-visit-popup__button {
          width: 100%;
          min-height: 52px;
          border: 0;
          border-radius: 999px;
          background: linear-gradient(135deg, #ee2d7a 0%, #ff6b6b 100%);
          color: #fff;
          cursor: pointer;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          gap: 10px;
          font-size: 16px;
          font-weight: 800;
          text-transform: uppercase;
          letter-spacing: 1px;
          box-shadow: 0 8px 22px rgba(238, 45, 122, 0.35);
          transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .site-visit-popup__button:hover {
          color: #fff;
          transform: translateY(-2px);
          box-shadow: 0 10px 28px rgba(238, 45, 122, 0.45);
        }

        @media (max-width: 575px) {
          .site-visit-popup__dialog {
            padding: 30px 22px 24px;
            border-radius: 14px;
          }
          .site-visit-popup__title {
            font-size: 25px;
          }
          .site-visit-popup__text {
            font-size: 15px;
          }
        }

        .whatsapp-chat-button {
          position: fixed;
          right: 20px;
          bottom: 76px;
          width: 46px;
          height: 46px;
          border-radius: 50%;
          background: #25d366;
          color: #fff;
          display: inline-flex;
          align-items: center;
          justify-content: center;
          font-size: 26px;
          box-shadow: 0 10px 26px rgba(37, 211, 102, 0.34);
          z-index: 100;
          opacity: 0;
          visibility: hidden;
          transform: translateY(-100px);
          transition: opacity 0.25s ease, visibility 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
        }
        .whatsapp-chat-button.active-progress {
          opacity: 1;
          visibility: visible;
          transform: translateY(0) scale(1);
        }
        .whatsapp-chat-button:hover,
        .whatsapp-chat-button:focus-visible {
          color: #fff;
          transform: translateY(-2px) scale(1.04);
          box-shadow: 0 12px 30px rgba(37, 211, 102, 0.46);
        }
        .whatsapp-chat-button:focus-visible {
          outline: 2px solid #fff;
          outline-offset: 3px;
        }

        @media (max-width: 575px) {
          .whatsapp-chat-button {
            right: 20px;
            bottom: 76px;
            width: 46px;
            height: 46px;
            font-size: 26px;
          }
        }
      </style>

    </div>
  </div>



  <script src="<?php echo url('assets/vandor/jquery/jquery.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/bootstrap/bootstrap.bundle.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/popup/jquery.magnific-popup.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/swiper/swiper-bundle.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/gsap/gsap.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/gsap/ScrollSmoother.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/gsap/ScrollTrigger.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/gsap/SplitText.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/gsap/SplitType.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/gsap/customEase.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/odometer/odometer.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/odometer/waypoints.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/menu/jquery.meanmenu.min.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/backtop/backToTop.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/nice-select/nice-select.js'); ?>"></script>
  <script src="<?php echo url('assets/vandor/wow/wow.min.js'); ?>"></script>

  <script src="<?php echo url('assets/vandor/common-js/common.js'); ?>"></script>
  <script src="<?php echo url('assets/js/main.js'); ?>"></script>
  <script src="<?php echo url('assets/js/categories-sidebar.js'); ?>"></script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const offcanvasRoot = document.getElementById('mocOffcanvas');
      const panel = offcanvasRoot ? offcanvasRoot.querySelector('.moc-panel') : null;
      const track = document.getElementById('mocTrack');
      const submenuList = document.getElementById('mocSubmenuList');
      const backBtn = document.getElementById('mocBack');
      const title = document.getElementById('mocTitle');
      const sideToggle = document.querySelector('.side-toggle');
      const overlay = document.querySelector('.offcanvas-overlay');
      const closeBtns = document.querySelectorAll('.side-info-close');

      if (!offcanvasRoot || !panel || !track || !submenuList || !backBtn || !title) {
        return;
      }

      function parseJsonAttr(name) {
        const raw = track.getAttribute(name);
        if (!raw) return [];
        try {
          const parsed = JSON.parse(raw);
          return Array.isArray(parsed) ? parsed : [];
        } catch (error) {
          return [];
        }
      }

      const menuTree = parseJsonAttr('data-menu-tree');
      const menuIndex = {};
      const menuStack = [];

      function escapeHtml(value) {
        return String(value)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }

      function indexMenu(items) {
        (items || []).forEach(function (item) {
          if (!item || typeof item !== 'object') {
            return;
          }
          menuIndex[String(item.id || '')] = item;
          if (Array.isArray(item.children) && item.children.length) {
            indexMenu(item.children);
          }
        });
      }

      function buildLinkMarkup(item) {
        const label = escapeHtml(item.title || '');
        const href = escapeHtml(item.url || '#');
        const children = Array.isArray(item.children) ? item.children : [];

        if (!children.length) {
          return '<li><a class="moc-link" href="' + href + '">' + label + '</a></li>';
        }

        return '<li><button type="button" class="moc-link moc-sub-parent" data-menu-id="' + escapeHtml(item.id || '') + '"><span>' + label + '</span><span class="moc-chevron" aria-hidden="true">&#8250;</span></button></li>';
      }

      function bindSubmenuButtons() {
        submenuList.querySelectorAll('.moc-sub-parent').forEach(function (button) {
          button.addEventListener('click', function () {
            const item = menuIndex[String(button.getAttribute('data-menu-id') || '')];
            if (!item) {
              return;
            }
            menuStack.push(item);
            renderCurrentSubmenu();
          });
        });
      }

      function renderCurrentSubmenu() {
        const currentItem = menuStack[menuStack.length - 1];
        if (!currentItem) {
          showRoot();
          return;
        }

        const children = Array.isArray(currentItem.children) ? currentItem.children : [];
        const introLink = '<li><a class="moc-link" href="' + escapeHtml(currentItem.url || '#') + '">' + escapeHtml(currentItem.title || '') + '</a></li>';
        submenuList.innerHTML = introLink + children.map(buildLinkMarkup).join('');
        title.textContent = String(currentItem.title || 'Menu');
        bindSubmenuButtons();
      }

      function showRoot() {
        menuStack.length = 0;
        track.classList.remove('is-submenu');
        backBtn.classList.remove('is-visible');
        title.textContent = 'Menu';
      }

      function showSubmenu(item) {
        if (!item) {
          return;
        }
        menuStack.length = 0;
        menuStack.push(item);
        renderCurrentSubmenu();
        track.classList.add('is-submenu');
        backBtn.classList.add('is-visible');
      }

      indexMenu(menuTree);

      backBtn.addEventListener('click', function () {
        if (menuStack.length > 1) {
          menuStack.pop();
          renderCurrentSubmenu();
          return;
        }
        showRoot();
      });

      offcanvasRoot.querySelectorAll('.moc-parent').forEach(function (parentBtn) {
        parentBtn.addEventListener('click', function () {
          const item = menuIndex[String(parentBtn.getAttribute('data-menu-id') || '')];
          showSubmenu(item);
        });
      });

      if (sideToggle) {
        sideToggle.addEventListener('click', function () {
          setTimeout(showRoot, 250);
        });
      }
      if (overlay) {
        overlay.addEventListener('click', function () {
          setTimeout(showRoot, 200);
        });
      }
      closeBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
          setTimeout(showRoot, 200);
        });
      });
    });
  </script>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Initialize nice-select for dropdowns using jQuery (wait for jQuery to be ready)
      function initNiceSelect() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.niceSelect) {
          // Category and language dropdowns are custom (--custom), no nice-select
        } else {
          // Retry if jQuery or niceSelect not ready yet
          setTimeout(initNiceSelect, 100);
        }
      }

      // Initialize when jQuery is ready
      if (typeof jQuery !== 'undefined') {
        jQuery(document).ready(function () {
          initNiceSelect();
        });
      } else {
        // Fallback: custom language dropdown runs via DOM script below
      }

      // Custom category dropdown (header-area-1)
      (function () {
        const wrap = document.getElementById('header-search-category-wrap');
        const trigger = document.getElementById('header-search-category-trigger');
        const list = document.getElementById('header-search-category-list');
        const label = wrap && wrap.querySelector('.header-area-1__search-category-label');
        const hiddenInput = document.getElementById('header-search-category');
        if (!wrap || !trigger || !list || !hiddenInput) return;

        function open() {
          wrap.classList.add('is-open');
          wrap.setAttribute('aria-expanded', 'true');
          trigger.setAttribute('aria-expanded', 'true');
        }
        function close() {
          wrap.classList.remove('is-open');
          wrap.setAttribute('aria-expanded', 'false');
          trigger.setAttribute('aria-expanded', 'false');
        }
        function toggle() {
          wrap.classList.toggle('is-open');
          const expanded = wrap.classList.contains('is-open');
          wrap.setAttribute('aria-expanded', expanded);
          trigger.setAttribute('aria-expanded', expanded);
        }

        trigger.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          toggle();
        });

        list.querySelectorAll('.header-area-1__search-category-option').forEach(function (option) {
          option.addEventListener('click', function (e) {
            e.preventDefault();
            const value = this.getAttribute('data-value');
            const text = this.textContent.trim();
            if (hiddenInput) hiddenInput.value = value;
            if (label) label.textContent = text;
            list.querySelectorAll('.header-area-1__search-category-option').forEach(function (opt) {
              opt.setAttribute('aria-selected', opt === option ? 'true' : 'false');
            });
            close();
          });
        });

        document.addEventListener('click', function (e) {
          if (wrap.classList.contains('is-open') && !wrap.contains(e.target)) {
            close();
          }
        });
      })();

      // Header language switcher with Google Translate integration.
      (function () {
        const switcher = document.getElementById('header-lang-switcher');
        const trigger = document.getElementById('header-lang-trigger');
        const menu = document.getElementById('header-lang-menu');
        const label = document.getElementById('header-lang-label');
        const flag = document.getElementById('header-lang-flag');
        const options = switcher ? switcher.querySelectorAll('.header-lang-switcher__option') : [];
        const supportedLanguages = ['en', 'fr', 'es'];
        const languageNames = {
          en: 'EN',
          fr: 'FR',
          es: 'ES'
        };
        const flagClasses = {
          en: 'flag-en',
          fr: 'flag-fr',
          es: 'flag-es'
        };

        if (!switcher || !trigger || !menu || !label || !flag || !options.length) return;

        function setCookie(name, value, days) {
          const expires = new Date(Date.now() + days * 86400000).toUTCString();
          const hostname = window.location.hostname;
          const cookieParts = [name + '=' + value, 'expires=' + expires, 'path=/'];
          document.cookie = cookieParts.join('; ');
          if (hostname && hostname.indexOf('.') !== -1) {
            document.cookie = cookieParts.concat(['domain=.' + hostname.replace(/^www\./i, '')]).join('; ');
          }
        }

        function clearCookie(name) {
          const hostname = window.location.hostname;
          document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
          if (hostname && hostname.indexOf('.') !== -1) {
            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/; domain=.' + hostname.replace(/^www\./i, '');
          }
        }

        function getCookie(name) {
          const match = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/[.$?*|{}()[\]\\/+^]/g, '\\$&') + '=([^;]*)'));
          return match ? decodeURIComponent(match[1]) : '';
        }

        function getCurrentLanguage() {
          const cookieValue = getCookie('googtrans');
          const match = cookieValue.match(/\/[a-z]{2}\/([a-z]{2})/i);
          const lang = match ? match[1].toLowerCase() : 'en';
          return supportedLanguages.includes(lang) ? lang : 'en';
        }

        function updateSelectedLanguage(lang) {
          const nextLang = supportedLanguages.includes(lang) ? lang : 'en';
          label.textContent = languageNames[nextLang] || languageNames.en;
          flag.className = 'header-lang-switcher__flag ' + (flagClasses[nextLang] || flagClasses.en);

          options.forEach(function (option) {
            const isActive = option.getAttribute('data-lang') === nextLang;
            option.classList.toggle('is-active', isActive);
            option.setAttribute('aria-selected', isActive ? 'true' : 'false');
          });
        }

        function openMenu() {
          switcher.classList.add('is-open');
          switcher.setAttribute('aria-expanded', 'true');
          trigger.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
          switcher.classList.remove('is-open');
          switcher.setAttribute('aria-expanded', 'false');
          trigger.setAttribute('aria-expanded', 'false');
        }

        function waitForTranslateSelect(callback, attempts) {
          const translateSelect = document.querySelector('.goog-te-combo');
          if (translateSelect) {
            callback(translateSelect);
            return;
          }

          if (attempts <= 0) return;

          window.setTimeout(function () {
            waitForTranslateSelect(callback, attempts - 1);
          }, 300);
        }

        function dispatchNativeChange(element) {
          try {
            const changeEvent = new Event('change', { bubbles: true });
            element.dispatchEvent(changeEvent);
          } catch (error) {
            const legacyEvent = document.createEvent('HTMLEvents');
            legacyEvent.initEvent('change', true, true);
            element.dispatchEvent(legacyEvent);
          }
        }

        function triggerTranslate(lang) {
          const targetLang = supportedLanguages.includes(lang) ? lang : 'en';

          if (targetLang === 'en') {
            clearCookie('googtrans');
          } else {
            setCookie('googtrans', '/en/' + targetLang, 30);
          }

          waitForTranslateSelect(function (translateSelect) {
            if (targetLang === 'en') {
              translateSelect.value = '';
              dispatchNativeChange(translateSelect);
              window.setTimeout(function () {
                window.location.reload();
              }, 150);
              return;
            }

            if (translateSelect.value !== targetLang) {
              translateSelect.value = targetLang;
              dispatchNativeChange(translateSelect);
            }
          }, 20);
        }

        function ensureGoogleTranslate() {
          if (window.google && window.google.translate && window.google.translate.TranslateElement) {
            if (!window.mybrandpleaseTranslateInitialized) {
              window.googleTranslateElementInit();
            }
            return;
          }

          if (document.getElementById('google-translate-script')) return;

          window.googleTranslateElementInit = function () {
            if (window.mybrandpleaseTranslateInitialized) return;
            window.mybrandpleaseTranslateInitialized = true;
            new window.google.translate.TranslateElement({
              pageLanguage: 'en',
              includedLanguages: supportedLanguages.join(','),
              autoDisplay: false,
              layout: window.google.translate.TranslateElement.InlineLayout.SIMPLE
            }, 'google_translate_element');

            window.setTimeout(function () {
              updateSelectedLanguage(getCurrentLanguage());
            }, 400);
          };

          const script = document.createElement('script');
          script.id = 'google-translate-script';
          script.src = 'https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit';
          script.async = true;
          document.body.appendChild(script);
        }

        updateSelectedLanguage(getCurrentLanguage());
        ensureGoogleTranslate();

        trigger.addEventListener('click', function (event) {
          event.preventDefault();
          event.stopPropagation();
          if (switcher.classList.contains('is-open')) {
            closeMenu();
          } else {
            openMenu();
          }
        });

        options.forEach(function (option) {
          option.addEventListener('click', function () {
            const lang = option.getAttribute('data-lang') || 'en';
            updateSelectedLanguage(lang);
            closeMenu();
            triggerTranslate(lang);
          });
        });

        document.addEventListener('click', function (event) {
          if (switcher.classList.contains('is-open') && !switcher.contains(event.target)) {
            closeMenu();
          }
        });

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && switcher.classList.contains('is-open')) {
            closeMenu();
          }
        });
      })();

      // Custom language dropdown (header-area-1)
      (function () {
        const wrap = document.getElementById('header-language-wrap');
        const trigger = document.getElementById('header-language-trigger');
        const list = document.getElementById('header-language-list');
        const label = wrap && wrap.querySelector('.header-area-1__language-label');
        const hiddenInput = document.getElementById('header-language-value');
        if (!wrap || !trigger || !list || !hiddenInput) return;

        function open() {
          wrap.classList.add('is-open');
          wrap.setAttribute('aria-expanded', 'true');
          trigger.setAttribute('aria-expanded', 'true');
        }
        function close() {
          wrap.classList.remove('is-open');
          wrap.setAttribute('aria-expanded', 'false');
          trigger.setAttribute('aria-expanded', 'false');
        }
        function toggle() {
          wrap.classList.toggle('is-open');
          const expanded = wrap.classList.contains('is-open');
          wrap.setAttribute('aria-expanded', expanded);
          trigger.setAttribute('aria-expanded', expanded);
        }

        trigger.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          toggle();
        });

        list.querySelectorAll('.header-area-1__language-option').forEach(function (option) {
          option.addEventListener('click', function (e) {
            e.preventDefault();
            const value = this.getAttribute('data-value');
            const text = this.textContent.trim();
            if (hiddenInput) hiddenInput.value = value;
            if (label) label.textContent = text;
            list.querySelectorAll('.header-area-1__language-option').forEach(function (opt) {
              opt.setAttribute('aria-selected', opt === option ? 'true' : 'false');
            });
            close();
          });
        });

        document.addEventListener('click', function (e) {
          if (wrap.classList.contains('is-open') && !wrap.contains(e.target)) {
            close();
          }
        });
      })();

      window.MybrandStore = (function () {
        const CART_KEY = 'cart';
        const WISHLIST_KEY = 'wishlist';

        function read(key) {
          try {
            const value = JSON.parse(localStorage.getItem(key) || '[]');
            if (Array.isArray(value)) return value;
            if (value && typeof value === 'object') return Object.values(value);
            return [];
          } catch (error) {
            return [];
          }
        }

        function write(key, items) {
          localStorage.setItem(key, JSON.stringify(items));
          syncBadges();
          if (key === WISHLIST_KEY) {
            syncWishlistToServer(items);
          }
          window.dispatchEvent(new CustomEvent('mybrand:store-updated', { detail: { key: key, items: items } }));
        }

        function syncWishlistToServer(items) {
          try {
            fetch('<?php echo url('api/wishlist.php'); ?>', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({
                action: 'replace',
                items: Array.isArray(items) ? items : []
              })
            }).catch(function () {});
          } catch (error) {
            // Ignore sync failures for guests/offline.
          }
        }

        function syncWishlistFromServer() {
          try {
            fetch('<?php echo url('api/wishlist.php'); ?>', {
              method: 'GET',
              headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (response) { return response.json(); })
            .then(function (data) {
              if (!data || !data.success || !data.data || !Array.isArray(data.data.items)) {
                return;
              }
              const localItems = read(WISHLIST_KEY);
              const serverItems = data.data.items;

              // Prevent empty server response from wiping locally added items.
              if (Array.isArray(localItems) && localItems.length > 0 && serverItems.length === 0) {
                syncWishlistToServer(localItems);
                syncBadges();
                window.dispatchEvent(new CustomEvent('mybrand:store-updated', { detail: { key: WISHLIST_KEY, items: localItems } }));
                return;
              }

              localStorage.setItem(WISHLIST_KEY, JSON.stringify(serverItems));
              syncBadges();
              window.dispatchEvent(new CustomEvent('mybrand:store-updated', { detail: { key: WISHLIST_KEY, items: serverItems } }));
            })
            .catch(function () {});
          } catch (error) {
            // Ignore sync failures for guests/offline.
          }
        }

        function normalizeProduct(product) {
          const parsedPrice = parseFloat(String(product.price ?? 0).replace(/[^0-9.]/g, ''));
          return {
            slug: String(product.slug || '').trim(),
            title: String(product.title || 'Product').trim(),
            price: Number.isFinite(parsedPrice) ? parsedPrice : 0,
            image: String(product.image || '').trim(),
            link: String(product.link || 'product-details.php').trim(),
            quantity: Math.max(1, parseInt(product.quantity || 1, 10) || 1)
          };
        }

        function syncBadges() {
          const wishlistCount = read(WISHLIST_KEY).length;

          document.querySelectorAll('[data-wishlist-count]').forEach(function (el) {
            el.textContent = String(wishlistCount);
            el.style.display = wishlistCount > 0 ? 'inline-block' : 'none';
          });
        }

        function addToCart(product) {
          const item = normalizeProduct(product);
          const cart = read(CART_KEY);
          const existing = cart.find(function (entry) {
            return entry.slug !== '' && entry.slug === item.slug;
          });

          if (existing) {
            existing.quantity = Math.max(1, parseInt(existing.quantity || 1, 10) || 1) + item.quantity;
          } else {
            cart.push(item);
          }

          write(CART_KEY, cart);
          return cart;
        }

        function updateCartQuantity(slug, quantity) {
          const cart = read(CART_KEY).map(function (item) {
            if (item.slug === slug) {
              item.quantity = Math.max(1, parseInt(quantity || 1, 10) || 1);
            }
            return item;
          });
          write(CART_KEY, cart);
          return cart;
        }

        function removeFromCart(slug) {
          const cart = read(CART_KEY).filter(function (item) {
            return item.slug !== slug;
          });
          write(CART_KEY, cart);
          return cart;
        }

        function toggleWishlist(product) {
          const item = normalizeProduct(product);
          const wishlist = read(WISHLIST_KEY);
          const index = wishlist.findIndex(function (entry) {
            return entry.slug !== '' && entry.slug === item.slug;
          });

          if (index >= 0) {
            wishlist.splice(index, 1);
            write(WISHLIST_KEY, wishlist);
            return false;
          }

          wishlist.push(item);
          write(WISHLIST_KEY, wishlist);
          return true;
        }

        function removeFromWishlist(slug) {
          const wishlist = read(WISHLIST_KEY).filter(function (item) {
            return item.slug !== slug;
          });
          write(WISHLIST_KEY, wishlist);
          return wishlist;
        }

        function isInWishlist(slug) {
          return read(WISHLIST_KEY).some(function (item) {
            return item.slug === slug;
          });
        }

        syncBadges();
        syncWishlistFromServer();

        return {
          getCart: function () { return read(CART_KEY); },
          getWishlist: function () { return read(WISHLIST_KEY); },
          addToCart: addToCart,
          updateCartQuantity: updateCartQuantity,
          removeFromCart: removeFromCart,
          toggleWishlist: toggleWishlist,
          removeFromWishlist: removeFromWishlist,
          isInWishlist: isInWishlist,
          syncBadges: syncBadges
        };
      })();

      // Search form submission
      const searchForm = document.querySelector('.search-form');
      if (searchForm) {
        searchForm.addEventListener('submit', function (e) {
          e.preventDefault();
          const searchInput = searchForm.querySelector('input[name="q"]');
          const searchTerm = searchInput ? searchInput.value.trim() : '';

          if (searchTerm) {
            window.location.href = `<?php echo url('shop.php'); ?>?q=${encodeURIComponent(searchTerm)}`;
          } else {
            window.location.href = '<?php echo url('shop.php'); ?>';
          }
        });
      }

      // Search wrap overlay form submission (same as header-area-2 flow)
      const searchWrapForm = document.getElementById('search-wrap-form');
      if (searchWrapForm) {
        searchWrapForm.addEventListener('submit', function (e) {
          e.preventDefault();
          const wrapInput = document.getElementById('search-wrap-input');
          const searchTerm = wrapInput ? wrapInput.value.trim() : '';
          if (searchTerm) {
            window.location.href = `<?php echo url('shop.php'); ?>?q=${encodeURIComponent(searchTerm)}`;
          } else {
            window.location.href = '<?php echo url('shop.php'); ?>';
          }
        });
      }

      document.querySelectorAll('.js-add-to-cart').forEach(function (button) {
        button.addEventListener('click', function (e) {
          e.preventDefault();
          const slug = (this.dataset.productSlug || '').trim();
          if (!slug) return;
          window.location.href = `<?php echo url('cart.php'); ?>?add=${encodeURIComponent(slug)}`;
        });
      });

      // Global AOS init (for pages that include AOS library + data-aos attributes)
      if (typeof window.AOS !== 'undefined' && typeof window.AOS.init === 'function') {
        window.AOS.init({
          once: false,
          mirror: true,
          offset: 60,
          duration: 800,
          easing: 'ease-out-cubic'
        });
      }

      // Social reels seamless marquee + muted autoplay + click-to-open lightbox
      (function () {
        const track = document.querySelector('.social-reels__track');
        if (!track) return;

        const originalCards = Array.from(track.querySelectorAll('.social-reels__card'));
        if (!originalCards.length) return;

        const rail = document.createElement('div');
        rail.className = 'social-reels__rail';
        originalCards.forEach(function (card) { rail.appendChild(card); });
        track.innerHTML = '';
        track.appendChild(rail);

        let rafId = 0;
        let paused = false;
        let interacting = false;
        let dragging = false;
        let dragMoved = false;
        let pointerDown = false;
        const speed = 0.55;
        let direction = 1;
        let x = 0;
        let dragStartX = 0;
        let dragStartOffset = 0;
        const dragThreshold = 8;
        const desktopPointerQuery = window.matchMedia('(hover: hover) and (pointer: fine)');

        function canDragReels() {
          return !desktopPointerQuery.matches;
        }

        function getMinOffset() {
          return Math.min(track.clientWidth - rail.scrollWidth, 0);
        }

        function step() {
          if (!paused && !interacting) {
            const minOffset = getMinOffset();
            x -= speed * direction;
            if (x <= minOffset) {
              x = minOffset;
              direction = -1;
            } else if (x >= 0) {
              x = 0;
              direction = 1;
            }
            rail.style.transform = 'translate3d(' + x + 'px,0,0)';
          }
          rafId = window.requestAnimationFrame(step);
        }

        function createModal() {
          const modal = document.createElement('div');
          modal.className = 'social-reels__modal';
          modal.setAttribute('aria-hidden', 'true');
          modal.innerHTML = '' +
            '<div class="social-reels__modal-backdrop" data-reel-close></div>' +
            '<button class="social-reels__modal-close" type="button" data-reel-close aria-label="Close reel video">&times;</button>' +
            '<div class="social-reels__modal-dialog" role="dialog" aria-modal="true" aria-label="Reel video player">' +
              '<div class="social-reels__modal-shell">' +
                '<div class="social-reels__modal-bar">' +
                  '<span class="social-reels__modal-pill">MBP Reels</span>' +
                  '<span class="social-reels__modal-meta">Private Label Showcase</span>' +
                '</div>' +
                '<video class="social-reels__modal-video" controls playsinline></video>' +
              '</div>' +
            '</div>';
          document.body.appendChild(modal);
          return modal;
        }

        const reelModal = createModal();
        const modalVideo = reelModal.querySelector('.social-reels__modal-video');
        const reelVideos = Array.from(track.querySelectorAll('.social-reels__video'));

        function markReelReady(video) {
          if (!video) return;
          const card = video.closest('.social-reels__card');
          if (card) {
            card.classList.add('is-ready');
          }
        }

        function pauseStripVideos() {
          reelVideos.forEach(function (video) {
            video.pause();
            video.muted = true;
            const card = video.closest('.social-reels__card');
            card && card.classList.remove('is-active');
            updateVolumeButton(card ? card.querySelector('.social-reels__volume-btn') : null, true);
          });
        }

        function resumeStripVideos() {
          reelVideos.forEach(function (video) {
            video.muted = true;
            const p = video.play();
            if (p && typeof p.catch === 'function') p.catch(function () {});
          });
        }

        function openReelModal(videoSrc) {
          if (!videoSrc || !modalVideo) return;
          paused = true;
          pauseStripVideos();
          modalVideo.pause();
          modalVideo.src = videoSrc;
          modalVideo.loop = true;
          modalVideo.playsInline = true;
          modalVideo.currentTime = 0;
          modalVideo.muted = false;
          modalVideo.volume = 1;
          reelModal.classList.add('is-open');
          reelModal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
          const p = modalVideo.play();
          if (p && typeof p.catch === 'function') p.catch(function () {});
        }

        function closeReelModal() {
          if (!modalVideo) return;
          modalVideo.pause();
          modalVideo.removeAttribute('src');
          modalVideo.load();
          reelModal.classList.remove('is-open');
          reelModal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
          paused = false;
          resumeStripVideos();
        }

        track.addEventListener('mouseenter', function () {
          paused = true;
        });
        track.addEventListener('mouseleave', function () {
          if (!reelModal.classList.contains('is-open') && !pointerDown) {
            paused = false;
          }
        });
        track.addEventListener('focusin', function () {
          paused = true;
        });
        track.addEventListener('focusout', function () {
          window.setTimeout(function () {
            const activeElement = document.activeElement;
            if (!reelModal.classList.contains('is-open') && !pointerDown && !track.contains(activeElement)) {
              paused = false;
            }
          }, 0);
        });

        track.addEventListener('pointerdown', function (event) {
          if (!canDragReels()) return;
          if (event.target.closest('.social-reels__volume-btn, .social-reels__badge--link')) return;
          pointerDown = true;
          paused = true;
          dragging = false;
          interacting = false;
          dragMoved = false;
          dragStartX = event.clientX;
          dragStartOffset = x;
        });
        track.addEventListener('pointermove', function (event) {
          if (!canDragReels() || !pointerDown) return;
          const delta = event.clientX - dragStartX;
          if (!dragging) {
            if (Math.abs(delta) < dragThreshold) return;
            dragging = true;
            interacting = true;
            dragMoved = true;
            track.setPointerCapture(event.pointerId);
          }
          x = dragStartOffset + delta;
          const minOffset = getMinOffset();
          if (x > 0) x = 0;
          if (x < minOffset) x = minOffset;
          rail.style.transform = 'translate3d(' + x + 'px,0,0)';
        });
        function endDrag(event) {
          if (event && canDragReels() && track.hasPointerCapture && track.hasPointerCapture(event.pointerId)) {
            track.releasePointerCapture(event.pointerId);
          }
          pointerDown = false;
          dragging = false;
          interacting = false;
          if (!reelModal.classList.contains('is-open') && !track.matches(':hover')) {
            paused = false;
          }
          window.setTimeout(function () {
            dragMoved = false;
          }, 0);
        }
        track.addEventListener('pointerup', endDrag);
        track.addEventListener('pointercancel', endDrag);
        track.addEventListener('pointerleave', function (event) {
          if (pointerDown) endDrag(event);
        });

        function updateVolumeButton(button, isMuted) {
          if (!button) return;
          button.classList.toggle('is-unmuted', !isMuted);
          button.setAttribute('aria-pressed', isMuted ? 'false' : 'true');
          button.setAttribute('aria-label', isMuted ? 'Unmute reel' : 'Mute reel');
          button.innerHTML = '<i class="fa-solid ' + (isMuted ? 'fa-volume-xmark' : 'fa-volume-high') + '" aria-hidden="true"></i>';
        }

        function muteOtherReels(activeVideo) {
          track.querySelectorAll('.social-reels__video').forEach(function (video) {
            if (video === activeVideo) return;
            video.muted = true;
            const card = video.closest('.social-reels__card');
            card && card.classList.remove('is-active');
            updateVolumeButton(card ? card.querySelector('.social-reels__volume-btn') : null, true);
          });
        }

        track.querySelectorAll('.social-reels__card').forEach(function (card) {
            const video = card.querySelector('video');
            const badgeLink = card.querySelector('.social-reels__badge--link');
            const volumeButton = card.querySelector('.social-reels__volume-btn');
            if (video) {
              if (video.readyState >= 2) {
                markReelReady(video);
              } else {
                video.addEventListener('loadeddata', function handleLoadedData() {
                  markReelReady(video);
                  video.removeEventListener('loadeddata', handleLoadedData);
                });
                video.addEventListener('canplay', function handleCanPlay() {
                  markReelReady(video);
                  video.removeEventListener('canplay', handleCanPlay);
                });
              }
              video.autoplay = true;
              video.muted = true;
              video.loop = true;
              video.playsInline = true;
              video.preload = 'metadata';
              const p = video.play();
              if (p && typeof p.catch === 'function') p.catch(function () {});
              updateVolumeButton(volumeButton, true);
            }

            if (volumeButton && video) {
              volumeButton.addEventListener('pointerdown', function (event) {
                event.stopPropagation();
              });
              volumeButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const shouldUnmute = video.muted;
                if (shouldUnmute) {
                  muteOtherReels(video);
                  card.classList.add('is-active');
                }
                video.muted = !shouldUnmute;
                card.classList.toggle('is-active', shouldUnmute);
                updateVolumeButton(volumeButton, video.muted);
                const p = video.play();
                if (p && typeof p.catch === 'function') p.catch(function () {});
              });
            }

          if (badgeLink) {
            badgeLink.addEventListener('click', function (event) {
              event.stopPropagation();
            });
          }

          function openFromCard(event) {
            if (event.target.closest('.social-reels__badge--link')) return;
            if (event.target.closest('.social-reels__volume-btn')) return;
            if (dragging || dragMoved) return;
            const videoSrc = card.getAttribute('data-video-src') || '';
            if (videoSrc !== '') {
              event.preventDefault();
              event.stopPropagation();
              openReelModal(videoSrc);
            }
          }

          card.addEventListener('click', openFromCard);
        });

        reelModal.querySelectorAll('[data-reel-close]').forEach(function (el) {
          el.addEventListener('click', closeReelModal);
        });
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && reelModal.classList.contains('is-open')) {
            closeReelModal();
          }
        });

        rafId = window.requestAnimationFrame(step);
        window.addEventListener('beforeunload', function () {
          if (rafId) window.cancelAnimationFrame(rafId);
        });
      })();

      // Home page section animations (GSAP)
      (function () {
        if (typeof window.gsap === 'undefined') return;
        const hasScrollTrigger = typeof window.ScrollTrigger !== 'undefined';
        if (hasScrollTrigger) {
          window.gsap.registerPlugin(window.ScrollTrigger);
        }

        // Why Private Label Business cards
        const whySection = document.querySelector('.js-why-business');
        const whyCards = document.querySelectorAll('.js-why-business .js-why-card');
        if (whySection && whyCards.length) {
          if (hasScrollTrigger) {
            window.ScrollTrigger.create({
              trigger: whySection,
              start: 'top 76%',
              once: true,
              onEnter: function () {
                window.gsap.fromTo(whyCards, {
                  y: 48,
                  opacity: 0
                }, {
                  y: 0,
                  opacity: 1,
                  duration: 0.8,
                  ease: 'power3.out',
                  stagger: 0.16
                });
              }
            });
          } else {
            window.gsap.fromTo(whyCards, {
              y: 48,
              opacity: 0
            }, {
              y: 0,
              opacity: 1,
              duration: 0.8,
              ease: 'power3.out',
              stagger: 0.16
            });
          }
        }

        // Milestones section entry + count up animation
        const milestoneSection = document.querySelector('.milestone-highlight');
        const milestoneCards = document.querySelectorAll('.milestone-highlight .milestone-card');
        const milestoneNums = document.querySelectorAll('.js-milestone-number');

        function resetMilestoneCount() {
          milestoneNums.forEach(function (el) {
            window.gsap.killTweensOf(el);
            el.textContent = '0+';
          });
        }

        function runMilestoneCount() {
          milestoneNums.forEach(function (el) {
            const target = parseInt(el.dataset.target || '0', 10);
            if (!Number.isFinite(target) || target <= 0) return;
            const state = { value: 0 };
            window.gsap.killTweensOf(state);
            el.textContent = '0+';
            window.gsap.to(state, {
              value: target,
              duration: 1.8,
              ease: 'power2.out',
              onUpdate: function () {
                el.textContent = Math.floor(state.value).toString() + '+';
              },
              onComplete: function () {
                el.textContent = target.toString() + '+';
              }
            });
          });
        }

        if (milestoneSection && milestoneCards.length) {
          if (hasScrollTrigger) {
            window.ScrollTrigger.create({
              trigger: milestoneSection,
              start: 'top 76%',
              onEnter: function () {
                window.gsap.fromTo(milestoneCards, {
                  y: 44,
                  opacity: 0
                }, {
                  y: 0,
                  opacity: 1,
                  duration: 0.9,
                  ease: 'power3.out',
                  stagger: 0.14
                });
                runMilestoneCount();
              },
              onEnterBack: function () {
                window.gsap.fromTo(milestoneCards, {
                  y: 22,
                  opacity: 0.75
                }, {
                  y: 0,
                  opacity: 1,
                  duration: 0.7,
                  ease: 'power2.out',
                  stagger: 0.08
                });
                runMilestoneCount();
              },
              onLeave: function () {
                resetMilestoneCount();
              },
              onLeaveBack: function () {
                resetMilestoneCount();
              }
            });
          } else {
            window.gsap.fromTo(milestoneCards, {
              y: 44,
              opacity: 0
            }, {
              y: 0,
              opacity: 1,
              duration: 0.9,
              ease: 'power3.out',
              stagger: 0.14
            });
            runMilestoneCount();
          }
        }

        // Global Presence markers: pulse + marker-entry animation
        const globalSection = document.querySelector('.js-global-presence');
        if (globalSection) {
          const markers = globalSection.querySelectorAll('.map-marker');
          const dots = globalSection.querySelectorAll('.map-marker__dot');
          if (markers.length) {
            window.gsap.from(markers, {
              scale: 0.6,
              opacity: 0,
              duration: 0.5,
              ease: 'back.out(1.8)',
              stagger: 0.08,
              scrollTrigger: hasScrollTrigger ? {
                trigger: globalSection,
                start: 'top 78%',
                once: true
              } : undefined
            });
          }
          if (dots.length) {
            window.gsap.to(dots, {
              scale: 1.22,
              duration: 1.1,
              repeat: -1,
              yoyo: true,
              ease: 'sine.inOut',
              stagger: 0.12
            });
          }
        }
      })();

      // CTA Modal Functionality
      (function () {
        const modal = document.getElementById('enquiry-modal');
        const openTriggers = document.querySelectorAll('#open-enquiry-btn, [data-open-enquiry]');
        
        if (!modal) return;

        function restoreNativeModalSelects() {
          if (typeof jQuery === 'undefined' || !jQuery.fn || !jQuery.fn.niceSelect) return;
          const $modalSelects = jQuery(modal).find('select');
          if ($modalSelects.length === 0) return;
          $modalSelects.each(function () {
            const $select = jQuery(this);
            if ($select.next('.nice-select').length > 0) {
              try {
                $select.niceSelect('destroy');
              } catch (error) {
                $select.next('.nice-select').remove();
                $select.css('display', '');
              }
            }
          });
        }

        // Keep modal selects as native controls for reliable open/select behavior.
        restoreNativeModalSelects();

        function openModal() {
          restoreNativeModalSelects();
          modal.classList.add('is-open');
          modal.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
          
          // Focus first input for better UX
          const firstInput = modal.querySelector('input, textarea, select');
          if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
          }
        }

        function closeModal() {
          modal.classList.remove('is-open');
          modal.setAttribute('aria-hidden', 'true');
          document.body.style.overflow = '';
        }

        window.mybrandpleaseOpenEnquiryModal = openModal;

        // Open modal when CTA buttons are clicked
        openTriggers.forEach(function (trigger) {
          trigger.addEventListener('click', function (event) {
            event.preventDefault();
            openModal();
          });
        });

        // Close modal when backdrop or close button is clicked
        modal.querySelectorAll('[data-enquiry-close]').forEach(function (trigger) {
          trigger.addEventListener('click', closeModal);
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
          }
        });

        // Close modal when clicking outside the dialog (on backdrop)
        modal.addEventListener('click', function (event) {
          if (event.target === modal || event.target.classList.contains('enquiry-modal__backdrop')) {
            closeModal();
          }
        });

        // Form submission handling
        const form = modal.querySelector('.enquiry-modal__form');
        if (form) {
          form.addEventListener('submit', function (e) {
            // Form will submit normally to contact.php
            // You can add client-side validation here if needed
            console.log('Form submitted');
          });
        }
      })();

      // Site visit popup: once dismissed, do not show it again for this browser.
      (function () {
        const popup = document.getElementById('site-visit-popup');
        if (!popup) return;

        const firstDelayMs = 15000;
        const popupDismissedKey = 'mybrandplease_site_visit_popup_dismissed';
        let timerId = 0;
        let hasShownPopup = false;

        function isPopupDismissed() {
          try {
            return window.localStorage.getItem(popupDismissedKey) === '1';
          } catch (error) {
            return false;
          }
        }

        function markPopupDismissed() {
          try {
            window.localStorage.setItem(popupDismissedKey, '1');
          } catch (error) {
            // Ignore storage failures and continue gracefully.
          }
        }

        function schedulePopup(delay) {
          if (hasShownPopup || isPopupDismissed()) return;
          if (timerId) window.clearTimeout(timerId);
          timerId = window.setTimeout(openPopup, delay);
        }

        function openPopup() {
          if (hasShownPopup || isPopupDismissed()) return;
          if (popup.classList.contains('is-open')) return;
          const enquiryModal = document.getElementById('enquiry-modal');
          if (enquiryModal && enquiryModal.classList.contains('is-open')) {
            schedulePopup(firstDelayMs);
            return;
          }
          hasShownPopup = true;
          timerId = 0;
          popup.classList.add('is-open');
          popup.setAttribute('aria-hidden', 'false');
          document.body.style.overflow = 'hidden';
        }

        function closePopup(restoreScroll, persistDismissal) {
          popup.classList.remove('is-open');
          popup.setAttribute('aria-hidden', 'true');
          if (persistDismissal) {
            markPopupDismissed();
          }
          if (restoreScroll !== false) {
            document.body.style.overflow = '';
          }
        }

        if (!isPopupDismissed()) {
          schedulePopup(firstDelayMs);
        }

        popup.querySelectorAll('[data-site-popup-close]').forEach(function (trigger) {
          trigger.addEventListener('click', function () {
            closePopup(true, true);
          });
        });

        const enquiryButton = document.getElementById('site-popup-enquiry-btn');
        if (enquiryButton) {
          enquiryButton.addEventListener('click', function () {
            closePopup(true, true);
            window.location.href = '<?php echo url('meeting-schedule.php'); ?>';
          });
        }

        document.addEventListener('keydown', function (event) {
          if (event.key === 'Escape' && popup.classList.contains('is-open')) {
            closePopup(true, true);
          }
        });
      })();

      // WhatsApp chat button follows the same visibility as the back-to-top progress button.
      (function () {
        const button = document.getElementById('whatsapp-chat-button');
        const progressWrap = document.querySelector('.progress-wrap');
        if (!button) return;

        function syncWithProgress() {
          const isActive = progressWrap
            ? progressWrap.classList.contains('active-progress')
            : (window.scrollY || window.pageYOffset || 0) > 50;
          button.classList.toggle('active-progress', isActive);
        }

        syncWithProgress();

        if (progressWrap && window.MutationObserver) {
          const observer = new MutationObserver(syncWithProgress);
          observer.observe(progressWrap, { attributes: true, attributeFilter: ['class'] });
        } else {
          window.addEventListener('scroll', syncWithProgress, { passive: true });
        }
      })();

    });
  </script>
</body>

</html>
