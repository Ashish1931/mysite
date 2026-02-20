<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Kylas_CRM_Form_Handler {

    public function __construct() {
        // CF7 Hook
        add_action( 'wpcf7_mail_sent', array( $this, 'handle_cf7_submission' ) );
    }

    /**
     * Handle CF7 Submission
     */
    public function handle_cf7_submission( $contact_form ) {

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

        // Load Helper if not loaded
        if ( ! class_exists( 'Kylas_CRM_Helper' ) ) {
            require_once KYLAS_CRM_PLUGIN_DIR . 'includes/class-kylas-helper.php';
        }

        // 1. Get Mapping
        $mapping_row = Kylas_CRM_Helper::get_mapping( 'cf7', $form_id );

        if ( ! $mapping_row ) {
            return; // No mapping saved
        }

        $mapping = json_decode( $mapping_row->mapping_json, true );

        if ( empty( $mapping ) || ! is_array( $mapping ) ) {
            return;
        }

        // 2. Prepare Payload
        $kylas_payload = array();
        $emails = array();
        $phones = array();

        foreach ( $mapping as $cf7_field => $kylas_field ) {

            if ( empty( $kylas_field ) ) {
                continue;
            }

            // Skip if field not submitted
            if ( ! isset( $posted_data[ $cf7_field ] ) ) {
                continue;
            }

            $value = $posted_data[ $cf7_field ];

            if ( is_array( $value ) ) {
                $value = implode( ', ', $value );
            }

            $value = sanitize_text_field( $value );

            if ( empty( $value ) ) {
                continue;
            }

            /**
             * Email Mapping
             */
            if ( stripos( $kylas_field, 'email' ) !== false ) {
                $emails[] = array(
                    "type"    => "OFFICE",
                    "value"   => sanitize_email( $value ),
                    "primary" => true
                );
            }

            /**
             * Phone Mapping
             */
            elseif ( stripos( $kylas_field, 'phone' ) !== false || stripos( $kylas_field, 'mobile' ) !== false ) {

                $clean_phone = preg_replace( '/[^0-9]/', '', $value );

                if ( ! empty( $clean_phone ) ) {
                    $phones[] = array(
                        "type"     => "MOBILE",
                        "code"     => "IN",
                        "value"    => $clean_phone,
                        "dialCode" => "+91",
                        "primary"  => true
                    );
                }
            }

            /**
             * Regular Fields
             */
            else {
                $kylas_payload[ $kylas_field ] = $value;
            }
        }

        // Attach Emails
        if ( ! empty( $emails ) ) {
            $kylas_payload['emails'] = $emails;
        }

        // Attach Phones
        if ( ! empty( $phones ) ) {
            $kylas_payload['phoneNumbers'] = $phones;
        }

        if ( empty( $kylas_payload ) ) {
            return;
        }

        // 3. Send to Kylas API
        $this->send_to_kylas( $kylas_payload );
    }

    /**
     * Send Data to Kylas CRM
     */
    private function send_to_kylas( $data ) {

        $api_key = get_option( 'kylas_crm_api_key' );

        if ( empty( $api_key ) ) {
            error_log( 'Kylas Integration Error: Missing API Key.' );
            return;
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
            return;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code >= 400 ) {
            error_log( 'Kylas API Error (' . $code . '): ' . $body );
        } else {
            // Optional success log
            // error_log( 'Kylas Lead Created: ' . $body );
        }
    }
}