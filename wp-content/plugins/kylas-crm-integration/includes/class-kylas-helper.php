<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Kylas_CRM_Helper
 *
 * Handles:
 * - Fetching CF7 forms & fields
 * - Providing Kylas Lead fields (static + future dynamic)
 * - Saving & retrieving field mappings
 */
class Kylas_CRM_Helper {

    /**
     * Fetch all Contact Form 7 forms.
     *
     * @return array
     */
    public static function get_cf7_forms() {
        if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
            return array();
        }

        return WPCF7_ContactForm::find();
    }

    /**
     * Get fields from a specific CF7 form.
     *
     * @param int $form_id
     * @return array
     */
    public static function get_cf7_form_fields( $form_id ) {
        if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
            return array();
        }

        $form = WPCF7_ContactForm::get_instance( $form_id );
        if ( ! $form ) {
            return array();
        }

        $manager = WPCF7_FormTagsManager::get_instance();
        $scan = $manager->scan( $form->prop( 'form' ) );

        $fields = array();

        foreach ( $scan as $tag ) {
            if ( ! empty( $tag->name ) && $tag->type != 'submit' ) {
                $fields[] = array(
                    'name' => $tag->name,
                    'type' => $tag->type
                );
            }
        }

        return $fields;
    }

    /**
     * Get Kylas Lead Fields
     *
     * Architecture:
     * - Today: Uses STATIC predefined fields (stable & working)
     * - Future: Can switch to dynamic API fields automatically
     *
     * @return array
     */
    public static function get_kylas_fields() {

        /**
         * Filter to enable dynamic fields in future
         * Example usage later:
         * add_filter('kylas_use_dynamic_fields', '__return_true');
         */
        $use_dynamic = apply_filters( 'kylas_use_dynamic_fields', false );

        // If dynamic enabled → try fetching from API
        if ( $use_dynamic ) {
            $api_fields = self::fetch_fields_from_api();

            // If API works, return dynamic fields
            if ( ! is_wp_error( $api_fields ) && ! empty( $api_fields ) ) {
                return $api_fields;
            }
        }

        // Fallback: return static predefined fields
        return self::get_static_fields();
    }

    /**
     * STATIC FIELD LIST (Current Working Solution)
     *
     * These are core Kylas Lead API fields.
     * Stable and guaranteed to work.
     *
     * @return array
     */
    private static function get_static_fields() {
        return array(
            array(
                'name'  => 'firstName',
                'label' => 'First Name',
                'type'  => 'text'
            ),
            array(
                'name'  => 'lastName',
                'label' => 'Last Name',
                'type'  => 'text'
            ),
            array(
                'name'  => 'emails',
                'label' => 'Email',
                'type'  => 'email'
            ),
            array(
                'name'  => 'phoneNumbers',
                'label' => 'Phone',
                'type'  => 'phone'
            ),
            array(
                'name'  => 'companyName',
                'label' => 'Company Name',
                'type'  => 'text'
            ),
            array(
                'name'  => 'source',
                'label' => 'Lead Source',
                'type'  => 'text'
            ),
            array(
                'name'  => 'campaign',
                'label' => 'Campaign',
                'type'  => 'text'
            ),
            array(
                'name'  => 'owner',
                'label' => 'Owner',
                'type'  => 'text'
            ),
            array(
                'name'  => 'notes',
                'label' => 'Notes',
                'type'  => 'textarea'
            ),
        );
    }

    /**
     * FUTURE: Fetch Fields Dynamically from Kylas API
     *
     * NOTE:
     * Currently Kylas does not provide public metadata endpoint.
     * When they release one, just update the endpoint below.
     *
     * @return array|WP_Error
     */
    private static function fetch_fields_from_api() {

        $api_key = get_option( 'kylas_crm_api_key' );
        if ( empty( $api_key ) ) {
            return new WP_Error( 'no_api_key', 'Kylas API key missing.' );
        }

        // Future endpoint (update when available)
        $endpoint = 'https://api.kylas.io/v1/leadFields';

        $response = wp_remote_get( $endpoint, array(
            'headers' => array(
                'api-key' => $api_key,
                'Accept'  => 'application/json'
            ),
            'timeout' => 15
        ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return new WP_Error( 'api_error', 'Dynamic API failed with code: ' . $code );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( ! is_array( $data ) ) {
            return new WP_Error( 'invalid_response', 'Invalid API response format.' );
        }

        return $data;
    }

    /**
     * Save Field Mapping to Database
     */
    public static function save_mapping( $form_type, $form_id, $mapping_data ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kylas_field_mappings';

        $data = array(
            'form_type'    => $form_type,
            'form_id'      => $form_id,
            'mapping_json' => json_encode( $mapping_data ),
            'updated_at'   => current_time( 'mysql' )
        );

        // Check if mapping exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE form_type = %s AND form_id = %d",
                $form_type,
                $form_id
            )
        );

        if ( $existing ) {
            $wpdb->update( $table_name, $data, array( 'id' => $existing->id ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_name, $data );
        }
    }

    /**
     * Get Saved Mapping
     */
    public static function get_mapping( $form_type, $form_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kylas_field_mappings';

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE form_type = %s AND form_id = %d",
                $form_type,
                $form_id
            )
        );
    }

    /**
     * Get All Saved Mappings
     */
    public static function get_all_mappings() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kylas_field_mappings';
        return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY updated_at DESC" );
    }

    /**
     * Delete Mapping
     */
    public static function delete_mapping( $id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kylas_field_mappings';
        $wpdb->delete( $table_name, array( 'id' => $id ) );
    }
}

