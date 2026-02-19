<?php
function ashish_theme_setup() {
    add_theme_support('title-tag');
}
add_action('after_setup_theme', 'ashish_theme_setup');

// Hook when CF7 form successfully submitted
add_action('wpcf7_mail_sent', 'send_lead_to_kylas_crm');

function send_lead_to_kylas_crm($contact_form) {

    // Get submitted data
    $submission = WPCF7_Submission::get_instance();
    if (!$submission) {
        return;
    }

    $data = $submission->get_posted_data();

    // Safely fetch fields
    $first_name = isset($data['first_name']) ? sanitize_text_field($data['first_name']) : '';
    $last_name  = isset($data['last_name']) ? sanitize_text_field($data['last_name']) : '';
    $email      = isset($data['email']) ? sanitize_email($data['email']) : '';
    $phone      = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';

   $body = array(
    "firstName" => $first_name,
    "lastName"  => $last_name,

    "phoneNumbers" => array(
        array(
            "type"     => "MOBILE",
            "code"     => "IN",
            "value"    => $phone,
            "dialCode" => "+91",
            "primary"  => true
        )
    ),

    "emails" => array(
        array(
            "type"    => "OFFICE",
            "value"   => $email,
            "primary" => true
        )
    ),

    "country" => "IN",
    "source"  => "Website"
);


    //  Kylas API endpoint 

    $api_url = 'https://api.kylas.io/v1/leads';

    $response = wp_remote_post($api_url, array(
        'method'  => 'POST',
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . KYLAS_API_TOKEN

        ),
        'body'    => json_encode($body),
        'timeout' => 20
    ));
// Debug logs
    if (is_wp_error($response)) {
        error_log('CRM API Error: ' . $response->get_error_message());
    } else {
        error_log('CRM API Response: ' . wp_remote_retrieve_body($response));
    }
}
