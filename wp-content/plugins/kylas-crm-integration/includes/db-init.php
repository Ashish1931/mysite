<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kylas_crm_create_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'kylas_field_mappings';

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name ) {
        return; // already exists
    }

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_type varchar(50) NOT NULL,
        form_id bigint(20) NOT NULL,
        mapping_json longtext NOT NULL,
        created_at datetime NOT NULL,
        updated_at datetime NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}