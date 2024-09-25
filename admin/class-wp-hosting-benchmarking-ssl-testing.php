<?php

class Wp_Hosting_Benchmarking_SSL_Testing {

    private $db;
    private $api;

    public function __construct($db, $api) {
        $this->db = $db;
        $this->api = $api;
    }


    // Display the SSL testing page
    public function display_ssl_testing_page() {
        // Check if user is registered
        $user_data = get_option('wp_hosting_benchmarking_registered_user');

        if (!$user_data) {
            $this->display_registration_form();
        } else {
            $this->display_ssl_testing_form();
        }
    }

    // Display the registration form
    private function display_registration_form() {
        $current_user = wp_get_current_user();
        $user_email = $current_user->user_email;
        ?>
        <h1>Register for SSL Testing</h1>
        <form id="ssl-registration-form">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required><br>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo esc_attr($user_email); ?>" required><br>

            <label for="organization">Organization:</label>
            <input type="text" id="organization" name="organization" required><br>

            <input type="submit" id="register-button" class="button button-primary" value="Register">
        </form>
        <p id="email-error" style="color: red;"></p>

        <script>
        jQuery(document).ready(function($) {
            $('#ssl-registration-form').on('submit', function(event) {
                event.preventDefault();

                var email = $('#email').val();
                var invalidDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
                var emailDomain = email.split('@')[1];

                // Check if email is from a disallowed domain
                if (invalidDomains.includes(emailDomain)) {
                    $('#email-error').text('Email addresses from Gmail, Yahoo, Outlook, or Hotmail are not allowed.');
                    return;
                }

                var formData = $(this).serialize();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ssl_registration',
                        form_data: formData,
                        nonce: '<?php echo wp_create_nonce('ssl_registration_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload(); // Reload the page after successful registration
                        } else {
                            $('#email-error').text(response.data);
                        }
                    }
                });
            });
        });
        </script>
        <?php
    }

    // Display the SSL testing form
    private function display_ssl_testing_form() {
        ?>
        <h1>SSL Testing</h1>
        <form id="ssl-testing-form">
            <input type="submit" id="test-ssl-button" class="button button-primary" value="Test SSL">
            <p id="test-status"></p>
        </form>

        <script>
        jQuery(document).ready(function($) {
            $('#ssl-testing-form').on('submit', function(event) {
                event.preventDefault();

                $('#test-status').text('Testing SSL, please wait...');
                $('#test-ssl-button').prop('disabled', true);

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'ssl_testing',
                        nonce: '<?php echo wp_create_nonce('ssl_testing_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#test-status').html('<strong>SSL Test Result:</strong><br>' + response.data);
                        } else {
                            $('#test-status').text('Error testing SSL.');
                        }
                        $('#test-ssl-button').prop('disabled', false); // Re-enable button
                    }
                });
            });
        });
        </script>
        <?php
    }

    public function handle_ssl_testing() {
        check_ajax_referer('ssl_testing_nonce', 'nonce');
    
        $domain = parse_url(home_url(), PHP_URL_HOST); // Get the domain of the WordPress site
    
        $api_url = 'https://api.ssllabs.com/api/v3/analyze?host=' . $domain;
    
        // Make the API request
        $response = wp_remote_get($api_url);
    
        if (is_wp_error($response)) {
            wp_send_json_error('Failed to communicate with SSL Labs API.');
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
    
        if ($data && isset($data->endpoints[0]->grade)) {
            $grade = $data->endpoints[0]->grade;
            wp_send_json_success('SSL Test Grade: ' . $grade);
        } else {
            wp_send_json_error('SSL testing failed.');
        }
    }

    
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

    
    

} // end of class




