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

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->api = new Wp_Hosting_Benchmarking_API();
        $this->db = new Wp_Hosting_Benchmarking_DB();

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
    public function add_admin_menu() {
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
        // Implement the logic to start the latency test
        // This should set up a WordPress cron job to run every 5 minutes for an hour
        wp_send_json_success('Test started successfully');
    }

    public function get_latest_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        $results = $this->db->get_latest_results();
        wp_send_json_success($results);
    }

    public function delete_all_results() {
        check_ajax_referer('wp_hosting_benchmarking_nonce', 'nonce');
        $this->db->delete_all_results();
        wp_send_json_success('All results deleted');
    }

}
