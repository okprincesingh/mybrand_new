<?php
/**
 * Section-wise Live Preview API
 * -----------------------------
 * Handles draft save / publish / discard / status for any section type.
 *
 * Endpoints (POST JSON, admin-auth + CSRF):
 *   - action=save_draft : persist current form values as a draft (live tables untouched)
 *   - action=publish    : copy draft fields into the real table, then delete the draft
 *   - action=discard    : delete the draft (preview reverts to published state)
 *   - action=status     : return {has_draft, draft_data, published_data}
 *
 * Safety:
 *   - Admin auth required.
 *   - CSRF token required.
 *   - Real tables are NEVER modified by save_draft / discard.
 *   - Only publish writes to real tables (then invalidates cache).
 *   - No public URL / routing / SEO changes.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/cms.php';
require_once __DIR__ . '/../../includes/catalog.php';
require_once __DIR__ . '/../../includes/preview.php';
require_once __DIR__ . '/../../includes/draft.php';
require_once __DIR__ . '/../../includes/url.php';
require_once __DIR__ . '/../../includes/section-render.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Admin auth
$adminUser = admin_current();
if (!$adminUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Parse JSON body
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

// CSRF check (supports JSON body, X-CSRF-Token header, and form POST)
$csrfToken = '';
if (is_array($payload) && isset($payload['csrf_token'])) {
    $csrfToken = (string) $payload['csrf_token'];
} elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $csrfToken = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
} elseif (!empty($_POST['csrf_token'])) {
    $csrfToken = (string) $_POST['csrf_token'];
}
if ($csrfToken === '' || !hash_equals(csrf_token(), $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

$action = (string) ($payload['action'] ?? '');
$contentType = (string) ($payload['content_type'] ?? '');
$entityId = (int) ($payload['entity_id'] ?? 0);

if ($contentType === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing content_type']);
    exit;
}

$pdo = db();
if (!$pdo) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database unavailable']);
    exit;
}

draft_ensure_table($pdo);

/**
 * Registry of supported section types.
 * aliases: form field name => real column (existing_* is also auto-mapped).
 */
$sectionRegistry = [
    'home_hero_video' => [
        'table' => 'home_hero_videos',
        'columns' => [
            'label',
            'desktop_video_url',
            'desktop_light_video_url',
            'mobile_video_url',
            'desktop_video_file',
            'desktop_light_video_file',
            'mobile_video_file',
            'poster_image',
        ],
        'aliases' => [
            'existing_poster_image' => 'poster_image',
            'existing_desktop_video_file' => 'desktop_video_file',
            'existing_desktop_light_video_file' => 'desktop_light_video_file',
            'existing_mobile_video_file' => 'mobile_video_file',
        ],
        'cache_invalidate' => 'cms_invalidate_home_hero_videos_cache',
    ],
    'home_slide' => [
        'table' => 'home_slides',
        'columns' => ['badge_text', 'title', 'description', 'button_text', 'button_url', 'image_path', 'image_alt'],
        'aliases' => [
            'existing_image_path' => 'image_path',
            'existing_image' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_slides_cache',
    ],
    'home_testimonial' => [
        'table' => 'home_testimonials',
        'columns' => ['name', 'location', 'content', 'rating', 'image_path'],
        'aliases' => [
            'existing_image_path' => 'image_path',
            'existing_image' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_testimonials_cache',
    ],
    'home_office' => [
        'table' => 'home_offices',
        'columns' => [
            'country',
            'company_name',
            'address',
            'email',
            'phone',
            'registration_label',
            'registration_number',
            'tax_label',
            'tax_number',
            'image_path',
        ],
        'aliases' => [
            'existing_image_path' => 'image_path',
            'existing_image' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_offices_cache',
    ],
    'home_instagram_reel' => [
        'table' => 'home_instagram_reels',
        'columns' => ['reel_url', 'video_path'],
        'aliases' => [
            'existing_video_path' => 'video_path',
            'existing_video' => 'video_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_instagram_reels_cache',
    ],
    'home_working_process' => [
        'table' => 'home_working_process',
        'columns' => ['section_key', 'title_small', 'title_large', 'text', 'href', 'image_path', 'alt_text', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_working_process_cache',
    ],
    'home_working_process_content' => [
        'table' => 'home_working_process_content',
        'columns' => ['section_key', 'eyebrow_text', 'title_span_text', 'title_text', 'description_text', 'animation_mode', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_working_process_content_cache',
    ],
    'home_brand_builder' => [
        'table' => 'home_brand_builder',
        'columns' => ['section_key', 'kicker_text', 'title_text', 'subtitle_text', 'primary_btn_text', 'primary_btn_url', 'secondary_btn_text', 'secondary_btn_url', 'stat_1_number', 'stat_1_label', 'stat_2_number', 'stat_2_label', 'stat_3_number', 'stat_3_label', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_brand_builder_cache',
    ],
    'home_getting_started' => [
        'table' => 'home_getting_started',
        'columns' => ['step_number', 'icon_emoji', 'title', 'description', 'learn_more_url', 'back_image_path', 'back_image_alt', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_back_image_path' => 'back_image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_getting_started_cache',
    ],
    'home_marquee_strip' => [
        'table' => 'home_marquee_strips',
        'columns' => ['strip_key', 'items', 'brand_text', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_marquee_strips_cache',
    ],
    'home_partner_logo' => [
        'table' => 'home_partner_logos',
        'columns' => ['logo_path', 'alt_text', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_logo_path' => 'logo_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_partner_logos_cache',
    ],
    'home_certification_logo' => [
        'table' => 'home_certification_logos',
        'columns' => ['logo_path', 'alt_text', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_logo_path' => 'logo_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_certification_logos_cache',
    ],
    'home_getting_started_content' => [
        'table' => 'home_getting_started_content',
        'columns' => ['section_key', 'heading_text', 'description_text', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_getting_started_content_cache',
    ],
    'home_milestones_content' => [
        'table' => 'home_milestones_content',
        'columns' => ['section_key', 'eyebrow_text', 'heading_text', 'description_text', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_milestones_content_cache',
    ],
    'home_milestones' => [
        'table' => 'home_milestones',
        'columns' => ['image_path', 'image_alt', 'kicker', 'number_value', 'title', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_milestones_cache',
    ],
    'home_cta_card' => [
        'table' => 'home_cta_cards',
        'columns' => ['card_key', 'title', 'button_text', 'button_url', 'image_path', 'image_alt', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
            'existing_image' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_home_cta_cards_cache',
    ],
    'home_testimonials_content' => [
        'table' => 'home_testimonials_content',
        'columns' => ['section_key', 'eyebrow_text', 'heading_text', 'rating_prefix', 'rating_highlight', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_testimonials_content_cache',
    ],
    'home_instagram_reels_content' => [
        'table' => 'home_instagram_reels_content',
        'columns' => ['section_key', 'eyebrow_text', 'heading_html', 'intro_text', 'tagline_text', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_instagram_reels_content_cache',
    ],
    'home_offices_content' => [
        'table' => 'home_offices_content',
        'columns' => ['section_key', 'eyebrow_text', 'heading_text', 'subheading_text', 'intro_text', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_home_offices_content_cache',
    ],
    'blog' => [
        'table' => 'blog_posts',
        'columns' => ['title', 'slug', 'excerpt', 'content', 'meta_title', 'canonical_url', 'meta_keywords', 'meta_description', 'featured_image', 'category', 'author_name', 'published_at', 'status', 'tags'],
        'aliases' => [
            'existing_featured_image' => 'featured_image',
        ],
    ],
    'page' => [
        'table' => 'pages',
        'columns' => ['title', 'slug', 'content', 'status', 'page_group', 'template_key'],
    ],
    'product' => [
        'table' => 'products',
        'columns' => ['name', 'slug', 'short_description', 'description', 'price', 'stock', 'status', 'featured_image', 'is_active'],
        'aliases' => [
            'existing_featured_image' => 'featured_image',
        ],
    ],
    'category' => [
        'table' => 'categories',
        'columns' => ['parent_id', 'name', 'slug', 'description', 'image_path', 'page_image_path', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
            'existing_page_image_path' => 'page_image_path',
            'existing_page_image' => 'page_image_path',
        ],
    ],
    'certificate' => [
        'table' => 'certificates',
        'columns' => ['title', 'image_path', 'file_path', 'file_type', 'category', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
            'existing_file_path' => 'file_path',
        ],
    ],
    'how_it_works_section' => [
        'table' => 'how_it_works_sections',
        'columns' => ['title', 'body_1', 'body_2', 'image_path', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_how_it_works_cache',
    ],
    'how_it_works_accordion' => [
        'table' => 'how_it_works_accordions',
        'columns' => ['title', 'body', 'sort_order', 'is_open_default', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_how_it_works_cache',
    ],
    'site_settings' => [
        'table' => 'site_settings',
        'columns' => [
            'shop_landing_title',
            'shop_landing_description',
            'shop_landing_subtitle',
            'shop_category_subtitle',
            'shop_subcategory_fallback_description',
            'shop_related_products_title',
            'how_it_works_hero_title',
            'how_it_works_hero_description',
            'how_it_works_layout',
            'about_intro_heading',
            'about_certifications_heading',
            'about_private_label_heading',
            'about_private_label_intro',
            'about_private_label_block_title',
            'about_private_label_image',
            'about_accreditations_heading',
            'about_accreditations_intro',
        ],
        'cache_invalidate' => 'cms_invalidate_settings_cache',
    ],
    'about_block' => [
        'table' => 'about_blocks',
        'columns' => ['section_heading', 'section_intro', 'block_title', 'body', 'image_path', 'image_alt', 'layout', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_about_cache',
    ],
    'about_certification' => [
        'table' => 'about_certifications',
        'columns' => ['icon_path', 'title', 'description', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_icon_path' => 'icon_path',
        ],
        'cache_invalidate' => 'cms_invalidate_about_cache',
    ],
    'about_key_benefit' => [
        'table' => 'about_key_benefits',
        'columns' => ['label', 'description', 'sort_order', 'is_active'],
        'cache_invalidate' => 'cms_invalidate_about_cache',
    ],
    'about_accreditation' => [
        'table' => 'about_accreditations',
        'columns' => ['image_path', 'alt_text', 'sort_order', 'is_active'],
        'aliases' => [
            'existing_image_path' => 'image_path',
        ],
        'cache_invalidate' => 'cms_invalidate_about_cache',
    ],
];

if (!isset($sectionRegistry[$contentType])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported content_type: ' . $contentType]);
    exit;
}

$registry = $sectionRegistry[$contentType];
$realTable = $registry['table'];
$columns = $registry['columns'];
$aliases = $registry['aliases'] ?? [];
$cacheFn = $registry['cache_invalidate'];

switch ($action) {
    case 'save_draft':
        $draftData = (array) ($payload['data'] ?? []);
        $clean = draft_normalize_form_data($contentType, $draftData, $columns, $aliases);
        if (!$clean) {
            echo json_encode(['success' => false, 'message' => 'No draft fields provided']);
            exit;
        }
        if ($entityId <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Save the record first (Add/Update), then edit it to use live preview drafts.',
            ]);
            exit;
        }
        $saved = draft_save($contentType, $entityId, $clean, (int) ($adminUser['id'] ?? 0));
        echo json_encode([
            'success' => $saved,
            'message' => $saved ? 'Draft saved. Live site unchanged until Publish.' : 'Failed to save draft.',
            'has_draft' => $saved,
        ]);
        exit;

    case 'discard':
        $discarded = draft_discard($contentType, $entityId);
        echo json_encode([
            'success' => $discarded,
            'message' => $discarded ? 'Draft discarded. Preview reverted to published state.' : 'No draft to discard.',
            'has_draft' => false,
        ]);
        exit;

    case 'publish':
        if ($entityId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cannot publish: record must be saved first (entity_id=0).']);
            exit;
        }
        $published = draft_publish($contentType, $entityId, function ($pdo, int $eid, array $draftData) use ($realTable, $columns): bool {
            $sets = [];
            $params = [':id' => $eid];
            foreach ($columns as $col) {
                if (array_key_exists($col, $draftData)) {
                    $sets[] = '`' . str_replace('`', '', $col) . '` = :' . $col;
                    $params[':' . $col] = (string) $draftData[$col];
                }
            }
            if (!$sets) {
                return false;
            }
            // Table name comes from our internal registry only (never from user input).
            $sql = 'UPDATE `' . str_replace('`', '', $realTable) . '` SET ' . implode(', ', $sets) . ' WHERE id = :id';
            return db_execute($pdo, $sql, $params);
        });

        if ($published && function_exists($cacheFn)) {
            $cacheFn();
        }

        echo json_encode([
            'success' => $published,
            'message' => $published ? 'Draft published to the live site.' : 'Publish failed. No draft exists for this record.',
            'has_draft' => !$published,
        ]);
        exit;

    case 'status':
        $hasDraft = draft_has($contentType, $entityId);
        // Always use raw draft for admin status (preview mode not required).
        $draftData = $hasDraft ? draft_get_raw($contentType, $entityId) : null;

        $publishedRow = null;
        if ($entityId > 0) {
            $publishedRow = db_fetch_one(
                $pdo,
                'SELECT * FROM `' . str_replace('`', '', $realTable) . '` WHERE id = :id LIMIT 1',
                [':id' => $entityId]
            );
        }

        echo json_encode([
            'success' => true,
            'has_draft' => $hasDraft,
            'draft_data' => $draftData,
            'published_data' => $publishedRow,
        ]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
        exit;
}
