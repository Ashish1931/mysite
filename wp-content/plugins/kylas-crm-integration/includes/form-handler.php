<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Kylas_CRM_Form_Handler {

    /**
     * Constructor
     * Using safer hook to avoid duplicate firing
     */
    public function __construct() {
        // Fires once per submission (more reliable than mail_sent)
        add_action( 'wpcf7_before_send_mail', array( $this, 'handle_cf7_submission' ), 10, 1 );
    }

    /**
     * Handle CF7 Submission
     */
    public function handle_cf7_submission( $contact_form ) {

        // Prevent duplicate execution within same request
        static $already_processed = false;
        if ( $already_processed ) {
            return;
        }
        $already_processed = true;

        if ( ! class_exists( 'WPCF7_Submission' ) ) {
            return;
        }

        $submission = WPCF7_Submission::get_instance();
        
        if ( ! $submission ) {
            return;
        }

        $form_id     = $contact_form->id();
        $posted_data = $submission->get_posted_data();

        if ( empty( $posted_data ) || ! is_array( $posted_data ) ) {
            return;
        }

        // 1. Fetch Saved Mapping
        global $wpdb;
        $mappings_table = $wpdb->prefix . 'kylas_field_mappings';
        $mapping_json = $wpdb->get_var($wpdb->prepare("SELECT mapping_json FROM $mappings_table WHERE form_id = %d", $form_id));
        
        if (!$mapping_json) {
            error_log('Kylas CRM: No field mapping found for form ID ' . $form_id);
            return;
        }

        $mapping = json_decode($mapping_json, true);
        if (empty($mapping)) {
            return;
        }

        // 2. Prepare CRM Payload based on mapping
        $kylas_payload = array();
        foreach ($mapping as $cf7_field => $kylas_field) {
            if (empty($kylas_field) || !isset($posted_data[$cf7_field])) {
                continue;
            }

            $value = sanitize_text_field($posted_data[$cf7_field]);

            switch ($kylas_field) {
                case 'email':
                    $kylas_payload['emails'] = array(
                        array("type" => "OFFICE", "value" => $value, "primary" => true)
                    );
                    break;
                case 'phone':
                    $clean_phone = preg_replace('/[^0-9]/', '', $value);
                    if (strlen($clean_phone) > 10) {
                        $clean_phone = substr($clean_phone, -10);
                    }
                    $kylas_payload['phoneNumbers'] = array(
                        array(
                            "type"     => "MOBILE", 
                            "code"     => "IN",
                            "value"    => $clean_phone, 
                            "dialCode" => "+91", 
                            "primary"  => true
                        )
                    );
                    break;
                case 'requirement':
                    $kylas_payload['notes'] = array(
                        array("content" => $value)
                    );
                    break;
                default:
                    $kylas_payload[$kylas_field] = $value;
                    break;
            }
        }

        // Standard fallback for local storage display (can be optimized)
        $first_name = isset($kylas_payload['firstName']) ? $kylas_payload['firstName'] : '';
        $last_name = isset($kylas_payload['lastName']) ? $kylas_payload['lastName'] : '';
        $email = isset($kylas_payload['emails'][0]['value']) ? $kylas_payload['emails'][0]['value'] : '';
        $phone = isset($kylas_payload['phoneNumbers'][0]['value']) ? $kylas_payload['phoneNumbers'][0]['value'] : '';

        // 3. Save locally first
        $lead_id = $this->save_lead_locally( 'cf7', $form_id, $posted_data, $first_name, $last_name, $email, $phone );

        // 4. Send to API
        $response = $this->send_to_kylas( $kylas_payload );

        // 5. Update local lead with response
        if ( $lead_id ) {
            $this->update_lead_status( $lead_id, $response );

            // 6. Send Notifications if successful
            if ( ! is_wp_error( $response ) ) {
                $code = wp_remote_retrieve_response_code( $response );
                if ( $code >= 200 && $code < 300 ) {
                    $this->send_notifications( $first_name, $last_name, $email );
                }
            }
        }
    }

    /**
     * Send Email Notifications
     */
    private function send_notifications( $first_name, $last_name, $lead_email ) {
        $notify_admin = get_option( 'kylas_crm_notify_admin', 'no' );
        $notify_lead  = get_option( 'kylas_crm_notify_lead', 'no' );
        $full_name    = trim( $first_name . ' ' . $last_name );

        // 1. Notify Admin
        if ( 'yes' === $notify_admin ) {
            $admin_email = get_option( 'admin_email' );
            $subject     = 'New Lead Created in Kylas CRM';
            $message     = "A new lead has been successfully registered in Kylas CRM.\n\n";
            $message    .= "Name: $full_name\n";
            $message    .= "Email: $lead_email\n";
            $message    .= "Date: " . current_time( 'mysql' ) . "\n";
            
            wp_mail( $admin_email, $subject, $message );
        }

        // 2. Notify Lead
        if ( 'yes' === $notify_lead && ! empty( $lead_email ) ) {
            $subject = 'Registration Successful';
            $message = "Hello $first_name,\n\n";
            $message .= "Thank you for reaching out! We have successfully received your information and registered you in our CRM.\n\n";
            $message .= "Our team will get back to you shortly.\n\n";
            $message .= "Best regards,\n" . get_bloginfo( 'name' );

            wp_mail( $lead_email, $subject, $message );
        }
    }

    /**
     * Save lead data locally
     */
    private function save_lead_locally( $form_type, $form_id, $data, $first_name, $last_name, $email, $phone ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_leads';

        $wpdb->insert(
            $table_name,
            array(
                'form_type'  => $form_type,
                'form_id'    => $form_id,
                'first_name' => $first_name,
                'last_name'  => $last_name,
                'email'      => $email,
                'phone'      => $phone,
                'form_data'  => wp_json_encode( $data ),
                'status'     => 'pending',
                'created_at' => current_time( 'mysql' )
            )
        );

        return $wpdb->insert_id;
    }

    /**
     * Update lead status after API call
     */
    private function update_lead_status( $lead_id, $response ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf7_leads';

        $status = 'failed';
        $code   = 0;
        $body   = '';

        if ( ! is_wp_error( $response ) ) {
            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            if ( $code >= 200 && $code < 300 ) {
                $status = 'success';
            }
        } else {
            $body = $response->get_error_message();
        }

        $wpdb->update(
            $table_name,
            array(
                'status'        => $status,
                'response_code' => $code,
                'response_body' => $body
            ),
            array( 'id' => $lead_id )
        );
    }

    /**
     * Send Data to Kylas CRM
     */
    private function send_to_kylas( $data ) {

        $api_key = get_option( 'kylas_crm_api_key' );
        $base_url = get_option( 'kylas_crm_base_url', 'https://api.kylas.io/v1/' );

        if ( empty( $api_key ) ) {
            error_log( 'Kylas Integration Error: Missing API Key.' );
            return new WP_Error( 'missing_api_key', 'Missing API Key' );
        }

        $endpoint = rtrim($base_url, '/') . '/leads';

        $args = array(
            'body'        => wp_json_encode( $data ),
            'headers'     => array(
                'Content-Type' => 'application/json',
                'api-key'      => $api_key,
            ),
            'timeout'     => 45,
            'blocking'    => true,
        );

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            error_log( 'Kylas Integration Error: ' . $response->get_error_message() );
        } else {
            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );

            if ( $code >= 400 ) {
                error_log( 'Kylas API Error (' . $code . '): ' . $body );
            }
        }

        return $response;
    }
}