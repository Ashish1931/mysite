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



        // 1. Extract standard fields
        $first_name = isset( $posted_data['first_name'] ) ? sanitize_text_field( $posted_data['first_name'] ) : '';
        $last_name  = isset( $posted_data['last_name'] ) ? sanitize_text_field( $posted_data['last_name'] ) : '';
        $email      = isset( $posted_data['email'] ) ? sanitize_email( $posted_data['email'] ) : '';
        $phone      = isset( $posted_data['phone'] ) ? sanitize_text_field( $posted_data['phone'] ) : '';

        // 2. Prepare CRM Payload
        $kylas_payload = array(
            'firstName' => $first_name,
            'lastName'  => $last_name,
        );

        if ( ! empty( $email ) ) {
            $kylas_payload['emails'] = array(
                array(
                    "type"    => "OFFICE",
                    "value"   => $email,
                    "primary" => true
                )
            );
        }

        if ( ! empty( $phone ) ) {
            $clean_phone = preg_replace( '/[^0-9]/', '', $phone );
            if ( ! empty( $clean_phone ) ) {
                $kylas_payload['phoneNumbers'] = array(
                    array(
                        "type"     => "MOBILE",
                        "code"     => "IN",
                        "value"    => $clean_phone,
                        "dialCode" => "+91",
                        "primary"  => true
                    )
                );
            }
        }

        // 3. Save locally first
        $lead_id = $this->save_lead_locally( 'cf7', $form_id, $posted_data, $first_name, $last_name, $email, $phone );

        // 4. Send to API
        $response = $this->send_to_kylas( $kylas_payload );

        // 5. Update local lead with response
        if ( $lead_id ) {
            $this->update_lead_status( $lead_id, $response );
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

        if ( empty( $api_key ) ) {
            error_log( 'Kylas Integration Error: Missing API Key.' );
            return new WP_Error( 'missing_api_key', 'Missing API Key' );
        }

        $endpoint = 'https://api.kylas.io/v1/leads';

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