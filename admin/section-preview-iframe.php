<?php
/**
 * Section Preview Iframe
 * ----------------------
 * Renders a single homepage section in isolation, with the full site CSS/JS
 * context so the preview is pixel-perfect.
 *
 * Query params:
 *   - content_type : e.g. home_hero_video, home_slide, home_testimonial, home_office, home_instagram_reel
 *   - entity_id    : the record id (0 for new/unsaved records)
 *
 * The page always enables preview mode (session flag) so draft_merge_row()
 * picks up the latest draft overrides. Output is noindex,nofollow.
 *
 * Admin auth required. Visitors never see this page.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cms.php';
require_once __DIR__ . '/../includes/cms_homepage_sections.php';
require_once __DIR__ . '/../includes/catalog.php';
require_once __DIR__ . '/../includes/preview.php';
require_once __DIR__ . '/../includes/draft.php';
require_once __DIR__ . '/../includes/url.php';
require_once __DIR__ . '/../includes/section-render.php';

// Admin auth
if (!admin_current()) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

// Force preview mode for THIS request only so drafts merge into CMS reads
// without permanently flipping the admin topbar "Preview Mode" toggle.
preview_mode_force_request(true);

header('X-Robots-Tag: noindex, nofollow');

$contentType = (string) ($_GET['content_type'] ?? '');
$entityId = (int) ($_GET['entity_id'] ?? 0);
$cacheBust = (string) ($_GET['_r'] ?? '');

if ($contentType === '') {
    http_response_code(400);
    echo 'Missing content_type';
    exit;
}

// Resolve the merged data for this section type.
$sectionHtml = '';
switch ($contentType) {
    case 'home_hero_video':
        $videos = cms_get_home_hero_videos();
        $video = $videos[0] ?? null;
        if (!$video) {
            $video = [
                'desktop_video_url' => 'https://jaikvik.in/lab/mybrand_video/mybrandvideo',
                'desktop_light_video_url' => 'https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo',
                'mobile_video_url' => 'https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo',
                'poster_image' => '',
            ];
        }
        $sectionHtml = section_render_hero_video($video);
        break;

    case 'home_slide':
        $slides = cms_get_home_slides();
        $sectionHtml = section_render_slider($slides);
        break;

    case 'home_testimonial':
        $testimonials = cms_get_home_testimonials();
        $sectionHtml = section_render_testimonials($testimonials);
        break;

    case 'home_office':
        $offices = cms_get_home_offices();
        $sectionHtml = section_render_offices($offices);
        break;

    case 'home_instagram_reel':
        $reels = cms_get_home_instagram_reels();
        $sectionHtml = section_render_instagram($reels);
        break;

    case 'home_working_process':
        $steps = cms_get_home_working_process();
        $sectionHtml = section_render_working_process($steps);
        break;

    case 'home_working_process_content':
        $workingProcessContent = cms_get_home_working_process_content();
        $steps = cms_get_home_working_process();
        $sectionHtml = section_render_working_process_content_preview($workingProcessContent, $steps);
        break;

    case 'home_brand_builder':
        $brandBuilder = cms_get_home_brand_builder();
        $items = cms_get_home_brand_builder_items();
        $sectionHtml = section_render_brand_builder($brandBuilder, $items);
        break;

    case 'home_getting_started':
        $steps = cms_get_home_getting_started();
        $sectionHtml = section_render_getting_started($steps);
        break;

    case 'home_marquee_strip':
        $strip = cms_get_home_marquee_strip('working_process_services');
        $sectionHtml = section_render_marquee_strip($strip);
        break;

    case 'home_partner_logo':
        $logos = cms_get_home_partner_logos();
        $sectionHtml = section_render_partner_logos($logos);
        break;

    case 'home_certification_logo':
        $logos = cms_get_home_certification_logos();
        $sectionHtml = section_render_certification_logos($logos);
        break;

    default:
        $sectionHtml = '<div class="alert alert-warning m-4">Unknown section type: ' . htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8') . '</div>';
}

// Build the list of frontend stylesheets (same as includes/head.php uses).
$styles = [
    'assets/vandor/bootstrap/bootstrap.min.css',
    'assets/vandor/fontawesome/fontawesome-pro.min.css',
    'assets/vandor/swiper/swiper-bundle.min.css',
    'assets/vandor/menu/meanmenu.min.css',
    'assets/vandor/wow/animate.css',
    'assets/css/style.css',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Section Preview — <?= htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8') ?></title>
    <?php foreach ($styles as $href): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars(url($href), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <style>
        /* Preview-only wrapper: neutral background, no header/footer chrome */
        html, body {
            margin: 0;
            padding: 0;
            background: #ffffff;
            color: #0c0c0c;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .section-preview-stage {
            width: 100%;
            min-height: 100vh;
            display: block;
        }
        /* Ensure hero video is visible in preview (it normally sits under a fixed header) */
        .hero-video-section { position: relative; min-height: 60vh; background: #000; }
        .hero-video { width: 100%; height: 60vh; object-fit: cover; display: block; }
        .hero-video-mobile { display: none; }
        @media (max-width: 1024px) {
            .hero-video-desktop { display: none; }
            .hero-video-mobile { display: block; }
        }
        /* Disable scroll-triggered animations in preview so content is immediately visible */
        .wow { visibility: visible !important; animation: none !important; }
        [data-aos] { opacity: 1 !important; transform: none !important; }
    </style>
</head>
<body>
    <div class="section-preview-stage">
        <?= $sectionHtml ?>
    </div>

    <!-- Frontend JS for interactive sections (swiper, video controls) -->
    <script src="<?= htmlspecialchars(url('assets/vandor/jquery/jquery.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(url('assets/vandor/bootstrap/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(url('assets/vandor/swiper/swiper-bundle.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script src="<?= htmlspecialchars(url('assets/vandor/fontawesome/fontawesome-pro.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Initialize any swiper sliders in the preview
        document.querySelectorAll('.swiper').forEach(function (el) {
            if (typeof Swiper === 'undefined') return;
            try {
                new Swiper(el, {
                    loop: false,
                    slidesPerView: 'auto',
                    spaceBetween: 16,
                    pagination: el.querySelector('.swiper-pagination') ? { el: el.querySelector('.swiper-pagination'), clickable: true } : false,
                    navigation: el.querySelector('.social-reels__nav--prev') ? {
                        prevEl: el.closest('section').querySelector('.social-reels__nav--prev'),
                        nextEl: el.closest('section').querySelector('.social-reels__nav--next')
                    } : false,
                    scrollbar: el.querySelector('.swiper-scrollbar') ? { el: el.querySelector('.swiper-scrollbar'), draggable: true } : false
                });
            } catch (e) {}
        });

        // Hero video: load sources so the preview actually plays
        document.querySelectorAll('[data-hero-video]').forEach(function (video) {
            const src = video.getAttribute('data-src') || '';
            if (src) {
                video.setAttribute('src', src);
                video.load();
                video.muted = true;
                const p = video.play();
                if (p && p.catch) p.catch(function () {});
            }
        });

        // Instagram reels: load video sources in preview
        document.querySelectorAll('.social-reels__video').forEach(function (video) {
            const src = video.getAttribute('data-src') || '';
            if (src) {
                video.setAttribute('src', src);
                video.load();
                video.muted = true;
                const p = video.play();
                if (p && p.catch) p.catch(function () {});
            }
        });
    });
    </script>
</body>
</html>