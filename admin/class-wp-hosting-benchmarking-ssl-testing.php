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
        echo "reg details:";
        print_r($registered_user);
        // Include the HTML + JS from the display file
        include plugin_dir_path(__FILE__) . 'admin/partials/wp-hosting-benchmarking-ssl-testing-display.php';
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

        //$api = new Wp_Hosting_Benchmarking_API();
    
        $registered_user = get_option('wp_hosting_benchmarking_registered_user');
        $email = $registered_user['email'];
        $email = 'jdoe@digitalmovement.co.uk';   
    
        $result = $this->api->test_ssl_certificate(home_url(), $email);

        if (is_array($result)) {
            if (isset($result['error'])) {
                wp_send_json_error($result['error']);
            } elseif (isset($result['status']) && $result['status'] !== 'READY') {
                // Test is still in progress
                wp_send_json_success($result);
            } else {
                // Test is complete
                $formatted_result = $this->format_ssl_test_results($result);
                wp_send_json_success($formatted_result);
            }
        } else {
            wp_send_json_error('Invalid response from SSL testing API');
        }


    }

    // Add the format_ssl_test_results function here
    function format_ssl_test_results($result) {

        $output = '<div class="ssl-test-results">';
        
        $output .= '<pre>--';

        $output .= print_r($result,true); 
        $output .= '</pre>';

        
        // Overall Rating
        $grade = $result['endpoints'][0]['grade'];
        $grade_color = ($grade === 'A' || $grade === 'A+') ? 'green' : (($grade === 'B') ? 'orange' : 'red');
        $output .= '<h2><i class="fas fa-award" style="color: ' . $grade_color . ';"></i> Overall Rating: <span style="color: ' . $grade_color . ';">' . $grade . '</span></h2>';
    
        // Certificate Information
        $cert = $result['certs'][0];
        $output .= '<h3><i class="fas fa-certificate"></i> Certificate Information</h3>';
        $output .= '<ul>';
        $output .= '<li><i class="fas fa-user"></i> Subject: ' . esc_html($cert['subject']) . '</li>';
        $output .= '<li><i class="fas fa-stamp"></i> Issuer: ' . esc_html($cert['issuerSubject']) . '</li>';
        $output .= '<li><i class="fas fa-calendar-plus"></i> Valid from: ' . date('Y-m-d', $cert['notBefore']/1000) . '</li>';
        $output .= '<li><i class="fas fa-calendar-times"></i> Valid until: ' . date('Y-m-d', $cert['notAfter']/1000) . '</li>';
        $output .= '</ul>';
    
        // Protocols
        $output .= '<h3><i class="fas fa-exchange-alt"></i> Supported Protocols</h3>';
        $output .= '<ul>';
        foreach ($result['endpoints'][0]['details']['protocols'] as $protocol) {
            $output .= '<li><i class="fas fa-check-circle"></i> ' . esc_html($protocol['name'] . ' ' . $protocol['version']) . '</li>';
        }
        $output .= '</ul>';
    
        // Cipher Suites
        $output .= '<h3><i class="fas fa-lock"></i> Cipher Suites</h3>';
        $output .= '<ul>';
        foreach ($result['endpoints'][0]['details']['suites']['list'] as $suite) {
            $icon = ($suite['q'] == 1) ? '<i class="fas fa-times-circle" style="color: red;"></i>' : '<i class="fas fa-check-circle" style="color: green;"></i>';
            $output .= '<li>' . $icon . ' ' . esc_html($suite['name']) . '</li>';
        }
        $output .= '</ul>';
    
        // Handshake
        $output .= '<h3><i class="fas fa-lock"></i> Handshake Simulation</h3>';
        $output .= '<ul>';
               foreach ($result['sim'][0]['details']['suites']['list'] as $suite) {
                   $icon = ($suite['q'] == 1) ? '<i class="fas fa-times-circle" style="color: red;"></i>' : '<i class="fas fa-check-circle" style="color: green;"></i>';
                   $output .= '<li>' . $icon . ' ' . esc_html($suite['name']) . '</li>';
               }
        $output .= '</ul>';
        $output .= $this->format_ssl_simulations($result['endpoints'][0]['details']['sims']);

        // Vulnerabilities
        $output .= '<h3><i class="fas fa-bug"></i> Vulnerabilities</h3>';
        $output .= '<ul>';
        $vulnerabilities = [
            'heartbleed' => 'Heartbleed',
            'poodle' => 'POODLE',
            'freak' => 'FREAK',
            'logjam' => 'Logjam'
        ];
        foreach ($vulnerabilities as $key => $name) {
            $vulnerable = $result['endpoints'][0]['details'][$key];
            $icon = $vulnerable ? '<i class="fas fa-exclamation-triangle" style="color: red;"></i>' : '<i class="fas fa-shield-alt" style="color: green;"></i>';
            $status = $vulnerable ? 'Vulnerable' : 'Not Vulnerable';
            $output .= '<li>' . $icon . ' ' . esc_html($name) . ': ' . $status . '</li>';
        }
        $output .= '</ul>';
    
        $output .= '</div>';
        return $output;
    }


    function format_ssl_simulations($sims) {
        $output = '<h3><i class="fas fa-laptop"></i> Client Simulations</h3>';
        $output .= '<table class="ssl-simulations-table">';
        $output .= '<thead><tr><th>Client</th><th>Version</th><th>Result</th></tr></thead>';
        $output .= '<tbody>';
    
        foreach ($sims['results'] as $sim) {
            $client = $sim['client'];
            $errorClass = ($sim['errorCode'] !== 0) ? ' class="error"' : '';
            
            $output .= '<tr' . $errorClass . '>';
            $output .= '<td>' . esc_html($client['name']) . '</td>';
            $output .= '<td>' . esc_html($client['version']) . '</td>';
            
            if ($sim['errorCode'] === 0) {
                $output .= '<td><i class="fas fa-check-circle" style="color: green;"></i> ' . esc_html($sim['suiteName']) . '</td>';
            } else {
                $output .= '<td><i class="fas fa-exclamation-triangle" style="color: red;"></i> ' . esc_html($sim['errorMessage']) . '</td>';
            }
            
            $output .= '</tr>';
        }
    
        $output .= '</tbody></table>';
    
        return $output;
    }


} // end of class




