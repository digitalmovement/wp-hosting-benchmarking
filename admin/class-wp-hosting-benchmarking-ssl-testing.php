<?php

class Wp_Hosting_Benchmarking_SSL_Testing {

    private $db;
    private $api;

    public function __construct($db, $api) {
        $this->db = $db;
        $this->api = $api;
    }


    // Display the SSL testing page
       // Display the SSL testing or registration page
       public function display_ssl_testing_page() {
        // Check if the user is already registered
        $registered_user = get_option('wp_hosting_benchmarking_registered_user');
        
        // Include the HTML + JS from the display file
        include plugin_dir_path(__FILE__) . 'wp-hosting-benchmarking-ssl-testing-display.php';
    }

    // Handle user registration via Ajax
    public function handle_ssl_registration() {
        check_ajax_referer('ssl_registration_nonce', 'nonce');

        parse_str($_POST['form_data'], $form_data);

        $email = sanitize_email($form_data['email']);
        $disallowed_domains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
        $email_domain = explode('@', $email)[1];

        // Validate email domain
        if (in_array($email_domain, $disallowed_domains)) {
            wp_send_json_error('Email addresses from Gmail, Yahoo, Outlook, or Hotmail are not allowed.');
        }

        // Save registration data
        $user_data = array(
            'first_name' => sanitize_text_field($form_data['first_name']),
            'last_name' => sanitize_text_field($form_data['last_name']),
            'email' => $email,
            'organization' => sanitize_text_field($form_data['organization']),
        );

        // Store user data in the options table
        update_option('wp_hosting_benchmarking_registered_user', $user_data);

        wp_send_json_success();
    }

    // Handle SSL testing via Ajax
    public function handle_ssl_testing() {
        check_ajax_referer('ssl_testing_nonce', 'nonce');

        $api = new Wp_Hosting_Benchmarking_API();
        $result = $api->test_ssl_certificate(home_url());

        if ($result) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error('SSL testing failed.');
        }
    }
} // end of class




