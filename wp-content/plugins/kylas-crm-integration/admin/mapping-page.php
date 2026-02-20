<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once KYLAS_CRM_PLUGIN_DIR . 'includes/class-kylas-helper.php';

// Handle Save Logic
if (isset($_POST['kylas_save_mapping']) && check_admin_referer('kylas_save_mapping_nonce', 'kylas_mapping_nonce')) {
    $form_type = sanitize_text_field($_POST['form_type']);
    $form_id = intval($_POST['form_id']);
    $mapping = isset($_POST['mapping']) ? $_POST['mapping'] : array();

    // Sanitize mapping array
    $sanitized_mapping = array();
    foreach ($mapping as $cf7_field => $kylas_field) {
        $sanitized_mapping[sanitize_text_field($cf7_field)] = sanitize_text_field($kylas_field);
    }

    Kylas_CRM_Helper::save_mapping($form_type, $form_id, $sanitized_mapping);
    echo '<div class="notice notice-success is-dismissible"><p>Mapping saved successfully!</p></div>';
}

// Handle Delete Logic
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && check_admin_referer('delete_mapping_' . $_GET['id'])) {
    Kylas_CRM_Helper::delete_mapping(intval($_GET['id']));
    echo '<div class="notice notice-success is-dismissible"><p>Mapping deleted.</p></div>';
}

$cf7_forms = Kylas_CRM_Helper::get_cf7_forms();
$kylas_fields = Kylas_CRM_Helper::get_kylas_fields();

// If API Key is missing or invalid, showing an error might be appropriate, but let's just proceed.
// If $kylas_fields is WP_Error, handle it.
$kylas_error = '';
if (is_wp_error($kylas_fields)) {
    $kylas_error = $kylas_fields->get_error_message();
    $kylas_fields = array(); // Empty array to avoid warnings
}

$selected_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$current_mapping = array();
if ($selected_form_id) {
    $saved_data = Kylas_CRM_Helper::get_mapping('cf7', $selected_form_id);
    if ($saved_data) {
        $current_mapping = json_decode($saved_data->mapping_json, true);
    }
}
?>

<div class="wrap">
    <h1>Field Mapping</h1>
    
    <?php if ($kylas_error): ?>
        <div class="notice notice-error"><p>Error fetching Kylas fields: <?php echo esc_html($kylas_error); ?></p></div>
    <?php endif; ?>

    <!-- Step 1 & 2: Select Form Type & Form -->
    <div class="card" style="padding: 20px; margin-bottom: 20px;">
        <h2>Select Form</h2>
        <form method="get" action="">
            <input type="hidden" name="page" value="kylas-crm-mapping" />
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="form_type">Form Type</label></th>
                    <td>
                        <select name="form_type" id="form_type">
                            <option value="cf7">Contact Form 7</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="form_id">Select Form</label></th>
                    <td>
                        <select name="form_id" id="form_id" onchange="this.form.submit()">
                            <option value="">-- Select a Form --</option>
                            <?php foreach ($cf7_forms as $form): ?>
                                <option value="<?php echo esc_attr($form->id()); ?>" <?php selected($selected_form_id, $form->id()); ?>>
                                    <?php echo esc_html($form->title()); ?> (ID: <?php echo esc_attr($form->id()); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </form>
    </div>

    <!-- Step 3: Mapping Panel -->
    <?php if ($selected_form_id): 
        $form_fields = Kylas_CRM_Helper::get_cf7_form_fields($selected_form_id);
    ?>
    <div class="card" style="padding: 20px; margin-bottom: 20px;">
        <h2>Map Fields for Form ID: <?php echo $selected_form_id; ?></h2>
        <form method="post" action="">
            <?php wp_nonce_field('kylas_save_mapping_nonce', 'kylas_mapping_nonce'); ?>
            <input type="hidden" name="form_type" value="cf7" />
            <input type="hidden" name="form_id" value="<?php echo esc_attr($selected_form_id); ?>" />

            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th style="width: 50%;"><strong>Contact Form 7 Field</strong></th>
                        <th style="width: 50%;"><strong>Kylas Field</strong></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($form_fields)): ?>
                        <tr><td colspan="2">No fields found in this form.</td></tr>
                    <?php else: ?>
                        <?php foreach ($form_fields as $field): ?>
                            <tr>
                                <td>
                                    <code><?php echo esc_html($field['name']); ?></code> 
                                    <span class="description">(<?php echo esc_html($field['type']); ?>)</span>
                                </td>
                                <td>
                                    <select name="mapping[<?php echo esc_attr($field['name']); ?>]" style="width: 100%;">
                                        <option value="">-- Ignore --</option>
                                        <?php foreach ($kylas_fields as $k_field): ?>
                                            <?php 
                                            // Handle varying structure based on API response
                                            $k_val = is_array($k_field) ? $k_field['name'] : $k_field->name;
                                            $k_label = is_array($k_field) ? $k_field['label'] : $k_field->label;
                                            
                                            $selected = isset($current_mapping[$field['name']]) && $current_mapping[$field['name']] === $k_val ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo esc_attr($k_val); ?>" <?php echo $selected; ?>>
                                                <?php echo esc_html($k_label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <p class="submit">
                <input type="submit" name="kylas_save_mapping" class="button button-primary" value="Save Mapping" />
            </p>
        </form>
    </div>
    <?php endif; ?>

    <!-- Step 4: View Saved Mappings -->
    <div class="card" style="padding: 20px;">
        <h2>Saved Mappings</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Form Type</th>
                    <th>Form ID</th>
                    <th>Mapped Fields</th>
                    <th>Last Updated</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                global $wpdb;
                $table_name = $wpdb->prefix . 'kylas_field_mappings';
                $saved_mappings = $wpdb->get_results("SELECT * FROM $table_name ORDER BY updated_at DESC"); // Using direct query instead of helper because helper get_all_mappings was static but inside class context here works better with object or just call static
                
                // Re-using helper static method if defined public
                if (method_exists('Kylas_CRM_Helper', 'get_all_mappings')) {
                    $saved_mappings = Kylas_CRM_Helper::get_all_mappings();
                }

                if ($saved_mappings):
                    foreach ($saved_mappings as $mapping):
                        $mapping_count = count(json_decode($mapping->mapping_json, true));
                        $edit_url = admin_url('admin.php?page=kylas-crm-mapping&form_type=' . $mapping->form_type . '&form_id=' . $mapping->form_id);
                        $delete_url = wp_nonce_url(admin_url('admin.php?page=kylas-crm-mapping&action=delete&id=' . $mapping->id), 'delete_mapping_' . $mapping->id);
                        ?>
                        <tr>
                            <td><?php echo esc_html(strtoupper($mapping->form_type)); ?></td>
                            <td><?php echo esc_html($mapping->form_id); ?></td>
                            <td><?php echo intval($mapping_count); ?></td>
                            <td><?php echo esc_html($mapping->updated_at); ?></td>
                            <td>
                                <a href="<?php echo esc_url($edit_url); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo esc_url($delete_url); ?>" class="button button-small button-link-delete" onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach;
                else: ?>
                    <tr><td colspan="5">No saved mappings found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
