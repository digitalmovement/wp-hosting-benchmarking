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

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->init_components();
        $this->gcp_latency = new Wp_Hosting_Benchmarking_GCP_Latency($this->db, $this->api);

      // Hook into 'admin_enqueue_scripts' to enqueue scripts/styles
      add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
      add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
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

        // Localize script after enqueuing
        wp_localize_script($this->plugin_name, 'wpHostingBenchmarking', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_hosting_benchmarking_nonce'), // Create nonce properly within this hook
            'selected_region' => get_option('wp_hosting_benchmarking_selected_region') // Pass the selected region            
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

        
        $registered_user = get_option('wp_hosting_benchmarking_registered_user');
        include_once 'partials/wp-hosting-benchmarking-ssl-testing-display.php';
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

    // Add a new section in the "Settings" page
    add_settings_section(
        'wp_hosting_benchmarking_section', // Section ID
        'General Settings',                // Section title
        null,                              // Section callback (not needed)
        'wp-hosting-benchmarking-settings' // Page slug
    );

    // Add a new field to the "General Settings" section
    add_settings_field(
        'wp_hosting_benchmarking_field',    // Field ID
        'Sample Setting',                  // Field title
        array($this, 'render_sample_setting_field'), // Callback function to render the field
        'wp-hosting-benchmarking-settings', // Page slug
        'wp_hosting_benchmarking_section'   // Section ID (fixed to match)
    );

    // Add a settings field (dropdown for GCP regions)
    add_settings_field(
        'wp_hosting_benchmarking_selected_region', // Field ID
        'Select Closest GCP Region',               // Field title
        array($this, 'gcp_region_dropdown_callback'), // Callback to display the dropdown
        'wp-hosting-benchmarking-settings',        // Page slug
        'wp_hosting_benchmarking_section'          // Section ID (fixed to match)
    );





    }

    /**
     * Render the field for the sample setting.
     */
    public function render_sample_setting_field() {
        $option = get_option('wp_hosting_benchmarking_option');
        ?>
        <input type="text" name="wp_hosting_benchmarking_option" value="<?php echo esc_attr($option); ?>">
        <?php
    }

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
}
