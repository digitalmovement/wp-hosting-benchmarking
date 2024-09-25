<?php
class Wp_Hosting_Benchmarking_GCP_Latency {

    private $db;
    private $api;

    public function __construct($db, $api) {
        $this->db = $db;
        $this->api = $api;
    }

    public function start_latency_test() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        if (!wp_next_scheduled('wp_hosting_benchmarking_cron_hook')) {
            $start_time = time();
            update_option('wp_hosting_benchmarking_start_time', $start_time);
            wp_schedule_event($start_time, 'five_minutes', 'wp_hosting_benchmarking_cron_hook');

            // Run the first test immediately
            $endpoints = $this->api->get_gcp_endpoints();
            foreach ($endpoints as $endpoint) {
                $latency = $this->api->ping_endpoint($endpoint['url']);
                if ($latency !== false) {
                    $this->db->insert_result($endpoint['region_name'], $latency);
                }
            }

            wp_send_json_success(array(
                'message' => 'Test started successfully',
                'start_time' => $start_time
            ));
        } else {
            wp_send_json_error('Test is already running');
        }
    }
    
    public function reset_latency_test() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        wp_clear_scheduled_hook('wp_hosting_benchmarking_cron_hook');
        delete_option('wp_hosting_benchmarking_start_time');
        wp_send_json_success('Test reset successfully');
    
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        if (!wp_next_scheduled('wp_hosting_benchmarking_cron_hook')) {
            wp_schedule_event(time(), 'five_minutes', 'wp_hosting_benchmarking_cron_hook');
            update_option('wp_hosting_benchmarking_start_time', time());
            
            // Run the first test immediately
            $endpoints = $this->api->get_gcp_endpoints();
            foreach ($endpoints as $endpoint) {
                $latency = $this->api->ping_endpoint($endpoint['url']);
                if ($latency !== false) {
                    $this->db->insert_result($endpoint['region_name'], $latency);
                }
            }
            
            wp_send_json_success('Test started successfully');
        } else {
            wp_send_json_error('Test is already running');
        }
    }

	
    public function stop_latency_test() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');

        wp_clear_scheduled_hook('wp_hosting_benchmarking_cron_hook');
        delete_option('wp_hosting_benchmarking_start_time');
        wp_send_json_success('Test stopped successfully');
    }


    public function get_latest_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
		if (!$this->db) {
            wp_send_json_error('Database object not initialized');
            return;
        }

        //$results = $this->db->get_latest_results();
		$latest_results = $this->db->get_latest_results_by_region();
		$fastest_and_slowest = $this->db->get_fastest_and_slowest_results();

        /*
		$results = array_map(function($result) {
			$result->latency = (float) $result->latency;
			return $result;
		}, $results);
        */

        // Merge the data
        foreach ($latest_results as &$result) {
            foreach ($fastest_and_slowest as $fas_slow) {
                if ($result->region_name === $fas_slow->region_name) {
                    $result->fastest_latency = $fas_slow->fastest_latency;
                    $result->slowest_latency = $fas_slow->slowest_latency;
                    break;
                }
            }
        }

		
        wp_send_json_success($latest_results);


    }

    public function get_results_for_time_range() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        
        $time_range = isset($_POST['time_range']) ? sanitize_text_field($_POST['time_range']) : '24_hours';
    
        // Fetch results from DB based on the time range
        $results = $this->db->get_results_by_time_range($time_range);
        $fastest_and_slowest = $this->db->get_fastest_and_slowest_results();

        // Merge the data
        foreach ($results as &$result) {
            foreach ($fastest_and_slowest as $fas_slow) {
                if ($result->region_name === $fas_slow->region_name) {                        
                    $result->fastest_latency = $fas_slow->fastest_latency;
                    $result->slowest_latency = $fas_slow->slowest_latency;
                    break;
                }
            }
        }

                
                
        if (!empty($results)) {
            wp_send_json_success($results);
        } else {
            wp_send_json_error('No results found for the selected time range.');
        }
    }

    
    public function delete_all_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        $this->db->delete_all_results();
        wp_send_json_success('All results deleted');
    }

} // end of class

