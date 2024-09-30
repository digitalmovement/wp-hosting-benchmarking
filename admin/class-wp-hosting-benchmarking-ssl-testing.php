<?php

class Wp_Hosting_Benchmarking_SSL_Testing {

    private $db;
    private $api;
    private $transient_key = 'wp_hosting_benchmarking_ssl_results';
    private $in_progress_key = 'wp_hosting_benchmarking_ssl_test_in_progress';


    public function __construct($db, $api) {
        $this->db = $db;
        $this->api = $api;
        add_action('wp_ajax_start_ssl_test', array($this, 'start_ssl_test'));
        add_action('wp_ajax_check_ssl_test_status', array($this, 'check_ssl_test_status'));
        add_action('wp_ajax_nopriv_check_ssl_test_status', array($this, 'check_ssl_test_status'));

    }

    public function start_ssl_test() {
        check_ajax_referer('ssl_testing_nonce', 'nonce');

        $registered_user = get_option('wp_hosting_benchmarking_registered_user');
        $email = isset($registered_user['email']) ? $registered_user['email'] : 'default@example.com';


        // EMail must changed. 
        $email = "jdoe@digitalmovement.co.uk";
        $result = $this->api->test_ssl_certificate(home_url(), $email);

        if (is_array($result) && isset($result['status']) && $result['status'] !== 'READY') {
            set_transient($this->in_progress_key, $result, 30 * MINUTE_IN_SECONDS);
            wp_send_json_success(array('status' => 'in_progress', 'message' => 'SSL test initiated. Please wait.'));
        } elseif (is_array($result) && !isset($result['error'])) {
            $this->cache_ssl_results($result);
            wp_send_json_success(array('status' => 'completed', 'data' => $this->format_ssl_test_results($result)));
        } else {
            wp_send_json_error('Failed to start SSL test.');
        }
    }


    public function check_ssl_test_status() {
        check_ajax_referer('ssl_testing_nonce', 'nonce');

        $in_progress_result = get_transient($this->in_progress_key);

        if (false === $in_progress_result) {
            $cached_result = get_transient($this->transient_key);
            if (false !== $cached_result) {
                wp_send_json_success(array('status' => 'completed', 'data' => $this->format_ssl_test_results($cached_result)));
            } else {
                wp_send_json_error('No SSL test in progress or cached results available.');
            }
            return;
        }

        $result = $this->api->check_ssl_test_status($in_progress_result);

        if (is_array($result) && isset($result['status']) && $result['status'] === 'READY') {
            delete_transient($this->in_progress_key);
            $this->cache_ssl_results($result);
            wp_send_json_success(array('status' => 'completed', 'data' => $this->format_ssl_test_results($result)));
        } elseif (is_array($result) && isset($result['status'])) {
            wp_send_json_success(array('status' => 'in_progress', 'message' => 'SSL test still in progress. Status: ' . $result['status']));
        } else {
            wp_send_json_error('Failed to check SSL test status.');
        }
    }


    private function cache_ssl_results($result) {
        set_transient($this->transient_key, $result, HOUR_IN_SECONDS);
    }

    public function get_cached_results() {
        return get_transient($this->transient_key);
    }


    // Display the SSL testing page
       // Display the SSL testing or registration page
    public function display_ssl_testing_page() {
        // Check if the user is already registered
        $registered_user = get_option('wp_hosting_benchmarking_registered_user');
        $cached_result = get_transient($this->transient_key);

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

    function format_certificate_info($cert) {
        $output = '<h3><i class="fas fa-certificate"></i> Certificate Information</h3>';
        $output .= '<table class="wp-list-table widefat fixed striped">';
        $output .= '<tbody>';
    
        $add_row = function($label, $value) use (&$output) {
            $output .= '<tr>';
            $output .= '<th scope="row">' . esc_html($label) . '</th>';
            $output .= '<td>' . $value . '</td>';
            $output .= '</tr>';
        };
    
        $add_row('Subject', esc_html($cert['subject']));
        $add_row('Fingerprint SHA256', esc_html($cert['sha256Hash']));
        $add_row('Pin SHA256', esc_html($cert['pinSha256']));
        $add_row('Common names', esc_html(implode(', ', $cert['commonNames'])));
        $add_row('Alternative names', esc_html(implode(', ', $cert['altNames'])));
        $add_row('Serial Number', esc_html($cert['serialNumber']));
        $add_row('Valid from', date('D, d M Y H:i:s T', $cert['notBefore'] / 1000));
        $add_row('Valid until', date('D, d M Y H:i:s T', $cert['notAfter'] / 1000) . ' (expires in ' . $this->format_expiry_time($cert['notAfter']) . ')');
        $add_row('Key', esc_html($cert['keyAlg']) . ' ' . $cert['keySize'] . ' bits (e ' . $cert['keyStrength'] . ')');
        $add_row('Weak key (Debian)', $cert['weakDebianKey'] ? 'Yes' : 'No');
        $add_row('Issuer', esc_html($cert['issuerSubject']));
        $add_row('Signature algorithm', esc_html($cert['sigAlg']));
        $add_row('Extended Validation', $cert['validationType'] === 'EV' ? 'Yes' : 'No');
        $add_row('Certificate Transparency', $cert['sct'] ? 'Yes' : 'No');
        $add_row('OCSP Must Staple', $cert['mustStaple'] ? 'Yes' : 'No');
    
        // Revocation information
        $revocation_info = 'Not available';
        if (isset($cert['revocationInfo'])) {
            if (is_array($cert['revocationInfo'])) {
                $revocation_info = esc_html(implode(', ', $cert['revocationInfo']));
            } else {
                $revocation_info = esc_html($cert['revocationInfo']);
            }
        }
        $add_row('Revocation information', $revocation_info);
    
        // Revocation status
        $revocation_status = isset($cert['revocationStatus']) ? esc_html($cert['revocationStatus']) : 'Not available';
        $add_row('Revocation status', $revocation_status);
    
        $output .= '</tbody>';
        $output .= '</table>';
    
        return $output;
    }
    
    function format_protocols($protocols) {
        $output = '<h3><i class="fas fa-exchange-alt"></i> Supported Protocols</h3>';
        $output .= '<ul>';
        foreach ($protocols as $protocol) {
            $output .= '<li><i class="fas fa-check-circle"></i> ' . esc_html($protocol['name'] . ' ' . $protocol['version']) . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }
    
    function format_cipher_suites($suites) {
        $output = '<h3><i class="fas fa-lock"></i> Cipher Suites</h3>';
            foreach ($suites as $protocol_suite) {
                $protocol = isset($protocol_suite['protocol']) ? 'TLS ' . number_format($protocol_suite['protocol'] / 256, 1) : 'Unknown Protocol';
                $output .= '<h4>' . esc_html($protocol) . '</h4>';
                $output .= '<ul>';
                
                if (isset($protocol_suite['list']) && is_array($protocol_suite['list'])) {
                    foreach ($protocol_suite['list'] as $suite) {
                        $icon = (isset($suite['q']) && $suite['q'] == 1) ? '<i class="fas fa-times-circle" style="color: red;"></i>' : '<i class="fas fa-check-circle" style="color: green;"></i>';
                        $strength = isset($suite['cipherStrength']) ? ' (' . $suite['cipherStrength'] . '-bit)' : '';
                        $output .= '<li>' . $icon . ' ' . esc_html($suite['name']) . esc_html($strength) . '</li>';
                    }
                }
                
                $output .= '</ul>';
            }
        return $output;
    }
    
    function format_vulnerabilities($details) {
        $output = '<h3><i class="fas fa-bug"></i> Vulnerabilities</h3>';
        $output .= '<ul>';
        $vulnerabilities = [
            'heartbleed' => 'Heartbleed',
            'poodle' => 'POODLE',
            'freak' => 'FREAK',
            'logjam' => 'Logjam'
        ];
        foreach ($vulnerabilities as $key => $name) {
            $vulnerable = $details[$key];
            $icon = $vulnerable ? '<i class="fas fa-exclamation-triangle" style="color: red;"></i>' : '<i class="fas fa-shield-alt" style="color: green;"></i>';
            $status = $vulnerable ? 'Vulnerable' : 'Not Vulnerable';
            $output .= '<li>' . $icon . ' ' . esc_html($name) . ': ' . $status . '</li>';
        }
        $output .= '</ul>';
        return $output;
    }


    

    // Add the format_ssl_test_results function here
    public function format_ssl_test_results($result) {

    
        // Certificate Information
        $output = '<div class="ssl-test-results" id="ssl-test-results-' . uniqid() . '">';
    
        // Overall Rating (always visible)
        $grade = $result['endpoints'][0]['grade'];
        $grade_color = ($grade === 'A' || $grade === 'A+') ? 'green' : (($grade === 'B') ? 'orange' : 'red');
        $output .= '<h2><i class="fas fa-award" style="color: ' . $grade_color . ';"></i> Overall Rating: <span style="color: ' . $grade_color . ';">' . $grade . '</span></h2>';
    
        // Start tabs
        $output .= '<div class="ssl-tabs">';
        $output .= '<ul class="ssl-tab-links">';
        $output .= '<li class="active"><a href="#tab-root-stores">Root Stores</a></li>';
        $output .= '<li><a href="#tab-cert">Certificate</a></li>';
        $output .= '<li><a href="#tab-protocols">Protocols</a></li>';
        $output .= '<li><a href="#tab-ciphers">Cipher Suites</a></li>';
        $output .= '<li><a href="#tab-handshake">Handshake Simulation</a></li>';
        $output .= '<li><a href="#tab-http">HTTP Request</a></li>';
        $output .= '<li><a href="#tab-vulnerabilities">Vulnerabilities</a></li>';
        $output .= '<li><a href="#tab-raw">Raw Data</a></li>';
        $output .= '</ul>';
    
    
        $output .= '<div class="ssl-tab-content">';
       // Root Stores and Certificate Details
       $output .= '<div id="tab-root-stores" class="ssl-tab active">';
       $output .= $this->format_root_stores_and_cert_details($result);
       $output .= '</div>';


        // Certificate Information
        $output .= '<div id="tab-cert" class="ssl-tab">';
        $output .= $this->format_certificate_info($result['certs'][0]);
        $output .= '</div>';
    
        // Protocols
        $output .= '<div id="tab-protocols" class="ssl-tab">';
        $output .= $this->format_protocols($result['endpoints'][0]['details']['protocols']);
        $output .= '</div>';
    
        // Cipher Suites
        $output .= '<div id="tab-ciphers" class="ssl-tab">';
        $output .= $this->format_cipher_suites($result['endpoints'][0]['details']['suites']);
        $output .= '</div>';
    
        // Handshake Simulation
        $output .= '<div id="tab-handshake" class="ssl-tab">';
        $output .= $this->format_ssl_simulations($result['endpoints'][0]['details']['sims']);
        $output .= '</div>';
    
        // HTTP Request Information
        $output .= '<div id="tab-http" class="ssl-tab">';
        $output .= $this->format_http_request_info($result['endpoints'][0]['details']['httpTransactions']);
        $output .= '</div>';
    
        // Vulnerabilities
        $output .= '<div id="tab-vulnerabilities" class="ssl-tab">';
        $output .= $this->format_vulnerabilities($result['endpoints'][0]['details']);
        $output .= '</div>';
    
        // Raw Data
        $output .= '<div id="tab-raw" class="ssl-tab">';
        $output .= '<pre>' . esc_html(print_r($result, true)) . '</pre>';
        $output .= '</div>';
    
        $output .= '</div>'; // End tab content
        $output .= '</div>'; // End tabs
    
        $output .= '</div>'; // End ssl-test-results

     
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

    function format_http_request_info($httpTransactions) {
        $output = '<h3><i class="fas fa-exchange-alt"></i> HTTP Request Information</h3>';
        
        foreach ($httpTransactions as $index => $transaction) {
            $output .= '<div class="http-transaction">';
            $output .= '<h4>Transaction #' . ($index + 1) . '</h4>';
            
            // Request Details
            $output .= '<h5><i class="fas fa-arrow-right"></i> Request</h5>';
            $output .= '<table class="http-info-table">';
            $output .= '<tr><th>URL</th><td>' . esc_html($transaction['requestUrl']) . '</td></tr>';
            $output .= '<tr><th>Method</th><td>' . esc_html(explode(' ', $transaction['requestLine'])[0]) . '</td></tr>';
            $output .= '</table>';
            
            // Request Headers
            $output .= '<h6>Request Headers:</h6>';
            $output .= '<table class="http-info-table">';
            foreach ($transaction['requestHeaders'] as $header) {
                $parts = explode(':', $header, 2);
                if (count($parts) == 2) {
                    $output .= '<tr><th>' . esc_html(trim($parts[0])) . '</th><td>' . esc_html(trim($parts[1])) . '</td></tr>';
                } else {
                    $output .= '<tr><td colspan="2">' . esc_html($header) . '</td></tr>';
                }
            }
            $output .= '</table>';
            
            // Response Details
            $output .= '<h5><i class="fas fa-arrow-left"></i> Response</h5>';
            $output .= '<table class="http-info-table">';
            $output .= '<tr><th>Status</th><td>' . esc_html($transaction['statusCode'] . ' ' . explode(' ', $transaction['responseLine'], 3)[2]) . '</td></tr>';
            $output .= '</table>';
            
            // Response Headers
            $output .= '<h6>Response Headers:</h6>';
            $output .= '<table class="http-info-table">';
            foreach ($transaction['responseHeaders'] as $header) {
                $output .= '<tr><th>' . esc_html($header['name']) . '</th><td>' . esc_html($header['value']) . '</td></tr>';
            }
            $output .= '</table>';
            
            $output .= '</div>';
        }
        
        return $output;
    }

    private function format_root_stores_and_cert_details($result) {
        $output = '<h3><i class="fas fa-shield-alt"></i> Trusted Root Stores</h3>';
        $output .= '<ul>';
    
        $trustPaths = $result['endpoints'][0]['details']['certChains'][0]['trustPaths'];
        $rootStores = ['Mozilla', 'Apple', 'Android', 'Java', 'Windows'];
    
        foreach ($rootStores as $store) {
            $trusted = false;
            foreach ($trustPaths as $path) {
                foreach ($path['trust'] as $trust) {
                    if ($trust['rootStore'] === $store && $trust['isTrusted']) {
                        $trusted = true;
                        break 2;
                    }
                }
            }
            $icon = $trusted ? '<i class="fas fa-check-circle" style="color: green;"></i>' : '<i class="fas fa-times-circle" style="color: red;"></i>';
            $output .= '<li>' . $icon . ' ' . esc_html($store) . '</li>';
        }
    
        $output .= '</ul>';
    
        return $output;
    }
    
    private function format_expiry_time($timestamp) {
        $now = time();
        $expiry = $timestamp / 1000; // Convert milliseconds to seconds
        $diff = $expiry - $now;
    
        $days = floor($diff / (60 * 60 * 24));
        $months = floor($days / 30);
        $days %= 30;
    
        if ($months > 0) {
            return "$months month" . ($months > 1 ? "s" : "") . " and $days day" . ($days > 1 ? "s" : "");
        } else {
            return "$days day" . ($days > 1 ? "s" : "");
        }
    }

} // end of class




