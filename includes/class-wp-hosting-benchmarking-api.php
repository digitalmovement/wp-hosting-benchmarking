<?php 

class Wp_Hosting_Benchmarking_API {

    public function get_gcp_endpoints() {
        $response = wp_remote_get('https://global.gcping.com/api/endpoints');
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        $endpoints = json_decode($body, true);
        
        if (!is_array($endpoints)) {
            return false;
        }

        $formatted_endpoints = [];
        foreach ($endpoints as $region_code => $region_data) {
            $formatted_endpoints[] = [
                'region' => $region_data['Region'],
                'region_name' => $region_data['RegionName'],
                'url' => $region_data['URL']
            ];
        }

        return $formatted_endpoints;
    }

    public function ping_endpoint($url) {
        $start_time = microtime(true);
        $response = wp_remote_get($url . '/api/ping');
        $end_time = microtime(true);
        if (is_wp_error($response)) {
            return false;
        }
        return round(($end_time - $start_time) * 1000, 1); // Convert to milliseconds and round to 1 decimal place
    }

    public function test_ssl_certificate($domain, $email) {
        $api_url = 'https://api.ssllabs.com/api/v4/analyze';
        $host = parse_url($domain, PHP_URL_HOST);
        
        // Prepare the request arguments
        $args = array(
            'timeout' => 30, // Reduced timeout for quicker responses
            'headers' => array(
                'email' => $email
            ),
            'body' => array(
                'host' => $host,
           //     'startNew' => 'on', // Start a new assessment if there's no cached result
                'fromCache' => 'on', // Don't use cached results
                'ignoreMismatch' => 'on' // Proceed even if there's a mismatch
            )
        );
    
        // Make the API request
        $response = wp_remote_get($api_url, $args);
    
        if (is_wp_error($response)) {
            return array('error' => 'Failed to connect to SSL Labs API: ' . $response->get_error_message());
        }
    
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true); // Decode as associative array
    
        if (!$data) {
            return array('error' => 'Failed to parse SSL Labs API response');
        }
    
        // Check the status of the assessment
        if (isset($data['status'])) {
            if ($data['status'] === 'READY' && isset($data['endpoints'])) {
                return $data; // Return the full data for detailed analysis
            } else {
                // Assessment is still in progress
                return array(
                    'status' => $data['status'],
                    'message' => isset($data['statusMessage']) ? $data['statusMessage'] : 'Assessment in progress'
                );
            }
        }
    
        if (isset($data['errors']) && !empty($data['errors'])) {
            return array('error' => 'SSL Labs reported errors: ' . implode(', ', $data['errors']));
        }
    
        return array('error' => 'Unexpected response from SSL Labs API');
    }
}