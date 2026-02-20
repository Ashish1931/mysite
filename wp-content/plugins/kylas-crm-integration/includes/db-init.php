<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function kylas_crm_create_tables() {
    global $wpdb;

    $leads_table = $wpdb->prefix . 'cf7_leads';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $leads_table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        form_type varchar(50) NOT NULL,
        form_id bigint(20) NOT NULL,
        first_name varchar(100) DEFAULT '',
        last_name varchar(100) DEFAULT '',
        email varchar(150) DEFAULT '',
        phone varchar(20) DEFAULT '',
        form_data longtext NOT NULL,
        status varchar(50) DEFAULT 'unprocessed',
        response_code int(10) DEFAULT NULL,
        response_body longtext DEFAULT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
