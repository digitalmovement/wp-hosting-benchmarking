<?php

class WP_Hosting_Benchmarking_Server_Performance {

    private $plugin_name;
    private $version;

    private $db;
    private $api;

    public function __construct($db, $api) {
        $this->db = $db;
        $this->api = $api;
    
        add_action('wp_ajax_wp_hosting_benchmarking_performance_toggle_test', array($this, 'ajax_performance_toggle_test'));
        add_action('wp_ajax_wp_hosting_benchmarking_performance_run_test', array($this, 'ajax_performance_run_test'));
        add_action('wp_ajax_wp_hosting_benchmarking_performance_get_results', array($this, 'ajax_performance_get_results'));
    }

    public function enqueue_scripts($hook) {
        if ('tools_page_wp-hosting-benchmarking-server-performance' !== $hook) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-core');
        wp_enqueue_script('jquery-ui-tabs');
        
        // Enqueue jQuery UI CSS
        wp_enqueue_style('wp-jquery-ui-dialog', includes_url('css/jquery-ui-dialog.min.css'), array(), null);

        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.7.0', true);
        
        wp_enqueue_script(
            'wp-hosting-benchmarking-server-performance',
            plugin_dir_url(__FILE__) . 'js/wp-hosting-benchmarking-server-performance.js',
            array('jquery', 'jquery-ui-core', 'jquery-ui-tabs', 'chart-js'),
            $this->version,
            true
        );

        wp_localize_script(
            'wp-hosting-benchmarking-server-performance',
            'wpHostingBenchmarkingPerformance',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_hosting_benchmarking_performance_nonce'),
                'testStatus' => get_option('wp_hosting_benchmarking_performance_test_status', 'stopped')
            )
        );
    }
    public function display_page() {
        $test_status = get_option('wp_hosting_benchmarking_performance_test_status', 'stopped');

        include plugin_dir_path(__FILE__) . 'partials/wp-hosting-benchmarking-server-performance-display.php';
    }

    public function ajax_performance_toggle_test() {
        check_ajax_referer('wp_hosting_benchmarking_performance_toggle_test');
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'stopped';
        update_option('wp_hosting_benchmarking_performance_test_status', $status);
        wp_send_json_success();
    }

    public function ajax_performance_run_test() {
        check_ajax_referer('wp_hosting_benchmarking_performance_run_test');
        
        // Run tests in the background
        $this->run_performance_tests();
        
        wp_send_json_success();
    }

    public function ajax_performance_get_results() {
        check_ajax_referer('wp_hosting_benchmarking_performance_get_results');
        
        $results = $this->get_test_results();
        $industry_avg = $this->get_industry_averages();
        
        wp_send_json_success(array(
            'cpu_memory' => $results['cpu_memory'],
            'filesystem' => $results['filesystem'],
            'database' => $results['database'],
            'object_cache' => $results['object_cache'],
            'industry_avg' => $industry_avg
        ));
    }

    private function run_performance_tests() {
        $results = array(
            'cpu_memory' => $this->test_cpu_memory(),
            'filesystem' => $this->test_filesystem(),
            'database' => $this->test_database(),
            'object_cache' => $this->test_object_cache()
        );

        update_option('wp_hosting_benchmarking_performance_test_results', $results);
        update_option('wp_hosting_benchmarking_performance_test_status', 'stopped');
    }

    private function test_cpu_memory() {
        // Implement CPU & Memory test
        // This is a placeholder implementation
        sleep(5);
        return rand(1, 5);
    }

    private function test_filesystem() {
        // Implement Filesystem test
        // This is a placeholder implementation
        return rand(1, 5);
    }

    private function test_database() {
        // Implement Database test
        // This is a placeholder implementation
        return rand(1, 5);
    }

    private function test_object_cache() {
        // Implement Object Cache test
        // This is a placeholder implementation
        return rand(1, 5);
    }

    private function get_test_results() {
        return get_option('wp_hosting_benchmarking_performance_test_results', array(
            'cpu_memory' => 0,
            'filesystem' => 0,
            'database' => 0,
            'object_cache' => 0
        ));
    }

    private function get_industry_averages() {
        $response = wp_remote_get('https://assets.fastestwordpress.com/performance-test-averages.json');
        
        if (is_wp_error($response)) {
            return array(
                'cpu_memory' => 2.5,
                'filesystem' => 2.5,
                'database' => 2.5,
                'object_cache' => 2.5
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!$data) {
            return array(
                'cpu_memory' => 2.5,
                'filesystem' => 2.5,
                'database' => 2.5,
                'object_cache' => 2.5
            );
        }
        
        return $data;
    }
}