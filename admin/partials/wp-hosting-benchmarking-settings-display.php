<?php
?>
<div class="wrap">
    <h1><?php echo esc_html('WP Hosting Benchmarking Settings'); ?></h1>
    <form method="post" action="options.php">
        <?php
        // Output security fields for the registered setting group "wp_hosting_benchmarking_settings"
        settings_fields('wp_hosting_benchmarking_settings'); // Group name must match register_setting()

        // Output setting sections and fields for the page slug 'wp-hosting-benchmarking-settings'
        do_settings_sections('wp-hosting-benchmarking-settings'); // Page slug must match add_settings_section()
        
        // Submit button
        submit_button('Save Settings');
        ?>
    </form>
</div>
<?php

