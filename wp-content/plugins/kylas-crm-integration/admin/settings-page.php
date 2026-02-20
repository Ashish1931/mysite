<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kylas_crm_settings_nonce']) && wp_verify_nonce($_POST['kylas_crm_settings_nonce'], 'kylas_crm_save_settings')) {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized user' );
    }

    $api_key = sanitize_text_field($_POST['kylas_crm_api_key']);
    update_option('kylas_crm_api_key', $api_key);

    echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
}

$api_key = get_option('kylas_crm_api_key', '');
?>

<div class="wrap">
    <h1>Kylas CRM Settings</h1>
    <form method="post" action="">
        <?php wp_nonce_field('kylas_crm_save_settings', 'kylas_crm_settings_nonce'); ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">API Key</th>
                <td>
                    <input type="password" name="kylas_crm_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                    <p class="description">Enter your Kylas CRM API Key.</p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
