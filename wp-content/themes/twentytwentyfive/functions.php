<?php
/**
 * Twenty Twenty-Five functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Twenty-Five 1.0
 */
// Adds theme support for post formats.
if ( ! function_exists( 'twentytwentyfive_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_post_format_setup' );
// Enqueues editor-style.css in the editors.
if ( ! function_exists( 'twentytwentyfive_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_editor_style() {
		add_editor_style( 'assets/css/editor-style.css' );
	}
endif;
add_action( 'after_setup_theme', 'twentytwentyfive_editor_style' );
// Enqueues the theme stylesheet on the front.
if ( ! function_exists( 'twentytwentyfive_enqueue_styles' ) ) :
	/**
	 * Enqueues the theme stylesheet on the front.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_enqueue_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$src    = 'style' . $suffix . '.css';
		wp_enqueue_style(
			'twentytwentyfive-style',
			get_parent_theme_file_uri( $src ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
		wp_style_add_data(
			'twentytwentyfive-style',
			'path',
			get_parent_theme_file_path( $src )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'twentytwentyfive_enqueue_styles' );
// Registers custom block styles.
if ( ! function_exists( 'twentytwentyfive_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'twentytwentyfive' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}
				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_block_styles' );
// Registers pattern categories.
if ( ! function_exists( 'twentytwentyfive_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_pattern_categories() {
		register_block_pattern_category(
			'twentytwentyfive_page',
			array(
				'label'       => __( 'Pages', 'twentytwentyfive' ),
				'description' => __( 'A collection of full page layouts.', 'twentytwentyfive' ),
			)
		);
		register_block_pattern_category(
			'twentytwentyfive_post-format',
			array(
				'label'       => __( 'Post formats', 'twentytwentyfive' ),
				'description' => __( 'A collection of post format patterns.', 'twentytwentyfive' ),
			)
		);
	}
endif;
add_action( 'init', 'twentytwentyfive_pattern_categories' );
// Registers block binding sources.
if ( ! function_exists( 'twentytwentyfive_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Twenty-Five 1.0
	 *
	 * @return void
	 */
	function twentytwentyfive_register_block_bindings() {
		register_block_bindings_source(
			'twentytwentyfive/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'twentytwentyfive' ),
				'get_value_callback' => 'twentytwentyfive_format_binding',
			)
		);
	}
endif;
add_action('wpcf7_mail_sent', 'send_lead_to_kylas_crm');

function send_lead_to_kylas_crm($contact_form) {

    $submission = WPCF7_Submission::get_instance();
    if (!$submission) return;

    $data = $submission->get_posted_data();

    $first_name = isset($data['first_name']) ? sanitize_text_field($data['first_name']) : '';
    $last_name  = isset($data['last_name']) ? sanitize_text_field($data['last_name']) : '';
    $email      = isset($data['email']) ? sanitize_email($data['email']) : '';
    $phone      = isset($data['phone']) ? sanitize_text_field($data['phone']) : '';

	/* -----------------------------
     * 1. STORE INTO DATABASE
     * ----------------------------- */
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_leads';

    $wpdb->insert($table_name, array(
        'first_name' => $first_name,
        'last_name'  => $last_name,
        'email'      => $email,
        'phone'      => $phone
    ));


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
        "country" => "IN"
        // "source"  => "Website"
    );

    // $response = wp_remote_post('https://api.kylas.io/v1/leads', array(
    //     'method'  => 'POST',
    //     'headers' => array(
    //         'Content-Type'  => 'application/json',
    //         'api-key' => KYLAS_API_TOKEN
    //     ),
    //     'body'    => json_encode($body),
    //     'timeout' => 20
    // ));

	$curl = curl_init();

	curl_setopt_array($curl, array(
	CURLOPT_URL => 'https://api.kylas.io/v1/leads',
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_TIMEOUT => 0,
	CURLOPT_FOLLOWLOCATION => true,
	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
	CURLOPT_CUSTOMREQUEST => 'POST',
	CURLOPT_POSTFIELDS => json_encode($body),
	CURLOPT_HTTPHEADER => array(
		'Content-Type: application/json; charset=utf-8',
		'Accept: application/json',
		'api-key:' . KYLAS_API_TOKEN
	),
	));

	$response = curl_exec($curl);

	curl_close($curl);


	print_r(json_encode($body)); exit;
	// print_r(json_decode($response)); exit;

    if (is_wp_error($response)) {
        error_log('CRM API Error: ' . $response->get_error_message());
    } else {
        error_log('CRM Status: ' . wp_remote_retrieve_response_code($response));
        error_log('CRM Response: ' . wp_remote_retrieve_body($response));
    }
}


function create_leads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cf7_leads';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        email VARCHAR(150),
        phone VARCHAR(20),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'create_leads_table');



