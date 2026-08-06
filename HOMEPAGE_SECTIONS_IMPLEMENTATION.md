# Homepage Dynamic Sections Implementation

## Overview
This implementation makes the following homepage sections dynamic and manageable through the admin panel:

1. **Working Process Cards** - 4-card section showing benefits of launching your own brand
2. **Brand Builder Section** - Hero section with rotating product category words and images
3. **Getting Started Steps** - 4-step process cards with flip animations
4. **Marquee Strips** - Scrolling service text strips
5. **Partner Logos** - Auto-scrolling partner/retailer logos
6. **Certification Logos** - Auto-scrolling certification/trust badges

## Files Created/Modified

### New Files
- `database/migration_homepage_sections.sql` - Database schema for all new tables
- `includes/cms_homepage_sections.php` - CMS functions to fetch dynamic content
- `admin/homepage-sections.php` - Admin interface for managing all sections

### Modified Files
- `index.php` - Updated to use dynamic CMS functions instead of hardcoded data

## Database Tables Created

1. **home_working_process** - Working process card data
2. **home_brand_builder** - Brand builder section configuration
3. **home_brand_builder_items** - Rotating words and images for brand builder
4. **home_getting_started** - Getting started step cards
5. **home_marquee_strips** - Marquee strip text content
6. **home_partner_logos** - Partner/retailer logos
7. **home_certification_logos** - Certification/trust badges

## Admin Panel Access

Navigate to: `admin/homepage-sections.php`

**Note:** A "Homepage Sections" link has been added to the admin sidebar under the **Home** section with a grid icon (bi bi-grid).

The admin interface provides 6 tabs:
1. **Working Process** - Manage the 4 benefit cards
2. **Brand Builder** - Edit the hero section text, buttons, and stats
3. **Getting Started** - Manage the 4-step process cards
4. **Marquee Strip** - Edit the scrolling service text
5. **Partner Logos** - Add/edit/delete partner company logos
6. **Certification Logos** - Add/edit/delete certification badges

## Features

### Caching
- All sections use 5-minute cache for optimal performance
- Cache is automatically invalidated when content is updated
- Preview mode bypasses cache for real-time editing

### Image Uploads
- Images are uploaded via `store_uploaded_image()` function
- Organized in subdirectories:
  - `uploads/home/working-process/`
  - `uploads/home/getting-started/`
  - `uploads/home/partner-logos/`
  - `uploads/home/certification-logos/`

### Fallback Content
- All CMS functions include fallback data
- If database is empty, hardcoded default content is displayed
- Ensures homepage always looks good even without admin configuration

## CMS Functions Available

```php
// Working Process Cards
cms_get_home_working_process(): array
cms_invalidate_home_working_process_cache(): void

// Brand Builder Section
cms_get_home_brand_builder(): array
cms_get_home_brand_builder_items(): array
cms_invalidate_home_brand_builder_cache(): void

// Getting Started Steps
cms_get_home_getting_started(): array
cms_invalidate_home_getting_started_cache(): void

// Marquee Strips
cms_get_home_marquee_strip(string $stripKey): array
cms_invalidate_home_marquee_strips_cache(): void

// Partner Logos
cms_get_home_partner_logos(): array
cms_invalidate_home_partner_logos_cache(): void

// Certification Logos
cms_get_home_certification_logos(): array
cms_invalidate_home_certification_logos_cache(): void
```

## Installation Steps

1. **Run the database migration:**
   ```bash
   mysql -u root mybrandplease < database/migration_homepage_sections.sql
   ```

2. **Verify files are in place:**
   - `includes/cms_homepage_sections.php` exists
   - `admin/homepage-sections.php` exists
   - `index.php` includes the new CMS file

3. **Access admin panel:**
   - Go to `admin/homepage-sections.php`
   - Login as admin if required
   - Start managing content through the tabs

## Usage Examples

### Adding a Working Process Step
1. Go to Admin → Homepage Sections → Working Process tab
2. Fill in the form with:
   - Title Small (e.g., "Brand")
   - Title Large (e.g., "Equity")
   - Description text
   - Link URL
   - Upload image
   - Set sort order
3. Click "Save Step"

### Editing Brand Builder Section
1. Go to Admin → Homepage Sections → Brand Builder tab
2. Modify any of the fields:
   - Kicker text (supports HTML)
   - Title text (supports HTML with rotating word span)
   - Subtitle, buttons, statistics
3. Click "Save Brand Builder"

### Managing Partner Logos
1. Go to Admin → Homepage Sections → Partner Logos tab
2. Click "Add New Partner Logo" or edit existing
3. Upload logo image (PNG, JPG, etc.)
4. Enter alt text for accessibility
5. Set sort order for positioning
6. Save

## Technical Details

### Cache Invalidation
When content is saved, the corresponding cache is cleared:
```php
cms_invalidate_home_working_process_cache();
cms_invalidate_home_brand_builder_cache();
// etc.
```

### Preview Mode Support
The CMS functions respect the preview mode system:
- `preview_mode_should_bypass_cache()` - Bypasses cache in preview mode
- `preview_mode_include_drafts()` - Includes inactive items in preview

### Security
- All output is escaped with `htmlspecialchars()`
- File uploads are validated by `store_uploaded_image()`
- Admin authentication required via `_init.php`
- CSRF protection should be added (recommended enhancement)

## Customization

### Adding New Marquee Strips
To add a new marquee strip type:
1. Insert into `home_marquee_strips` table with a new `strip_key`
2. Call `cms_get_home_marquee_strip('your_strip_key')` in the template

### Adding More Brand Builder Items
The brand builder supports unlimited rotating words/images:
1. Items are linked to the brand builder section via `section_id`
2. Add new items through the admin interface (enhancement needed)
3. Or directly insert into `home_brand_builder_items` table

## Troubleshooting

### Images not showing
- Check file permissions on `uploads/` directory
- Verify images were uploaded successfully
- Check browser console for 404 errors

### Changes not appearing on homepage
- Clear browser cache
- Wait 5 minutes for cache to expire
- Check if "Active" checkbox was checked
- Verify database connection

### Migration errors
- Ensure database name in `.env` matches (mybrandplease)
- Check MySQL user has CREATE TABLE permissions
- Review error messages for specific SQL issues

## Future Enhancements

1. Add CSRF protection to all forms
2. Implement drag-and-drop sorting for items
3. Add bulk upload for logos
4. Create brand builder items management tab
5. Add image cropping/resizing
6. Implement preview functionality
7. Add activity log for changes
8. Support for multiple marquee strips in admin

## Support

For issues or questions, refer to the main project documentation or contact the development team.