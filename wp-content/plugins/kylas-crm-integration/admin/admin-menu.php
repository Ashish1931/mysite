<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Kylas_CRM_Admin_Menu {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts') );
    }

    public function enqueue_scripts( $hook ) {
        // Only load scripts/styles on our plugin pages
        if ( ! strpos( $hook, 'kylas-crm' ) ) {
            return;
        }

        wp_enqueue_script( 'jquery' ); 
        
        // Inline script for tab handling or simple JS logic
        // For the field mapping logic, you'll eventually want a dedicated JS file.
    }

    public function add_menu_page() {
        add_menu_page(
            'Kylas CRM',
            'Kylas CRM',
            'manage_options',
            'kylas-crm',
            array($this, 'render_settings_page'),
            'dashicons-chart-bar',
            6
        );

        add_submenu_page(
            'kylas-crm',
            'Settings',
            'Settings',
            'manage_options',
            'kylas-crm',
            array($this, 'render_settings_page')
        );

    }

    public function render_settings_page() {
        // Separate file logic for cleaner code
        require_once KYLAS_CRM_PLUGIN_DIR . 'admin/settings-page.php';
    }
}
