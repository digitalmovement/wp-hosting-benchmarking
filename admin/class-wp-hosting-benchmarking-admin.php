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
    private $gcp_latency;
    private $performance_testing;


	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->init_components();
        $this->gcp_latency = new Wp_Hosting_Benchmarking_GCP_Latency($this->db, $this->api);
        $this->ssl_testing = new Wp_Hosting_Benchmarking_SSL_Testing($this->db, $this->api);
        $this->performance_testing = new WP_Hosting_Benchmarking_Server_Performance($this->db, $this->api);
 
        // Hook into 'admin_enqueue_scripts' to enqueue scripts/styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($server_performance, 'enqueue_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
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
        wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/wp-hosting-benchmarking-admin.css', array(), $this->version, 'all' );
    }

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
    public function enqueue_scripts() {
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
        wp_enqueue_script('luxon', 'https://cdn.jsdelivr.net/npm/luxon/build/global/luxon.min.js', array(), null, true);
        wp_enqueue_script('chartjs-adapter-luxon', 'https://cdn.jsdelivr.net/npm/chartjs-adapter-luxon', array('chart-js'), null, true);

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/wp-hosting-benchmarking-admin.js', array('jquery'), $this->version, false);
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css');

        // Localize script after enqueuing
        wp_localize_script($this->plugin_name, 'wpHostingBenchmarking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_hosting_benchmarking_nonce'), // Create nonce properly within this hook
            'selected_region' => get_option('wp_hosting_benchmarking_selected_region') // Pass the selected region            
        ));

        wp_enqueue_script($this->plugin_name . '-settings', plugin_dir_url(__FILE__) . 'js/wp-hosting-benchmarking-settings.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name . '-settings', 'wpHostingBenchmarkingSettings', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_hosting_benchmarking_settings_nonce'),
        ));

          // Enqueue the server performance script
        wp_enqueue_script($this->plugin_name . '-server-performance', plugin_dir_url(__FILE__) . 'js/wp-hosting-benchmarking-server-performance.js', array('jquery', 'chart-js'), $this->version, true);

        // Localize script for server performance
        wp_localize_script($this->plugin_name . '-server-performance', 'wpHostingBenchmarkingPerformance', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_hosting_benchmarking_performance_nonce'),
            'testStatus' => get_option('wp_hosting_benchmarking_performance_test_status', 'stopped')
        ));

  

        

    }


    /**
     * wpHostingBenchmarkingSettings
     * 
     * Add admin menu item for WP Benchmarking
     */
    public function add_plugin_admin_menu() {

        add_menu_page(
            'WP Benchmarking',
            'WP Benchmarking',
            'manage_options',
            'wp-hosting-benchmarking', // Slug for dashboard
            array($this, 'display_plugin_admin_page'), // Callback for dashboard
            'dashicons-performance',
            999 // Position in the admin menu
        );
    
        // Add submenu for GCP Latency Testing
        add_submenu_page(
            'wp-hosting-benchmarking', // Parent slug (dashboard)
            'Latency Testing',              // Page title
            'Latency Testing',              // Menu title
            'manage_options',                   // Capability
            'wp-hosting-benchmarking-latency',  // Slug for latency testing
            array($this, 'display_gcp_latency_page') // Callback function
        );
    
        // Add submenu for SSL Testing
        add_submenu_page(
            'wp-hosting-benchmarking', // Parent slug
            'SSL Testing',                      // Page title
            'SSL Testing',                      // Menu title
            'manage_options',                   // Capability
            'wp-hosting-benchmarking-ssl',      // Slug for SSL testing
            array($this, 'display_ssl_testing_page') // Callback function
        );
    
                // New submenu item: Server Performance
        add_submenu_page(
            'wp-hosting-benchmarking',
            'Server Performance',
            'Server Performance',
            'manage_options',
            'wp-hosting-benchmarking-server-performance',
            array($this, 'display_server_performance_page')
        );
    
        // New submenu item: External Load Testing
        add_submenu_page(
            'wp-hosting-benchmarking',
            'External Load Testing',
            'External Load Testing',
            'manage_options',
            'wp-hosting-benchmarking-external-load',
            array($this, 'display_external_load_testing_page')
        );

        // Add submenu for Settings
        add_submenu_page(
            'wp-hosting-benchmarking', // Parent slug
            'Settings',                         // Page title
            'Settings',                         // Menu title
            'manage_options',                   // Capability
            'wp-hosting-benchmarking-settings', // Slug for settings
            array($this, 'display_settings_page') // Callback for settings
        );
    }

    /**
     * Display admin page content
     */

	public function display_plugin_admin_page() {
        include_once 'partials/wp-hosting-benchmarking-admin-display.php';
    }

    public function display_gcp_latency_page() {
        include_once 'partials/wp-hosting-benchmarking-gcp-latency-display.php';
    }

    public function display_ssl_testing_page() {
        $script_data = array(
            'registrationNonce' => wp_create_nonce('ssl_registration_nonce'),
            'testingNonce'      => wp_create_nonce('ssl_testing_nonce'),
        );
        wp_localize_script('ssl-testing-script', 'sslTestingData', $script_data);

        $cached_result = get_transient($this->transient_key);
        $registered_user = get_option('wp_hosting_benchmarking_registered_user');
 
        $cached_result = $this->ssl_testing->get_cached_results();
        $registered_user = get_option('wp_hosting_benchmarking_registered_user');

        // Make the SSL testing object available to the view
        $ssl_testing = $this->ssl_testing;
        
        include_once 'partials/wp-hosting-benchmarking-ssl-testing-display.php';
    }

    public function display_server_performance_page() {
        $test_status = get_option('wp_hosting_benchmarking_performance_test_status', 'stopped');

        include_once 'partials/wp-hosting-benchmarking-server-performance-display.php';
    }

    // New method for External Load Testing page
    public function display_external_load_testing_page() {
        include_once 'partials/wp-hosting-benchmarking-external-load-testing-display.php';
    }



    public function display_settings_page() {
        include_once 'partials/wp-hosting-benchmarking-settings-display.php';
    }


    /**
     * Register plugin settings.
     */
    public function register_settings() {
        // Register a new setting for "wp_hosting_benchmarking_settings"
        register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_option');
        register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_selected_region');

        register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_selected_provider');
        register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_selected_package');
        // Register new setting for anonymous data collection
        register_setting('wp_hosting_benchmarking_settings', 'wp_hosting_benchmarking_allow_data_collection', array(
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'boolval'
        ));


        // Add a new section in the "Settings" page
        add_settings_section(
            'wp_hosting_benchmarking_section', // Section ID
            'General Settings',                // Section title
            null,                              // Section callback (not needed)
            'wp-hosting-benchmarking-settings' // Page slug
        );


        // Add a settings field (dropdown for GCP regions)
        add_settings_field(
            'wp_hosting_benchmarking_selected_region', // Field ID
            'Select Closest GCP Region',               // Field title
            array($this, 'gcp_region_dropdown_callback'), // Callback to display the dropdown
            'wp-hosting-benchmarking-settings',        // Page slug
            'wp_hosting_benchmarking_section'          // Section ID (fixed to match)
        );

        add_settings_field(
            'wp_hosting_benchmarking_selected_provider',
            'Select Hosting Provider',
            array($this, 'hosting_provider_dropdown_callback'),
            'wp-hosting-benchmarking-settings',
            'wp_hosting_benchmarking_section'
        );

        add_settings_field(
            'wp_hosting_benchmarking_selected_package',
            'Select Package',
            array($this, 'hosting_package_dropdown_callback'),
            'wp-hosting-benchmarking-settings',
            'wp_hosting_benchmarking_section'
        );

              // Add a new field for anonymous data collection
        add_settings_field(
            'wp_hosting_benchmarking_allow_data_collection',
            'Allow anonymous data collection',
             array($this, 'render_data_collection_field'),
            'wp-hosting-benchmarking-settings',
            'wp_hosting_benchmarking_section'
        );

    }

    /**
     * Render the fields
     */

    // Callback to display the GCP region dropdown
    public function gcp_region_dropdown_callback() {

        $gcp_endpoints = $this->api->get_gcp_endpoints(); // Fetch GCP endpoints
        $selected_region = get_option('wp_hosting_benchmarking_selected_region'); // Get selected region

        if (!empty($gcp_endpoints)) {
            echo '<select name="wp_hosting_benchmarking_selected_region">';
            foreach ($gcp_endpoints as $endpoint) {
                $region_name = esc_attr($endpoint['region_name']);
                echo '<option value="' . $region_name . '"' . selected($selected_region, $region_name, false) . '>';
                echo esc_html($region_name);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<p>No GCP endpoints available.</p>';
        }
           // Explanation text
        echo '<p class="description">Please select the region closest to where most of your customers or visitors are based. </p>';
    }

   public function hosting_provider_dropdown_callback() {
        $providers = $this->api->get_hosting_providers();
        $selected_provider = get_option('wp_hosting_benchmarking_selected_provider');

        if (!empty($providers)) {
            echo '<select id="wp_hosting_benchmarking_selected_provider" name="wp_hosting_benchmarking_selected_provider">';
            echo '<option value="">Select a provider</option>';
            foreach ($providers as $provider) {
                $provider_name = esc_attr($provider['name']);
                echo '<option value="' . $provider_name . '"' . selected($selected_provider, $provider_name, false) . '>';
                echo esc_html($provider_name);
                echo '</option>';
            }
            echo '</select>';
        } else {
            echo '<p>No hosting providers available.</p>';
        }
    }

    public function hosting_package_dropdown_callback() {
        $selected_provider = get_option('wp_hosting_benchmarking_selected_provider');
        $selected_package = get_option('wp_hosting_benchmarking_selected_package');
    
        echo '<select id="wp_hosting_benchmarking_selected_package" name="wp_hosting_benchmarking_selected_package">';
        echo '<option value="">Select a package</option>';
    
        if ($selected_provider) {
            $providers = $this->api->get_hosting_providers();
            foreach ($providers as $provider) {
                if ($provider['name'] === $selected_provider) {
                    foreach ($provider['packages'] as $package) {
                        $package_type = esc_attr($package['type']);
                        echo '<option value="' . $package_type . '"' . selected($selected_package, $package_type, false) . '>';
                        echo esc_html($package_type);
                        echo '</option>';
                    }
                    break;
                }
            }
        }
        echo '</select>';
    
        if (!$selected_provider) {
            echo '<p class="description">Please select a provider first.</p>';
        }
    }


    public function ajax_get_provider_packages() {
        check_ajax_referer('wp_hosting_benchmarking_settings_nonce', 'nonce');

        $provider_name = sanitize_text_field($_POST['provider']);
        $providers = $this->api->get_hosting_providers();

        $packages = array();
        foreach ($providers as $provider) {
            if ($provider['name'] === $provider_name) {
                $packages = $provider['packages'];
                break;
            }
        }

        wp_send_json_success($packages);
    }

    public function render_data_collection_field() {
        $option = get_option('wp_hosting_benchmarking_allow_data_collection', true);
        ?>
        <input type="checkbox" id="wp_hosting_benchmarking_allow_data_collection" name="wp_hosting_benchmarking_allow_data_collection" value="1" <?php checked($option, true); ?>>
        <label for="wp_hosting_benchmarking_allow_data_collection">Allow anonymous data collection</label>
        <p class="description">Help improve our plugin by allowing anonymous data collection. <a href="https://wpspeedtestpro.com/privacy-policy" target="_blank">Learn more about our privacy policy</a>.</p>
        <?php
    }


}
