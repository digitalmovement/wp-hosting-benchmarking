<?php 

class Wp_Hosting_Benchmarking_API {
    public function get_gcp_endpoints() {
        $response = wp_remote_get('https://global.gcping.com/api/endpoints');
        if (is_wp_error($response)) {
            return false;
        }
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }

    public function ping_endpoint($endpoint) {
        $url = "https://{$endpoint}/api/ping";
        $start_time = microtime(true);
        $response = wp_remote_get($url);
        $end_time = microtime(true);
        if (is_wp_error($response)) {
            return false;
        }
        return round(($end_time - $start_time) * 1000, 1); // Convert to milliseconds and round to 1 decimal place
    }
}

