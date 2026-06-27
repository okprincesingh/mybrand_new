<?php
if (!function_exists('our_certificates_fallback_items')) {
    function our_certificates_fallback_items(): array
    {
        return [
            [
                'title' => 'FDA Compliant Facility',
                'image' => url('assets/imgs/about/FDA-scaled-500x502.jpg'),
                'category' => 'regulatory',
            ],
            [
                'title' => 'European Standards',
                'image' => url('assets/imgs/about/EU.jpg'),
                'category' => 'regulatory',
            ],
            [
                'title' => 'GMP Certified',
                'image' => url('assets/imgs/about/GMP1-500x500.jpg'),
                'category' => 'quality-standards',
            ],
            [
                'title' => 'ISO 9001 Certificate',
                'image' => url('assets/imgs/about/9001.jpg'),
                'category' => 'quality-standards',
            ],
            [
                'title' => 'HACCP Certificate',
                'image' => url('assets/imgs/about/HACCP1-1-500x268.jpg'),
                'category' => 'quality-standards',
            ],
            [
                'title' => 'FIEO Certificate',
                'image' => url('assets/imgs/about/FIEO-500x214.jpg'),
                'category' => 'business-registration',
            ],
            [
                'title' => 'Professional Beauty Association',
                'image' => url('assets/imgs/about/PBA-500x189.jpg'),
                'category' => 'business-registration',
            ],
        ];
    }
}

if (!function_exists('our_certificates_get_items')) {
    function our_certificates_get_items(): array
    {
        $pdo = db();
        if (!$pdo) {
            return our_certificates_fallback_items();
        }

        try {
            $rows = db_fetch_all(
                $pdo,
                'SELECT title, image_path, category FROM certificates WHERE is_active = 1 ORDER BY sort_order ASC, id ASC'
            );
        } catch (Throwable $e) {
            $rows = [];
        }

        if (!$rows) {
            return our_certificates_fallback_items();
        }

        $certificates = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $imagePath = trim((string) ($row['image_path'] ?? ''));
            if ($title === '' || $imagePath === '') {
                continue;
            }

            $certificates[] = [
                'title' => $title,
                'image' => url($imagePath),
                'category' => (string) ($row['category'] ?? 'quality-standards'),
            ];
        }

        return $certificates ?: our_certificates_fallback_items();
    }
}

if (!function_exists('render_our_certificates_page')) {
    function render_our_certificates_page(): void
    {
        $certificates = our_certificates_get_items();

        $meta = [
            'title' => 'Our Certificates - Private Label Skin Care & Hair Care Product Manufacturer | Build Your Brand With a Customized Line of Natural and Organic Products - My Brand Please',
            'description' => 'Our Certifications & Accreditations',
            'canonical' => 'our-certificates',
        ];

        include __DIR__ . '/head.php';
        include __DIR__ . '/header.php';
        ?>
        <div class="breadcumb">
          <div class="container rr-container-1895">
            <div class="breadcumb-wrapper section-spacing-120 fix" data-bg-src="assets/imgs/breadcumbBg.jpg">
              <div class="breadcumb-wrapper__title">Our Certificates</div>
              <ul class="breadcumb-wrapper__items">
                <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-house"></i></li>
                <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
                <li class="breadcumb-wrapper__items-list"><a href="<?php echo htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="breadcumb-wrapper__items-list-title">Home</a></li>
                <li class="breadcumb-wrapper__items-list"><i class="fa-regular fa-chevron-right"></i></li>
                <li class="breadcumb-wrapper__items-list"><span class="breadcumb-wrapper__items-list-title2">Our Certificates</span></li>
              </ul>
            </div>
          </div>
        </div>

        <section class="certificates-page section-spacing-120 rr-ov-hidden">
          <div class="container rr-container-1350">
            <div class="certificates-page__intro text-center">
              <span class="certificates-page__eyebrow">Our Certifications &amp; Accreditations</span>
              <h1 class="certificates-page__title">Our Certifications &amp; Accreditations</h1>
              <p class="certificates-page__lead">We maintain the highest standards of quality and compliance through our internationally recognized certifications and accreditations.</p>
            </div>

            <div class="certificates-filter" role="tablist" aria-label="Certificate categories">
              <button class="certificates-filter__btn is-active" type="button" data-filter="all">All Certificates</button>
              <button class="certificates-filter__btn" type="button" data-filter="quality-standards">Quality Standards</button>
              <button class="certificates-filter__btn" type="button" data-filter="regulatory">Regulatory</button>
              <button class="certificates-filter__btn" type="button" data-filter="business-registration">Business Registration</button>
            </div>

            <div class="row g-4 certificates-grid" id="certificates-grid">
              <?php foreach ($certificates as $certificate): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 certificate-item" data-category="<?php echo htmlspecialchars((string) $certificate['category'], ENT_QUOTES, 'UTF-8'); ?>">
                  <article class="certificate-card">
                    <a
                      class="certificate-card__media"
                      href="<?php echo htmlspecialchars((string) $certificate['image'], ENT_QUOTES, 'UTF-8'); ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      aria-label="View <?php echo htmlspecialchars((string) $certificate['title'], ENT_QUOTES, 'UTF-8'); ?>">
                      <img src="<?php echo htmlspecialchars((string) $certificate['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) $certificate['title'], ENT_QUOTES, 'UTF-8'); ?>">
                    </a>
                    <div class="certificate-card__body">
                      <h3 class="certificate-card__title"><?php echo htmlspecialchars((string) $certificate['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                  </article>
                </div>
              <?php endforeach; ?>
            </div>

            <div class="certificates-empty" id="certificates-empty" hidden>
              <h3>No certificates found</h3>
              <p>Try selecting a different category</p>
            </div>

            <p class="certificates-page__note text-center">© 2023 Nimisha Impex Inc. All certifications are valid and regularly audited.</p>
          </div>
        </section>

        <style>
          .certificates-page__intro {
            max-width: 780px;
            margin: 0 auto 36px;
          }
          .certificates-page__eyebrow {
            display: inline-block;
            color: #ee2d7a;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-bottom: 12px;
          }
          .certificates-page__title {
            margin-bottom: 14px;
          }
          .certificates-page__lead {
            color: #5a5a66;
            font-size: 18px;
            line-height: 1.8;
          }
          .certificates-filter {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            margin-bottom: 34px;
          }
          .certificates-filter__btn {
            border: 1px solid #ebd2dc;
            background: #fff;
            color: #433848;
            border-radius: 999px;
            padding: 11px 18px;
            font-weight: 600;
            transition: all 0.25s ease;
          }
          .certificates-filter__btn.is-active,
          .certificates-filter__btn:hover {
            background: #ee2d7a;
            border-color: #ee2d7a;
            color: #fff;
          }
          .certificate-card {
            background: #fff;
            border: 1px solid #f0e4eb;
            border-radius: 22px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 18px 40px rgba(80, 45, 69, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
          }
          .certificate-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 44px rgba(80, 45, 69, 0.14);
          }
          .certificate-card__media {
            display: block;
            background: #fff9fc;
            padding: 14px;
          }
          .certificate-card__media img {
            width: 100%;
            aspect-ratio: 4 / 5;
            object-fit: contain;
            border-radius: 16px;
            background: #fff;
          }
          .certificate-card__body {
            padding: 18px 18px 24px;
            text-align: center;
          }
          .certificate-card__title {
            font-size: 20px;
            line-height: 1.45;
            margin: 0;
          }
          .certificates-empty {
            text-align: center;
            padding: 42px 20px 10px;
          }
          .certificates-empty h3 {
            margin-bottom: 8px;
          }
          .certificates-empty p,
          .certificates-page__note {
            color: #6b6472;
          }
          .certificates-page__note {
            margin-top: 34px;
            margin-bottom: 0;
          }
          @media (max-width: 767px) {
            .certificates-page__lead {
              font-size: 16px;
            }
            .certificate-card__title {
              font-size: 18px;
            }
          }
        </style>

        <script>
          (function () {
            const buttons = Array.from(document.querySelectorAll('.certificates-filter__btn'));
            const items = Array.from(document.querySelectorAll('.certificate-item'));
            const emptyState = document.getElementById('certificates-empty');
            if (!buttons.length || !items.length || !emptyState) return;

            function applyFilter(filter) {
              let visibleCount = 0;
              items.forEach(function (item) {
                const matches = filter === 'all' || item.getAttribute('data-category') === filter;
                item.hidden = !matches;
                if (matches) visibleCount += 1;
              });
              emptyState.hidden = visibleCount !== 0;
            }

            buttons.forEach(function (button) {
              button.addEventListener('click', function () {
                buttons.forEach(function (btn) { btn.classList.remove('is-active'); });
                button.classList.add('is-active');
                applyFilter(button.getAttribute('data-filter') || 'all');
              });
            });

            applyFilter('all');
          })();
        </script>
        <?php
        include __DIR__ . '/footer.php';
    }
}
