<?php
require_once __DIR__ . '/url.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/preview.php';
require_once __DIR__ . '/draft.php';

if (!function_exists('our_certificates_fallback_items')) {
    function our_certificates_fallback_items(): array
    {
        return our_certificates_folder_items();
    }
}

if (!function_exists('our_certificates_title_from_filename')) {
    function our_certificates_title_from_filename(string $filename): string
    {
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $title = preg_replace('/^\s*\d+\)\s*/', '', $title) ?? $title;
        $title = preg_replace('/[_-]+/', ' ', $title) ?? $title;
        $title = preg_replace('/\s+/', ' ', $title) ?? $title;
        $title = preg_replace('/\s+copy$/i', '', $title) ?? $title;
        $title = preg_replace('/\s+Page\s+\d+$/i', '', $title) ?? $title;
        $title = trim($title, " .\t\n\r\0\x0B");

        $replacements = [
            'DIPP227867 NIMISHA IMPEX WORLDWIDE PRIVATE LIMITED RECOGNITION 4149808632514872724' => 'DPIIT Recognition - Nimisha Impex Worldwide Private Limited',
            'MemCertificate EPB' => 'EPB Membership Certificate',
            'Certificate UPEPC' => 'UPEPC Certificate',
            'NIWPL Signed IEC Code' => 'NIWPL IEC Code',
            'PAN NIWPLsigned' => 'NIWPL PAN',
            'TAN NIWPLsigned' => 'NIWPL TAN',
            'NIMISHA IMPEX INC EIN No 41 4152316' => 'Nimisha Impex Inc EIN',
            'NIMISHA IMPEX US FDA Certificate MoCRA' => 'Nimisha Impex US FDA Certificate - MoCRA',
        ];

        return $replacements[$title] ?? $title;
    }
}

if (!function_exists('our_certificates_category_for_title')) {
    function our_certificates_category_for_title(string $title): string
    {
        $lower = strtolower($title);

        if (preg_match('/iso|gmp|haccp|eurofins|fda|mocra|cpnp/', $lower)) {
            return 'quality-standards';
        }

        if (preg_match('/gst|pan|tan|ein|incorporation|articles|udyam|dpiit|dipp|lei/', $lower)) {
            return 'business-registration';
        }

        return 'regulatory';
    }
}

if (!function_exists('our_certificates_folder_items')) {
    function our_certificates_folder_items(): array
    {
        $directory = dirname(__DIR__) . '/assets/imgs/our-certificates';
        $webDirectory = 'assets/imgs/our-certificates';
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (!is_dir($directory)) {
            return [];
        }

        $files = array_values(array_filter(scandir($directory) ?: [], static function (string $file) use ($directory, $allowedExtensions): bool {
            if ($file === '.' || $file === '..') {
                return false;
            }

            if (preg_match('/\s+-\s+page\s+\d+\.(jpe?g|png|webp)$/i', $file)) {
                return false;
            }

            $path = $directory . '/' . $file;
            if (!is_file($path)) {
                return false;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($extension, $allowedExtensions, true);
        }));

        usort($files, static function (string $a, string $b): int {
            $aHasSeries = preg_match('/^\s*(\d+)\)/', $a, $aMatch);
            $bHasSeries = preg_match('/^\s*(\d+)\)/', $b, $bMatch);

            if ($aHasSeries && $bHasSeries) {
                return ((int) $aMatch[1]) <=> ((int) $bMatch[1]);
            }

            if ($aHasSeries) {
                return -1;
            }

            if ($bHasSeries) {
                return 1;
            }

            return strnatcasecmp($a, $b);
        });

        $certificates = [];
        foreach ($files as $file) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $title = our_certificates_title_from_filename($file);
            $path = $webDirectory . '/' . rawurlencode($file);
            $previewFilename = pathinfo($file, PATHINFO_FILENAME) . ' - page 1.jpg';
            $previewPath = $directory . '/' . $previewFilename;
            $previewUrl = is_file($previewPath)
                ? url($webDirectory . '/' . rawurlencode($previewFilename))
                : url($path);

            $certificates[] = [
                'title' => $title,
                'image' => $extension === 'pdf' ? $previewUrl : url($path),
                'file' => url($path),
                'category' => our_certificates_category_for_title($title),
                'type' => $extension === 'pdf' ? 'pdf' : 'image',
                'has_preview' => $extension !== 'pdf' || is_file($previewPath),
            ];
        }

        return $certificates;
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
            $activeClause = preview_mode_include_drafts() ? '' : ' WHERE is_active = 1';
            $rows = db_fetch_all(
                $pdo,
                'SELECT id, title, image_path, file_path, category, file_type FROM certificates' . $activeClause . ' ORDER BY sort_order ASC, id ASC'
            );
        } catch (Throwable $e) {
            $rows = [];
        }

        if (!$rows) {
            return our_certificates_fallback_items();
        }

        if (preview_mode_include_drafts()) {
            $rows = draft_merge_rows($rows, 'certificate');
        }

        $certificates = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $imagePath = trim((string) ($row['image_path'] ?? ''));
            if ($title === '' || $imagePath === '') {
                continue;
            }

            $filePath = trim((string) ($row['file_path'] ?? ''));
            if ($filePath === '') {
                $filePath = $imagePath;
            }
            $fileType = strtolower(trim((string) ($row['file_type'] ?? 'image')));
            if (!in_array($fileType, ['image', 'pdf'], true)) {
                $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'pdf' ? 'pdf' : 'image';
            }

            $certificates[] = [
                'title' => $title,
                'image' => url($imagePath),
                'file' => url($filePath),
                'category' => (string) ($row['category'] ?? 'quality-standards'),
                'type' => $fileType,
                'has_preview' => $fileType !== 'pdf' || $imagePath !== $filePath,
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

        <section class="certificates-page section-spacing-120 rr-ov-hidden" id="certificatesPage">
          <div class="container rr-container-1350">
            <div class="certificates-page__intro text-center">
              <span class="certificates-page__eyebrow">Our Certifications</span>
              <p class="certificates-page__lead">We maintain the highest standards of quality and compliance through our internationally recognized certifications and accreditations.</p>
            </div>

            <div class="row g-4 certificates-grid" id="certificates-grid">
              <?php foreach ($certificates as $certificateIndex => $certificate): ?>
                <div class="col-xl-3 col-lg-4 col-md-6 certificate-item" style="--certificate-delay: <?php echo (int) ($certificateIndex % 12); ?>;">
                  <article class="certificate-card">
                    <?php
                      $certificateFile = (string) ($certificate['file'] ?? $certificate['image']);
                      $certificateType = (string) ($certificate['type'] ?? 'image');
                      $certificateTitle = (string) $certificate['title'];
                      $hasPreview = (bool) ($certificate['has_preview'] ?? $certificateType !== 'pdf');
                    ?>
                    <a
                      class="certificate-card__media"
                      href="<?php echo htmlspecialchars($certificateFile, ENT_QUOTES, 'UTF-8'); ?>"
                      target="_blank"
                      rel="noopener noreferrer"
                      aria-label="View <?php echo htmlspecialchars($certificateTitle, ENT_QUOTES, 'UTF-8'); ?>">
                      <?php if ($certificateType === 'pdf' && !$hasPreview): ?>
                        <span class="certificate-card__pdf" aria-hidden="true">
                          <i class="fa-regular fa-file-pdf"></i>
                          <span>PDF</span>
                        </span>
                      <?php else: ?>
                        <img src="<?php echo htmlspecialchars((string) $certificate['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($certificateTitle, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($certificateType === 'pdf'): ?>
                          <span class="certificate-card__type-badge">PDF</span>
                        <?php endif; ?>
                      <?php endif; ?>
                    </a>
                    <div class="certificate-card__body">
                      <h3 class="certificate-card__title"><?php echo htmlspecialchars($certificateTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                    </div>
                  </article>
                </div>
              <?php endforeach; ?>
            </div>

            <p class="certificates-page__note text-center">&copy; 2023 Nimisha Impex Inc. All certifications are valid and regularly audited.</p>
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
          .certificate-card {
            position: relative;
            background: #fff;
            border: 1px solid #f0e4eb;
            border-radius: 22px;
            overflow: hidden;
            height: 100%;
            box-shadow: 0 18px 40px rgba(80, 45, 69, 0.08);
            opacity: 0;
            transform: translateY(24px) scale(0.97);
            transition: transform 0.32s ease, box-shadow 0.32s ease, border-color 0.32s ease;
            will-change: transform, opacity;
          }
          .certificates-page.is-loaded .certificate-card {
            animation: certificateCardIn 0.68s cubic-bezier(.22, 1, .36, 1) forwards;
            animation-delay: calc(var(--certificate-delay) * 70ms);
          }
          .certificate-card::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(115deg, transparent 0 35%, rgba(255, 255, 255, 0.65) 48%, transparent 62% 100%);
            opacity: 0;
            transform: translateX(-58%);
            pointer-events: none;
          }
          .certificate-card:hover {
            transform: translateY(-8px) scale(1.012);
            border-color: rgba(238, 45, 122, 0.22);
            box-shadow: 0 24px 44px rgba(80, 45, 69, 0.14);
          }
          .certificate-card:hover::after {
            opacity: 1;
            animation: certificateCardShine 1s ease forwards;
          }
          .certificate-card__media {
            position: relative;
            display: block;
            background: #fff9fc;
            padding: 14px;
            overflow: hidden;
          }
          .certificate-card__media img {
            width: 100%;
            aspect-ratio: 4 / 5;
            object-fit: contain;
            border-radius: 16px;
            background: #fff;
            transform: scale(1);
            transition: transform 0.45s cubic-bezier(.22, 1, .36, 1), filter 0.35s ease;
          }
          .certificate-card:hover .certificate-card__media img {
            transform: scale(1.035);
            filter: saturate(1.04) contrast(1.02);
          }
          .certificate-card__pdf {
            width: 100%;
            aspect-ratio: 4 / 5;
            display: grid;
            place-items: center;
            align-content: center;
            gap: 12px;
            border: 1px solid #f4d5e2;
            border-radius: 16px;
            background: linear-gradient(145deg, #fff, #fff1f7);
            color: #ee2d7a;
          }
          .certificate-card__pdf i {
            font-size: 56px;
            line-height: 1;
          }
          .certificate-card__pdf span {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.08em;
          }
          .certificate-card__type-badge {
            position: absolute;
            top: 24px;
            right: 24px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 44px;
            min-height: 28px;
            padding: 6px 10px;
            border-radius: 999px;
            background: #ee2d7a;
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: 0.04em;
            box-shadow: 0 10px 22px rgba(238, 45, 122, 0.24);
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
          .certificates-page__note {
            color: #6b6472;
          }
          .certificates-page__note {
            margin-top: 34px;
            margin-bottom: 0;
          }
          @keyframes certificateCardIn {
            from {
              opacity: 0;
              transform: translateY(24px) scale(0.97);
            }

            to {
              opacity: 1;
              transform: translateY(0) scale(1);
            }
          }
          @keyframes certificateCardShine {
            from {
              transform: translateX(-58%);
            }

            to {
              transform: translateX(58%);
            }
          }
          @media (prefers-reduced-motion: reduce) {
            .certificate-card {
              opacity: 1;
              transform: none;
              animation: none;
              transition: none;
            }

            .certificate-card:hover,
            .certificate-card:hover .certificate-card__media img {
              transform: none;
            }

            .certificate-card:hover::after {
              animation: none;
              opacity: 0;
            }
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
            const page = document.getElementById('certificatesPage');
            if (!page) return;

            window.requestAnimationFrame(function () {
              page.classList.add('is-loaded');
            });
          })();
        </script>

        <?php
        include __DIR__ . '/footer.php';
    }
}
