-- WordPress Table Cleanup for Staging
-- Remove all WordPress/WooCommerce legacy tables
-- Created: 2025-11-09
-- WARNING: This is irreversible! Backup created before running.

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- WooCommerce tables
DROP TABLE IF EXISTS `cfk_actionscheduler_actions`;
DROP TABLE IF EXISTS `cfk_actionscheduler_claims`;
DROP TABLE IF EXISTS `cfk_actionscheduler_groups`;
DROP TABLE IF EXISTS `cfk_actionscheduler_logs`;
DROP TABLE IF EXISTS `cfk_aws_cache`;
DROP TABLE IF EXISTS `cfk_aws_index`;
DROP TABLE IF EXISTS `cfk_commentmeta`;
DROP TABLE IF EXISTS `cfk_comments`;
DROP TABLE IF EXISTS `cfk_e_events`;
DROP TABLE IF EXISTS `cfk_e_notes`;
DROP TABLE IF EXISTS `cfk_e_notes_users_relations`;
DROP TABLE IF EXISTS `cfk_e_submissions`;
DROP TABLE IF EXISTS `cfk_e_submissions_actions_log`;
DROP TABLE IF EXISTS `cfk_e_submissions_values`;
DROP TABLE IF EXISTS `cfk_links`;
DROP TABLE IF EXISTS `cfk_options`;
DROP TABLE IF EXISTS `cfk_postmeta`;
DROP TABLE IF EXISTS `cfk_posts`;
DROP TABLE IF EXISTS `cfk_rsssl_csp_log`;
DROP TABLE IF EXISTS `cfk_snippets`;
DROP TABLE IF EXISTS `cfk_term_relationships`;
DROP TABLE IF EXISTS `cfk_term_taxonomy`;
DROP TABLE IF EXISTS `cfk_termmeta`;
DROP TABLE IF EXISTS `cfk_terms`;
DROP TABLE IF EXISTS `cfk_usermeta`;
DROP TABLE IF EXISTS `cfk_users`;
DROP TABLE IF EXISTS `cfk_wc_admin_note_actions`;
DROP TABLE IF EXISTS `cfk_wc_admin_notes`;
DROP TABLE IF EXISTS `cfk_wc_category_lookup`;
DROP TABLE IF EXISTS `cfk_wc_customer_lookup`;
DROP TABLE IF EXISTS `cfk_wc_download_log`;
DROP TABLE IF EXISTS `cfk_wc_order_addresses`;
DROP TABLE IF EXISTS `cfk_wc_order_coupon_lookup`;
DROP TABLE IF EXISTS `cfk_wc_order_operational_data`;
DROP TABLE IF EXISTS `cfk_wc_order_product_lookup`;
DROP TABLE IF EXISTS `cfk_wc_order_stats`;
DROP TABLE IF EXISTS `cfk_wc_order_tax_lookup`;
DROP TABLE IF EXISTS `cfk_wc_orders`;
DROP TABLE IF EXISTS `cfk_wc_orders_meta`;
DROP TABLE IF EXISTS `cfk_wc_product_attributes_lookup`;
DROP TABLE IF EXISTS `cfk_wc_product_download_directories`;
DROP TABLE IF EXISTS `cfk_wc_product_meta_lookup`;
DROP TABLE IF EXISTS `cfk_wc_rate_limits`;
DROP TABLE IF EXISTS `cfk_wc_reserved_stock`;
DROP TABLE IF EXISTS `cfk_wc_spm_checkpoints`;
DROP TABLE IF EXISTS `cfk_wc_tax_rate_classes`;
DROP TABLE IF EXISTS `cfk_wc_webhooks`;
DROP TABLE IF EXISTS `cfk_woocommerce_api_keys`;
DROP TABLE IF EXISTS `cfk_woocommerce_attribute_taxonomies`;
DROP TABLE IF EXISTS `cfk_woocommerce_downloadable_product_permissions`;
DROP TABLE IF EXISTS `cfk_woocommerce_log`;
DROP TABLE IF EXISTS `cfk_woocommerce_order_itemmeta`;
DROP TABLE IF EXISTS `cfk_woocommerce_order_items`;
DROP TABLE IF EXISTS `cfk_woocommerce_payment_tokenmeta`;
DROP TABLE IF EXISTS `cfk_woocommerce_payment_tokens`;
DROP TABLE IF EXISTS `cfk_woocommerce_sessions`;
DROP TABLE IF EXISTS `cfk_woocommerce_shipping_zone_locations`;
DROP TABLE IF EXISTS `cfk_woocommerce_shipping_zone_methods`;
DROP TABLE IF EXISTS `cfk_woocommerce_shipping_zones`;
DROP TABLE IF EXISTS `cfk_woocommerce_tax_rate_locations`;
DROP TABLE IF EXISTS `cfk_woocommerce_tax_rates`;
DROP TABLE IF EXISTS `cfk_wpmailsmtp_debug_events`;
DROP TABLE IF EXISTS `cfk_wpmailsmtp_tasks_meta`;
DROP TABLE IF EXISTS `cfk_wpml_mails`;

-- WordPress core tables
DROP TABLE IF EXISTS `wp_commentmeta`;
DROP TABLE IF EXISTS `wp_comments`;
DROP TABLE IF EXISTS `wp_links`;
DROP TABLE IF EXISTS `wp_options`;
DROP TABLE IF EXISTS `wp_postmeta`;
DROP TABLE IF EXISTS `wp_posts`;
DROP TABLE IF EXISTS `wp_term_relationships`;
DROP TABLE IF EXISTS `wp_term_taxonomy`;
DROP TABLE IF EXISTS `wp_termmeta`;
DROP TABLE IF EXISTS `wp_terms`;
DROP TABLE IF EXISTS `wp_usermeta`;
DROP TABLE IF EXISTS `wp_users`;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Done!
SELECT 'WordPress tables removed successfully!' as status;
