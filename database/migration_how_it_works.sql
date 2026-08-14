-- Migration: How It Works Page (Sections, Accordions, Site Settings & Performance Indexing)

CREATE TABLE IF NOT EXISTS `how_it_works_sections` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `body_1` TEXT NOT NULL,
  `body_2` TEXT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_how_it_works_sections_active_order` (`is_active`, `sort_order`, `id`),
  INDEX `idx_how_it_works_sections_order` (`sort_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `how_it_works_accordions` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_open_default` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_how_it_works_accordions_active_order` (`is_active`, `sort_order`, `id`),
  INDEX `idx_how_it_works_accordions_order` (`sort_order`, `id`),
  INDEX `idx_how_it_works_accordions_open_default` (`is_open_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default site settings if not existing
INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES ('how_it_works_layout', 'default')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES ('how_it_works_hero_title', 'Unleash Your Brand\'s Potential With Our Perfect Solution.')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

INSERT INTO `site_settings` (`setting_key`, `setting_value`)
VALUES ('how_it_works_hero_description', 'Embrace complete customization, meticulously tailoring your product line to seamlessly harmonize with your brand and visionary essence.\n\nUnlock boundless possibilities with mybrandplease.com\'s revolutionary approach to Private Label. Elevate your brand\'s identity and reign supreme in the industry.')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

-- Seed default feature sections if empty
INSERT INTO `how_it_works_sections` (`title`, `body_1`, `body_2`, `image_path`, `sort_order`, `is_active`)
SELECT 'Choose Your Product Components',
       'Unleash your brand\'s potential with <span class="theme-color-font fw-bold">mybrandplease.com</span>. Explore our extensive range of over 200+ formulations across body, skin, and hair care, carefully crafted for professional-grade results. Experience the luxury of high-quality ingredients, including naturally derived and certified organic components. Tailor your products to perfection with our diverse packaging options and captivating fragrances.',
       'Handpick your favorites, knowing they will captivate and delight your cherished clients. Embark on a sensory journey and sample our extraordinary products today.',
       'assets/imgs/how-it-works/Choose-Your-Product-Components-min-2048x1244.webp',
       1, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_sections`);

INSERT INTO `how_it_works_sections` (`title`, `body_1`, `body_2`, `image_path`, `sort_order`, `is_active`)
SELECT 'Define Your Offerings',
       'Harness the power of your brand\'s message and fine-tune your opening order. Define product names, quantities, and sizes to perfection. Make key decisions that will shape your product line. Take control and let us bring your vision to reality.',
       'Explore our blog for invaluable expert tips and tricks. Seize this opportunity to create a remarkable brand experience. Check out our blog for our expert tips &amp; tricks <a href="blog.php" class="theme-color-font">here</a>.',
       'assets/imgs/how-it-works/Define-Your-Offerings-min-2048x1244.webp',
       2, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_sections` WHERE `title` = 'Define Your Offerings');

INSERT INTO `how_it_works_sections` (`title`, `body_1`, `body_2`, `image_path`, `sort_order`, `is_active`)
SELECT 'Label Design & Printing',
       'Embark on your design journey with meticulous planning and make your labels shine. Our expert Graphic Designers are poised to create stunning logos and labels for your personal care products.',
       'Benefit from our comprehensive design services or utilize our templates to collaborate with your own team. Experience the added convenience of our in-house digital print services or explore external options for unique finishes and metallic elements.',
       'assets/imgs/how-it-works/Label-Design-Printing-min-2048x1243.webp',
       3, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_sections` WHERE `title` = 'Label Design & Printing');

INSERT INTO `how_it_works_sections` (`title`, `body_1`, `body_2`, `image_path`, `sort_order`, `is_active`)
SELECT 'Finishing Touches',
       'Elevate your brand with exceptional exterior packaging and exquisite finishing touches. Enhance your marketing presence and create a luxurious impression by adding premium exterior boxes.',
       'Ensure optimal protection during shipping and explore options like seals, shrink wrap, inserts, and promotional materials to make your products truly distinctive. Invest in finer details that leave a long-lasting impression.',
       'assets/imgs/how-it-works/Finishing-Touches-min-768x467.webp',
       4, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_sections` WHERE `title` = 'Finishing Touches');

-- Seed default order process accordions if empty
INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Contact our Project Consultants to place your order.',
       '<p class="text-muted lh-base fs-17 word-spacing-6">Once you have all the elements of your order finalized, get in touch with one of our Project Consultants to place your order. The following details will be required:</p><ul class="order-accordion__list"><li><strong>Products:</strong> The products you\'d like to order</li><li><strong>Fragrance:</strong> If you would like any of your products scented</li><li><strong>Sizes:</strong> The unit size of each product you would like us to produce</li><li><strong>Packaging:</strong> The containers and closures you would like to use</li><li><strong>Quantity:</strong> How many of each unit you would like to order</li><li><strong>Labels:</strong> If you need any assistance with label design and/or label printing</li><li><strong>Finishing Touches:</strong> If you require any exterior elements, such as boxes or seals</li><li><strong>Shipping Details:</strong> Where you will want your products shipped once complete.</li><li><strong>Additional Services:</strong> If you would like to use any of our additional services, such as photography or documentation preparations</li></ul>',
       1, 1, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions`);

INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Receive your Production Quote & Make Any Changes!',
       '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0">Your Project Consultant will consolidate all of your elements into a final production quote for you to review & view your unit and services pricing. This production quote will be the document that our Production Teams use to manufacture your goods, so it is essential that you make any necessary changes or modifications at this stage!</p>',
       2, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions` WHERE `sort_order` = 2);

INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Approve your Order & Pay your Deposit.',
       '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0">Once you have signed off on all the details of your order, we will require a 50% deposit before we move the order to production. Changes cannot be made after this time.</p>',
       3, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions` WHERE `sort_order` = 3);

INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Begin your Design Process with our Graphics Team or Share your Designs With us.',
       '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0">If you’ve chosen to use our graphic design services to design your labels and/or logo, the design process will begin now, after the order has been placed. You’ll be matched up with a designer and they will walk you through the process of the design. Otherwise, if you will be designing your own labels, we will provide your team with our templates at this time so they can set them up to ensure they will work with our printing presses. It is important to note that we always will need final approval on your order to proceed with any graphic design initiatives.</p>',
       4, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions` WHERE `sort_order` = 4);

INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Your Order Will Begin Production.',
       '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0">Now that your labels are finalized & ready for print, all of the puzzle pieces have come together and your order will go into the final stage of its production process. Our team will manufacture your order per the specifications of your approved production quote. Our standard lead time for opening orders is 8 weeks, once the labels have been finalized, however, these lead times are not guaranteed and can fluctuate to be both shorter and longer depending on a number of factors including component sourcing & seasonality.</p>',
       5, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions` WHERE `sort_order` = 5);

INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Your Order is Complete & Ready for Shipping! Final Payment is Required.',
       '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0">Once your order is complete and ready for shipping, we will require the balance of your order to be paid. Please note that any shipping charges will be added to your final bill, along with any applicable taxes or fees. Once paid, we will ship your products to your desired location, whether that be your personal or business address, or a fulfillment center of your choosing.</p>',
       6, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions` WHERE `sort_order` = 6);

INSERT INTO `how_it_works_accordions` (`title`, `body`, `sort_order`, `is_open_default`, `is_active`)
SELECT 'Your Vision has Been Brought to Life & your Products are Ready for your Clients!',
       '<p class="text-muted lh-base fs-17 word-spacing-6 mb-0">Your finished custom products have arrived! You are now ready to launch and present your personal care line to your customers.</p>',
       7, 0, 1
WHERE NOT EXISTS (SELECT 1 FROM `how_it_works_accordions` WHERE `sort_order` = 7);
