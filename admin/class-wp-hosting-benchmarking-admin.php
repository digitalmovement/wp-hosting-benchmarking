<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://fastestwordpress.com
 * @since      1.0.0
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/admin
 * @author     Fastest WordPress <help@fastestwordpress.com>
 */
class Wp_Hosting_Benchmarking_Admin {

	private $plugin_name;
    private $version;
    private $db;
    private $api;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->init_components();
   
	}

	private function init_components() {
        $this->db = new Wp_Hosting_Benchmarking_DB();
        $this->api = new Wp_Hosting_Benchmarking_API();
    }

   /**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Hosting_Benchmarking_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Hosting_Benchmarking_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-hosting-benchmarking-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Wp_Hosting_Benchmarking_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Wp_Hosting_Benchmarking_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		 wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-hosting-benchmarking-admin.js', array('jquery'), $this->version, false);
		 wp_localize_script($this->plugin_name, 'wpHostingBenchmarking', array(
			 'ajax_url' => admin_url('admin-ajax.php'),
			 'nonce' => wp_create_nonce('wp_hosting_benchmarking_nonce')
		 ));
 
	}
  /**
     * Add admin menu item for WP Benchmarking
     */
    public function add_plugin_admin_menu() {
        add_menu_page(
            'WP Benchmarking',
            'WP Benchmarking',
            'manage_options',
            'wp-hosting-benchmarking',
            array( $this, 'display_plugin_admin_page' ),
            'dashicons-performance',
            999 // Set a high number to ensure it's the last menu item
        );
    }

    /**
     * Display admin page content
     */

	public function display_plugin_admin_page() {
        include_once 'partials/wp-hosting-benchmarking-admin-display.php';
    }

	public function start_latency_test() {
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

        $results = $this->db->get_latest_results();
		$results = array_map(function($result) {
			$result['latency'] = (float) $result['latency'];
			return $result;
		}, $results);

		
        wp_send_json_success($results);

		//$results = $this->db->get_latest_results_by_region();
        //wp_send_json_success($results);
    }

    public function delete_all_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        $this->db->delete_all_results();
        wp_send_json_success('All results deleted');
    }

}
