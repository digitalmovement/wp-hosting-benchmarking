<?php
// HTML + JavaScript for SSL Testing and Registration

$current_user = wp_get_current_user();
$user_email = $current_user->user_email;
$registered_user = isset($registered_user) ? $registered_user : false;
?>
<div class="wrap">
    <h1>SSL Testing</h1>

    <?php if (!$registered_user): ?>
        <form id="ssl-registration-form">
            <h2>Register for SSL Testing</h2>
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" required><br>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" required><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo esc_attr($user_email); ?>" required><br>

            <label for="organization">Organization:</label>
            <input type="text" id="organization" name="organization" required><br>

            <input type="submit" id="register-button" class="button button-primary" value="Register">
        </form>
        <p id="email-error" style="color: red;"></p>
    <?php else: ?>
        <div id="ssl-results">
            <?php 
            if ($cached_result) {
                echo $ssl_testing->format_ssl_test_results($cached_result);
            } 
            ?>
        </div>

        <form id="ssl-testing-form">
            <?php wp_nonce_field('ssl_testing_nonce', 'ssl_nonce'); ?>
            <input type="submit" id="test-ssl-button" class="button button-primary" value="<?php echo $cached_result ? 'Retest SSL' : 'Start SSL Test'; ?>">
            <span id="loading-icon" style="display: none; margin-left: 10px;">
                <i class="fa-solid fa-spinner fa-spin-pulse"></i>
            </span>
            <p id="test-status"></p>
        </form>
    <?php endif; ?>
</div>

<script>


    // Handle registration
    var checkStatusInterval;
  

    function startSSLTest() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'start_ssl_test',
                nonce: jQuery('#ssl_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'in_progress') {
                        jQuery('#test-status').text(response.data.message);
                        checkStatusInterval = setInterval(checkSSLTestStatus, 60000); // Check every 60 seconds
                    } else if (response.data.status === 'completed') {
                        displaySSLResults(response.data.data);
                    }
                } else {
                    jQuery('#test-status').text('Error starting SSL test: ' + response.data);
                    jQuery('#test-ssl-button').prop('disabled', false);
                    jQuery('#loading-icon').hide();
                }
            },
            error: function() {
                jQuery('#test-status').text('An error occurred while communicating with the server.');
                jQuery('#test-ssl-button').prop('disabled', false);
                jQuery('#loading-icon').hide();
            }
        });
    }

    function checkSSLTestStatus() {
        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'check_ssl_test_status',
                nonce: jQuery('#ssl_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'completed') {
                        clearInterval(checkStatusInterval);
                        displaySSLResults(response.data.data);
                    } else if (response.data.status === 'in_progress') {
                        jQuery('#test-status').text(response.data.message);
                    }
                } else {
                    clearInterval(checkStatusInterval);
                    jQuery('#test-status').text('Error checking SSL test status: ' + response.data);
                    jQuery('#test-ssl-button').prop('disabled', false);
                    jQuery('#loading-icon').hide();
                }
            },
            error: function() {
                clearInterval(checkStatusInterval);
                jQuery('#test-status').text('An error occurred while communicating with the server.');
                jQuery('#test-ssl-button').prop('disabled', false);
                jQuery('#loading-icon').hide();
            }
        });
    }


    function displaySSLResults(data) {
        jQuery('#ssl-results').html(data);
        jQuery('#test-status').text('SSL test completed successfully.');
        jQuery('#test-ssl-button').prop('disabled', false).val('Retest SSL');
        jQuery('#loading-icon').hide();
        setupSSLTabs();
    }


    function initSSLTabs(containerId) {
        try {
            const container = jQuery(`#${containerId}`);
            if (container.length === 0) {
                console.error(`Container with ID "${containerId}" not found.`);
                return;
            }

            container.find('.ssl-tab-links a').off('click').on('click', function(e) {
                e.preventDefault();
                var targetTab = jQuery(this).attr('href');
                console.log("Target tab:", targetTab);

                if (!targetTab) {
                    console.error("Target tab attribute is missing or empty.");
                    return;
                }

                // Remove active class from all tabs and contents
                container.find('.ssl-tab-links li').removeClass('active');
                container.find('.ssl-tab').removeClass('active');

                // Add active class to current tab and content
                jQuery(this).parent('li').addClass('active');
                
                var targetElement = container.find(targetTab);
                if (targetElement.length === 0) {
                    console.error(`Target tab element "${targetTab}" not found.`);
                    return;
                }
                targetElement.addClass('active');
                
                console.log("Tab clicked:", targetTab);
            });

            console.log("SSL Tabs initialized for container:", containerId);
        } catch (error) {
            console.error("Error in initSSLTabs:", error);
        }
    }


jQuery(document).ready(function($) {
    // Handle SSL testing form submission
    $('#ssl-testing-form').on('submit', function(event) {
        event.preventDefault();
        $('#test-status').text('Initiating SSL test, please wait...');
        $('#test-ssl-button').prop('disabled', true);
        $('#loading-icon').show();
        startSSLTest();
    });

    // Initialize tabs on page load if results are already present
    if ($('.ssl-test-results').length > 0) {
        setupSSLTabs();
    }
});
</script>