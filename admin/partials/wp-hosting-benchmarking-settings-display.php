<?php
?>
<div class="wrap">
    <h1><?php echo esc_html('WP Hosting Benchmarking Settings'); ?></h1>
    <form method="post" action="options.php">
        <?php
        // Output security fields for the registered setting "wp_hosting_benchmarking_settings"
        settings_fields('wp_hosting_benchmarking_settings');

        // Output setting sections and fields
        do_settings_sections('wp-hosting-benchmarking-settings');

        // Submit button
        submit_button('Save Settings');
        ?>
    </form>
</div>
<?php

