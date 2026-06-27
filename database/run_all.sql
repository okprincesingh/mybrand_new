CREATE DATABASE IF NOT EXISTS mybrandplease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mybrandplease;

SOURCE database/schema.sql;
SOURCE database/migration_auth_tokens.sql;
SOURCE database/migration_query_optimization.sql;
SOURCE database/migration_home_slider.sql;
SOURCE database/migration_home_dynamic.sql;
SOURCE database/migration_why_pages.sql;
SOURCE database/migration_why_accordion.sql;
SOURCE database/migration_remove_media_library.sql;
SOURCE database/migration_shipping_system.sql;
