<?php
/**
 * Section Render Library
 * ----------------------
 * Reusable render functions for individual homepage sections.
 *
 * These functions output the EXACT same HTML/CSS classes used on the live
 * website (index.php), so the admin "Section-wise Live Preview" is pixel-perfect.
 *
 * Each function accepts the merged data array (published row + draft overrides)
 * and returns a single <section> fragment — nothing else.
 *
 * Used by:
 *   - admin/section-preview-iframe.php  (isolated section preview)
 *   - index.php (optional single-source refactor, off by default)
 *
 * Safety:
 *   - No database reads here. Callers pass already-merged data.
 *   - No side effects. Pure HTML output.
 */

require_once __DIR__ . '/url.php';
require_once __DIR__ . '/security.php';

if (!function_exists('section_render_hero_video')) {
    /**
     * Render the homepage hero video section.
     *
     * @param array $video Merged hero video row (desktop_video_url, mobile_video_url, poster_image, ...).
     * @return string HTML <section> fragment.
     */
    function section_render_hero_video(array $video): string
    {
        $desktopUrl = (string) ($video['desktop_video_url'] ?? 'https://jaikvik.in/lab/mybrand_video/mybrandvideo');
        $desktopLightUrl = (string) ($video['desktop_light_video_url'] ?? 'https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo');
        $mobileUrl = (string) ($video['mobile_video_url'] ?? 'https://jaikvik.in/lab/mybrand_video/mybrandmobilevideo');
        $poster = (string) ($video['poster_image'] ?? '');

        $desktopSrc = htmlspecialchars(url($desktopUrl), ENT_QUOTES, 'UTF-8');
        $desktopLightSrc = htmlspecialchars(url($desktopLightUrl), ENT_QUOTES, 'UTF-8');
        $mobileSrc = htmlspecialchars(url($mobileUrl), ENT_QUOTES, 'UTF-8');
        $posterAttr = $poster !== '' ? ' poster="' . htmlspecialchars(url($poster), ENT_QUOTES, 'UTF-8') . '"' : '';

        return <<<HTML
<section class="hero-video-section container-fluid p-0">
    <video
        class="hero-video hero-video-desktop"
        data-hero-video="desktop"
        data-src="{$desktopSrc}"
        data-src-light="{$desktopLightSrc}"{$posterAttr}
        autoplay muted loop playsinline preload="none"></video>
    <video
        class="hero-video hero-video-mobile"
        data-hero-video="mobile"
        data-src="{$mobileSrc}"{$posterAttr}
        autoplay muted loop playsinline preload="none"></video>
    <button
        class="hero-video-mute-toggle"
        type="button"
        aria-label="Unmute hero video"
        aria-pressed="false"
        data-hero-video-mute>
        <i class="fa-solid fa-volume-xmark" aria-hidden="true"></i>
    </button>
</section>
HTML;
    }
}

if (!function_exists('section_render_slider')) {
    /**
     * Render the homepage slider section (intro1 slider).
     *
     * @param array $slides List of merged slide rows.
     * @return string HTML <section> fragment.
     */
    function section_render_slider(array $slides): string
    {
        if (!$slides) {
            $slides = [
                [
                    'badge_text' => 'PRIVATE LABEL IS NOW SIMPLIFIED',
                    'title' => 'Unleash your custom personal care line effortlessly',
                    'description' => 'Launch Your Own Cosmetic Line & Amplify Your Brand With Our Expert Formulations.',
                    'button_text' => 'Explore Collection',
                    'button_url' => 'shop.php',
                    'image_path' => 'assets/imgs/hero/hero-img.png',
                    'image_alt' => 'Beauty model',
                ],
            ];
        }

        $items = '';
        foreach ($slides as $slide) {
            $badge = htmlspecialchars((string) ($slide['badge_text'] ?? ''), ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars((string) ($slide['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $desc = htmlspecialchars((string) ($slide['description'] ?? ''), ENT_QUOTES, 'UTF-8');
            $btnText = htmlspecialchars((string) ($slide['button_text'] ?? ''), ENT_QUOTES, 'UTF-8');
            $btnUrl = htmlspecialchars(url((string) ($slide['button_url'] ?? 'shop.php')), ENT_QUOTES, 'UTF-8');
            $image = htmlspecialchars(url((string) ($slide['image_path'] ?? 'assets/imgs/hero/hero-img.png')), ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars((string) ($slide['image_alt'] ?? $title), ENT_QUOTES, 'UTF-8');

            $items .= <<<HTML
<div class="intro1-slider-item swiper-slide">
    <div class="row g-4 align-items-center">
        <div class="col-lg-6">
            <div class="intro1__content">
                <span class="intro1__badge">{$badge}</span>
                <h1 class="intro1__content-title">{$title}</h1>
                <p class="intro1__content-desc">{$desc}</p>
                <div class="intro1__btn-wrap">
                    <a href="{$btnUrl}" class="rr-btn-button">{$btnText}</a>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="intro1__thumb">
                <img src="{$image}" alt="{$alt}">
            </div>
        </div>
    </div>
</div>
HTML;
        }

        return <<<HTML
<section class="intro1-section rr-ov-hidden">
    <div class="container">
        <div class="intro1-slider swiper">
            <div class="swiper-wrapper">
                {$items}
            </div>
            <div class="intro1-slider__dots"></div>
        </div>
    </div>
</section>
HTML;
    }
}

if (!function_exists('section_render_testimonials')) {
    /**
     * Render the homepage testimonials section.
     *
     * @param array $testimonials List of merged testimonial rows.
     * @return string HTML <section> fragment.
     */
    function section_render_testimonials(array $testimonials): string
    {
        if (!$testimonials) {
            $testimonials = [
                [
                    'name' => 'Charlotte Evans',
                    'location' => 'Birmingham, UK',
                    'content' => 'I have sensitive skin, and most products irritate me. Your formulas changed that and gave visible results quickly.',
                    'rating' => 5,
                    'image_path' => 'assets/imgs/home/testimonial-thumb3_1.png',
                ],
            ];
        }

        $items = '';
        foreach ($testimonials as $t) {
            $name = htmlspecialchars((string) ($t['name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $location = htmlspecialchars((string) ($t['location'] ?? ''), ENT_QUOTES, 'UTF-8');
            $content = htmlspecialchars((string) ($t['content'] ?? ''), ENT_QUOTES, 'UTF-8');
            $rating = max(1, min(5, (int) ($t['rating'] ?? 5)));
            $image = htmlspecialchars(url((string) ($t['image_path'] ?? 'assets/imgs/home/testimonial-thumb3_1.png')), ENT_QUOTES, 'UTF-8');

            $stars = '';
            for ($i = 1; $i <= 5; $i++) {
                $stars .= $i <= $rating
                    ? '<i class="fa-solid fa-star"></i>'
                    : '<i class="fa-regular fa-star"></i>';
            }

            $items .= <<<HTML
<div class="testimonial-card swiper-slide">
    <div class="testimonial-card__inner">
        <div class="testimonial-card__rating">{$stars}</div>
        <p class="testimonial-card__content">{$content}</p>
        <div class="testimonial-card__author">
            <img src="{$image}" alt="{$name}" class="testimonial-card__avatar">
            <div>
                <h4 class="testimonial-card__name">{$name}</h4>
                <span class="testimonial-card__location">{$location}</span>
            </div>
        </div>
    </div>
</div>
HTML;
        }

        return <<<HTML
<section class="testimonial-section section-spacing-120 rr-ov-hidden">
    <div class="container">
        <div class="section-heading text-center mb-5">
            <h2 class="section-heading__title">What Our Clients Say</h2>
        </div>
        <div class="testimonial-slider swiper">
            <div class="swiper-wrapper">
                {$items}
            </div>
            <div class="swiper-pagination"></div>
        </div>
    </div>
</section>
HTML;
    }
}

if (!function_exists('section_render_offices')) {
    /**
     * Render the homepage offices section.
     *
     * @param array $offices List of merged office rows.
     * @return string HTML <section> fragment.
     */
    function section_render_offices(array $offices): string
    {
        if (!$offices) {
            $offices = [
                [
                    'country' => 'United Kingdom',
                    'company_name' => 'mybrandplease UK',
                    'address' => 'Unit 1, Durham Way South, Newton Aycliffe, DL5 6ZF, UNITED KINGDOM',
                    'email' => 'info@mybrandplease.com',
                    'phone' => '+44 7940 359995',
                    'image_path' => 'assets/imgs/home/office/Flag-United-Kingdom.webp',
                ],
            ];
        }

        $items = '';
        $delay = 0.1;
        foreach ($offices as $office) {
            $country = htmlspecialchars((string) ($office['country'] ?? 'Office'), ENT_QUOTES, 'UTF-8');
            $company = htmlspecialchars((string) ($office['company_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $address = htmlspecialchars((string) ($office['address'] ?? ''), ENT_QUOTES, 'UTF-8');
            $email = htmlspecialchars((string) ($office['email'] ?? ''), ENT_QUOTES, 'UTF-8');
            $phone = htmlspecialchars((string) ($office['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
            $image = htmlspecialchars(url((string) ($office['image_path'] ?? 'assets/imgs/home/office/Flag-United-Kingdom.webp')), ENT_QUOTES, 'UTF-8');
            $phoneHref = htmlspecialchars(preg_replace('/\D+/', '', $phone), ENT_QUOTES, 'UTF-8');
            $delayClass = 'wow fadeInUp';
            $delayAttr = 'data-wow-delay=".' . (int) round($delay * 10) . 's"';

            $companyHtml = $company !== '' ? '<p class="office-card__company text-center">' . $company . '</p>' : '';
            $phoneHtml = $phone !== '' ? '<a class="office-card__meta" href="https://wa.me/' . $phoneHref . '" target="_blank" rel="noopener noreferrer"><span class="office-card__meta-icon"><i class="fa-brands fa-whatsapp"></i></span><span>' . $phone . '</span></a>' : '';
            $emailHtml = $email !== '' ? '<a class="office-card__meta" href="mailto:' . $email . '"><span class="office-card__meta-icon"><i class="fa-regular fa-envelope"></i></span><span>' . $email . '</span></a>' : '';

            $items .= <<<HTML
<article class="office-card {$delayClass}" {$delayAttr}>
    <div class="office-card__topline"></div>
    <div class="office-card__flag">
        <img src="{$image}" alt="{$country} Office">
    </div>
    <div class="office-card__body">
        <h3 class="office-card__title">{$country}</h3>
        {$companyHtml}
        <p class="office-card__address text-center">{$address}</p>
        <div class="office-card__meta-list">
            {$phoneHtml}
            {$emailHtml}
        </div>
    </div>
</article>
HTML;
            $delay += 0.1;
        }

        return <<<HTML
<section class="section-spacing-120 rr-ov-hidden">
    <div class="container">
        <div class="office-showcase__intro wow fadeInUp" data-wow-delay=".3s">
            <span class="office-showcase__eyebrow">Global Presence</span>
            <h2 class="office-showcase__title">~ Our Global Network ~</h2>
            <p class="office-showcase__lead text-center">Our registered offices across key markets bring local expertise, seamless coordination, and responsive support to every partnership.</p>
        </div>
        <div class="office-grid">
            {$items}
        </div>
    </div>
</section>
HTML;
    }
}

if (!function_exists('section_render_instagram')) {
    /**
     * Render the homepage Instagram reels section.
     *
     * @param array $reels List of merged reel rows.
     * @return string HTML <section> fragment.
     */
    function section_render_instagram(array $reels): string
    {
        if (!$reels) {
            return '<section class="social-reels rr-ov-hidden" id="video-showcase"><div class="container"><p class="text-center text-muted">No reels available. Add your first reel video.</p></div></section>';
        }

        $cards = '';
        $rendered = 0;
        foreach ($reels as $reel) {
            if ($rendered >= 8) {
                break;
            }
            $reelUrl = (string) ($reel['reel_url'] ?? '');
            $videoPath = trim((string) ($reel['video_path'] ?? ''));
            $videoUrl = '';
            if ($videoPath !== '') {
                $videoUrl = url($videoPath);
            }
            $cleanUrl = preg_replace('/\?.*$/', '', $reelUrl) ?: $reelUrl;
            $cleanUrl = rtrim($cleanUrl, '/');
            $embedUrl = str_ends_with($cleanUrl, '/embed') ? $cleanUrl : ($cleanUrl !== '' ? ($cleanUrl . '/embed') : '');
            if ($videoUrl === '' && $embedUrl === '') {
                continue;
            }
            $rendered++;

            $videoSrcAttr = $videoUrl !== '' ? ' data-video-src="' . htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') . '"' : '';
            $embedSrcAttr = $embedUrl !== '' ? ' data-embed-src="' . htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8') . '"' : '';

            $media = '';
            if ($videoUrl !== '') {
                $media = '<video data-src="' . htmlspecialchars($videoUrl, ENT_QUOTES, 'UTF-8') . '" class="social-reels__video" playsinline muted loop preload="none"></video><button class="social-reels__volume-btn" type="button" aria-label="Unmute reel" aria-pressed="false"><i class="fa-solid fa-volume-xmark" aria-hidden="true"></i></button>';
            } elseif ($embedUrl !== '') {
                $media = '<iframe src="' . htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8') . '" class="social-reels__iframe" title="Instagram reel ' . $rendered . '" loading="lazy" allow="autoplay; encrypted-media; picture-in-picture; clipboard-write" allowfullscreen></iframe>';
            }

            $badge = $reelUrl !== ''
                ? '<a href="' . htmlspecialchars($reelUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer" class="social-reels__badge social-reels__badge--link" aria-label="Open Instagram reel ' . $rendered . '"><i class="fa-brands fa-instagram"></i></a>'
                : '<span class="social-reels__badge"><i class="fa-brands fa-instagram"></i></span>';

            $cards .= <<<HTML
<div class="social-reels__card js-reel-card swiper-slide" aria-label="Open reel {$rendered}"{$videoSrcAttr}{$embedSrcAttr}>
    {$media}
    {$badge}
</div>
HTML;
        }

        return <<<HTML
<section class="social-reels rr-ov-hidden" id="video-showcase">
    <div class="container">
        <div class="social-reels__intro">
            <span class="milestone-highlight__eyebrow">Video Showcase</span>
            <h2 class="social-reels__title">
                <span>Watch it!</span>
                <span class="social-reels__title-star" aria-hidden="true">*</span>
                <span class="social-reels__title-love">Love it!</span>
                <span class="social-reels__title-star" aria-hidden="true">*</span>
                <span>Build it!</span>
            </h2>
            <p class="social-reels__lead text-center">We don't just manufacture products. We manufacture dominance.</p>
        </div>
    </div>
    <div class="social-reels__viewport">
        <button class="social-reels__nav social-reels__nav--prev" type="button" aria-label="Previous video"><i class="fa-solid fa-chevron-left" aria-hidden="true"></i></button>
        <div class="social-reels__slider swiper js-video-showcase-slider" aria-label="Customer social reels">
            <div class="swiper-wrapper">
                {$cards}
            </div>
        </div>
        <button class="social-reels__nav social-reels__nav--next" type="button" aria-label="Next video"><i class="fa-solid fa-chevron-right" aria-hidden="true"></i></button>
        <div class="social-reels__scrollbar swiper-scrollbar" aria-label="Video showcase slider"></div>
    </div>
</section>
HTML;
    }
}

if (!function_exists('section_render_working_process')) {
    /**
     * Render the homepage working process section.
     *
     * @param array $steps List of merged working process rows.
     * @return string HTML <section> fragment.
     */
    function section_render_working_process(array $steps): string
    {
        ob_start();
        ?>
        <section class="working-process pt-5 pb-5">
            <div class="container">
                <div class="section-title text-center mb-4">
                    <h2>How It Works</h2>
                </div>
                <div class="row">
                    <?php foreach ($steps as $step): ?>
                        <div class="col-md-3 mb-4">
                            <div class="process-card">
                                <img src="<?php echo htmlspecialchars(url($step['image_path']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($step['alt_text'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid mb-3">
                                <h3><?php echo htmlspecialchars($step['title_small'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($step['title_large'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><?php echo htmlspecialchars($step['text'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <a href="<?php echo htmlspecialchars(url($step['href']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary">Learn More</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_working_process_content_preview')) {
    /**
     * Render a working process preview section with editable content.
     *
     * @param array $content
     * @param array $steps
     * @return string
     */
    function section_render_working_process_content_preview(array $content, array $steps): string
    {
        ob_start();
        $marqueeStrip = cms_get_home_marquee_strip('working_process_services');
        ?>
        <section class="working-process-section" aria-label="<?php echo htmlspecialchars(($content['title_span_text'] ?? 'Why launch') . ' ' . ($content['title_text'] ?? 'your own brand'), ENT_QUOTES, 'UTF-8'); ?>">

            <div class="working-process-section__strip" aria-label="mybrandplease creative services">

                <div class="working-process-section__strip-services">
                    <?php
                    $marqueeItems = $marqueeStrip['items'] ?? [
                        'Skin Care',
                        'Hair Care',
                        'Body Care',
                        'Fragrances',
                        'Cosmetic Packaging'
                    ];
                    for ($stripLoop = 0; $stripLoop < 2; $stripLoop++):
                        foreach ($marqueeItems as $item):
                    ?>
                        <span><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="working-process-section__strip-dot">*</span>
                    <?php
                        endforeach;
                    endfor;
                    ?>
                </div>

                <div class="working-process-section__strip-brand">
                    <?php echo htmlspecialchars($marqueeStrip['brand_text'] ?? 'mybrandplease.com', ENT_QUOTES, 'UTF-8'); ?>
                </div>

            </div>

            <div class="working-process-section__inner">

                <div class="working-process-section__intro">

                    <span class="working-process-section__eyebrow">
                        <?php echo htmlspecialchars($content['eyebrow_text'] ?? 'Private Label', ENT_QUOTES, 'UTF-8'); ?>
                    </span>

                    <h2 class="working-process-section__title">
                        <span class="working-process-section__title-span"><?php echo htmlspecialchars($content['title_span_text'] ?? 'Why launch', ENT_QUOTES, 'UTF-8'); ?></span><br><?php echo $content['title_text'] ?? 'your own brand'; ?>
                    </h2>

                    <p class="working-process-section__lead">
                        <?php echo htmlspecialchars($content['description_text'] ?? 'Enhance your brand reputation and profitability with premium private label cosmetic products, low minimum order quantity, and competitive pricing.', ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                </div>

                <div class="working-process-section__track-wrap">

                    <div class="working-process-section__track" data-working-process-track data-animation-mode="<?php echo htmlspecialchars($content['animation_mode'] ?? 'default', ENT_QUOTES, 'UTF-8'); ?>">

                        <?php foreach ($steps as $processStep): ?>

                            <article class="working-process-card">

                                <div class="working-process-card__top">

                                    <span class="working-process-card__label">

                                        <span class="working-process-card__spark">
                                            &starf;
                                        </span>

                                        <span>
                                            Benefits of having your own brand
                                        </span>

                                    </span>

                                    <a class="working-process-card__link" href="<?php echo url($processStep['href']); ?>">
                                        <span aria-hidden="true">&nearr;</span>
                                    </a>

                                </div>

                                <span class="working-process-card__image">
                                    <img src="<?php echo htmlspecialchars(url($processStep['image_path']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($processStep['alt_text'], ENT_QUOTES, 'UTF-8'); ?>">
                                </span>

                                <h3 class="working-process-card__title">
                                    <span class="working-process-card__title-small"><?php echo htmlspecialchars($processStep['title_small'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <span class="working-process-card__title-large"><?php echo htmlspecialchars($processStep['title_large'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </h3>

                                <p class="working-process-card__text"><?php echo htmlspecialchars($processStep['text'], ENT_QUOTES, 'UTF-8'); ?></p>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </div>

            </div>

        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_brand_builder')) {
    /**
     * Render the homepage brand builder section.
     *
     * @param array $brandBuilder Merged brand builder row.
     * @param array $items List of merged builder item rows.
     * @return string HTML <section> fragment.
     */
    function section_render_brand_builder(array $brandBuilder, array $items): string
    {
        ob_start();
        ?>
        <section class="brand-builder pt-5 pb-5">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="brand-builder__content">
                            <span class="brand-builder__kicker"><?php echo $brandBuilder['kicker_text']; ?></span>
                            <h2><?php echo $brandBuilder['title_text']; ?></h2>
                            <p><?php echo htmlspecialchars($brandBuilder['subtitle_text'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="brand-builder__actions">
                                <a href="<?php echo htmlspecialchars(url($brandBuilder['primary_btn_url']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary"><?php echo htmlspecialchars($brandBuilder['primary_btn_text'], ENT_QUOTES, 'UTF-8'); ?></a>
                                <a href="<?php echo htmlspecialchars(url($brandBuilder['secondary_btn_url']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-secondary"><?php echo htmlspecialchars($brandBuilder['secondary_btn_text'], ENT_QUOTES, 'UTF-8'); ?></a>
                            </div>
                            <div class="brand-builder__stats row mt-4">
                                <div class="col-4"><strong><?php echo htmlspecialchars($brandBuilder['stat_1_number'], ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($brandBuilder['stat_1_label'], ENT_QUOTES, 'UTF-8'); ?></p></div>
                                <div class="col-4"><strong><?php echo htmlspecialchars($brandBuilder['stat_2_number'], ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($brandBuilder['stat_2_label'], ENT_QUOTES, 'UTF-8'); ?></p></div>
                                <div class="col-4"><strong><?php echo htmlspecialchars($brandBuilder['stat_3_number'], ENT_QUOTES, 'UTF-8'); ?></strong><p><?php echo htmlspecialchars($brandBuilder['stat_3_label'], ENT_QUOTES, 'UTF-8'); ?></p></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="brand-builder__grid">
                            <?php foreach ($items as $item): ?>
                                <div class="brand-builder__item">
                                    <img src="<?php echo htmlspecialchars(url($item['image_path']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($item['image_alt'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid">
                                    <span><?php echo htmlspecialchars($item['word_text'], ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_getting_started')) {
    /**
     * Render the homepage getting started section.
     *
     * @param array $steps List of merged getting started rows.
     * @return string HTML <section> fragment.
     */
    function section_render_getting_started(array $steps): string
    {
        ob_start();
        ?>
        <section class="getting-started pt-5 pb-5">
            <div class="container">
                <div class="section-title text-center mb-4">
                    <h2>Getting Started</h2>
                </div>
                <div class="row">
                    <?php foreach ($steps as $step): ?>
                        <div class="col-md-3 mb-4">
                            <div class="getting-started__card" style="background-image: url('<?php echo htmlspecialchars(url($step['back_image_path']), ENT_QUOTES, 'UTF-8'); ?>')">
                                <div class="getting-started__content">
                                    <span class="getting-started__step"><?php echo htmlspecialchars($step['step_number'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <h3><?php echo htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($step['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                    <a href="<?php echo htmlspecialchars(url($step['learn_more_url']), ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-link">Learn More</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_marquee_strip')) {
    /**
     * Render the homepage marquee strip section.
     *
     * @param array $strip Merged marquee strip row.
     * @return string HTML <section> fragment.
     */
    function section_render_marquee_strip(array $strip): string
    {
        ob_start();
        ?>
        <section class="marquee-strip py-4 bg-light">
            <div class="container">
                <div class="marquee-strip__row d-flex align-items-center justify-content-between">
                    <div class="marquee-strip__text">
                        <strong><?php echo htmlspecialchars($strip['brand_text'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="marquee-strip__items d-flex overflow-hidden">
                        <?php foreach ($strip['items'] as $item): ?>
                            <span class="marquee-strip__item px-3"><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_partner_logos')) {
    /**
     * Render the homepage partner logos section.
     *
     * @param array $logos List of merged partner logos.
     * @return string HTML <section> fragment.
     */
    function section_render_partner_logos(array $logos): string
    {
        ob_start();
        ?>
        <section class="partner-logos py-5">
            <div class="container">
                <div class="row justify-content-center align-items-center g-4">
                    <?php foreach ($logos as $logo): ?>
                        <div class="col-4 col-sm-2 text-center">
                            <img src="<?php echo htmlspecialchars(url($logo['logo_path']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($logo['alt_text'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_certification_logos')) {
    /**
     * Render the homepage certification logos section.
     *
     * @param array $logos List of merged certification logos.
     * @return string HTML <section> fragment.
     */
    function section_render_certification_logos(array $logos): string
    {
        ob_start();
        ?>
        <section class="certification-logos py-5 bg-light">
            <div class="container">
                <div class="row justify-content-center align-items-center g-4">
                    <?php foreach ($logos as $logo): ?>
                        <div class="col-4 col-sm-2 text-center">
                            <img src="<?php echo htmlspecialchars(url($logo['logo_path']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($logo['alt_text'], ENT_QUOTES, 'UTF-8'); ?>" class="img-fluid">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('section_render_by_type')) {
    /**
     * Dispatch helper: render a section by its content_type.
     *
     * @param string $contentType e.g. 'home_hero_video', 'home_slide', 'home_testimonial', 'home_office', 'home_instagram_reel'.
     * @param array  $data        Merged data array (single row for singletons, list for collections).
     * @return string HTML fragment.
     */
    function section_render_by_type(string $contentType, array $data): string
    {
        switch ($contentType) {
            case 'home_hero_video':
                return section_render_hero_video($data);
            case 'home_slide':
                return section_render_slider($data);
            case 'home_testimonial':
                return section_render_testimonials($data);
            case 'home_office':
                return section_render_offices($data);
            case 'home_instagram_reel':
                return section_render_instagram($data);
            case 'home_working_process':
                return section_render_working_process($data);
            case 'home_brand_builder':
                return section_render_brand_builder($data[0] ?? [], $data[1] ?? []);
            case 'home_getting_started':
                return section_render_getting_started($data);
            case 'home_marquee_strip':
                return section_render_marquee_strip($data);
            case 'home_partner_logo':
                return section_render_partner_logos($data);
            case 'home_certification_logo':
                return section_render_certification_logos($data);
            default:
                return '<div class="alert alert-warning m-4">Unknown section type: ' . htmlspecialchars($contentType, ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
}