<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://fastestwordpress.com
 * @since      1.0.0
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/includes
 * @author     Fastest WordPress <help@fastestwordpress.com>
 */
class Wp_Hosting_Benchmarking {

    private $loader;
    private $plugin_name;
    private $version;
    private $db;
    private $api;

	
	public function __construct() {
		if ( defined( 'WP_HOSTING_BENCHMARKING_VERSION' ) ) {
			$this->version = WP_HOSTING_BENCHMARKING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-hosting-benchmarking';

		$this->load_dependencies();
		$this->set_locale();
		$this->init_components();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_cron_hooks();

		//$this->db = new Wp_Hosting_Benchmarking_DB();
       // $this->api = new Wp_Hosting_Benchmarking_API();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Hosting_Benchmarking_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Hosting_Benchmarking_i18n. Defines internationalization functionality.
	 * - Wp_Hosting_Benchmarking_Admin. Defines all hooks for the admin area.
	 * - Wp_Hosting_Benchmarking_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-hosting-benchmarking-loader.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-hosting-benchmarking-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-hosting-benchmarking-admin.php';
        //require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-hosting-benchmarking-dashboard.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-hosting-benchmarking-ssl-testing.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-hosting-benchmarking-gcp-latency.php';

		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-hosting-benchmarking-public.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-hosting-benchmarking-api.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-hosting-benchmarking-db.php';

		$this->loader = new Wp_Hosting_Benchmarking_Loader();

	}

	private function init_components() {
        $this->db = new Wp_Hosting_Benchmarking_DB();
        $this->api = new Wp_Hosting_Benchmarking_API();
    }

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Hosting_Benchmarking_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Hosting_Benchmarking_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Hosting_Benchmarking_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
//        $this->loader->add_action('wp_ajax_start_latency_test', $plugin_admin, 'start_latency_test');
 //       $this->loader->add_action('wp_ajax_stop_latency_test', $plugin_admin, 'stop_latency_test');
  //      $this->loader->add_action('wp_ajax_get_latest_results', $plugin_admin, 'get_latest_results');
   //     $this->loader->add_action('wp_ajax_delete_all_results', $plugin_admin, 'delete_all_results');
	//	$this->loader->add_action('wp_ajax_get_results_for_time_range', $plugin_admin, 'get_results_for_time_range');
		

		add_action('wp_ajax_start_latency_test', array($this->gcp_latency, 'start_latency_test'));
		add_action('wp_ajax_reset_latency_test', array($this->gcp_latency, 'reset_latency_test'));
		add_action('wp_ajax_stop_latency_test', array($this->gcp_latency, 'stop_latency_test'));
		add_action('wp_ajax_get_latest_results', array($this->gcp_latency, 'get_latest_results'));
		add_action('wp_ajax_get_results_for_time_range', array($this->gcp_latency, 'get_results_for_time_range'));
		add_action('wp_ajax_delete_all_results', array($this->gcp_latency, 'delete_all_results'));


		//	$this->loader->add_action('wp_ajax_get_results_for_time_range', array($this, 'get_results_for_time_range'));
  		
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Hosting_Benchmarking_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	private function define_cron_hooks() {
        $this->loader->add_action('wp_hosting_benchmarking_cron_hook', $this, 'execute_latency_test');
        $this->loader->add_filter('cron_schedules', $this, 'add_cron_interval');
    }

    public function add_cron_interval($schedules) {
        $schedules['five_minutes'] = array(
            'interval' => 300,
            'display'  => esc_html__('Every Five Minutes'),
        );
        return $schedules;
    }

    public function execute_latency_test() {
    
        $endpoints = $this->api->get_gcp_endpoints();
		foreach ($endpoints as $endpoint) {
			$latency = $this->api->ping_endpoint($endpoint['url']);
			if ($latency !== false) {
				// Store the result in the database
				$this->db->insert_result($endpoint['region_name'], $latency);
			}
		}

        // Check if it's time to stop the test
        $start_time = get_option('wp_hosting_benchmarking_start_time');
        if (time() - $start_time >= 3600) { // 1 hour
            wp_clear_scheduled_hook('wp_hosting_benchmarking_cron_hook');
            delete_option('wp_hosting_benchmarking_start_time');
        }
    }
	public function check_version() {
        if (get_option('wp_hosting_benchmarking_version') != WP_HOSTING_BENCHMARKING_VERSION) {
            $db = new Wp_Hosting_Benchmarking_DB();
            $db->create_table(); // This will update the table if the structure has changed
            update_option('wp_hosting_benchmarking_version', WP_HOSTING_BENCHMARKING_VERSION);
        }
    }
	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
		$this->check_version();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Hosting_Benchmarking_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
