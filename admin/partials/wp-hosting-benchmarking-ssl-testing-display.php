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
        <form id="ssl-testing-form">
            <input type="submit" id="test-ssl-button" class="button button-primary" value="Test SSL">
            <p id="test-status"></p>
        </form>
    <?php endif; ?>
</div>

<script>


jQuery(document).ready(function($) {
    // Handle registration
    $('#ssl-registration-form').on('submit', function(event) {
        event.preventDefault();

        var email = $('#email').val();
        var invalidDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
        var emailDomain = email.split('@')[1];

        // Check if email is from a disallowed domain
        if (invalidDomains.includes(emailDomain)) {
            $('#email-error').text('Email addresses from Gmail, Yahoo, Outlook, or Hotmail are not allowed.');
            return;
        }

        var formData = $(this).serialize();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ssl_registration',
                form_data: formData,
                nonce: '<?php echo wp_create_nonce('ssl_registration_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload(); // Reload the page after successful registration
                } else {
                    $('#email-error').text(response.data);
                }
            }
        });
    });

    // Handle SSL testing with polling
    $('#ssl-testing-form').on('submit', function(event) {
        event.preventDefault();

        $('#test-status').text('Initiating SSL test, please wait...');
        $('#test-ssl-button').prop('disabled', true);
        $('#loading-icon').show(); // Show the loading icon

        startSSLTest();
    });

    function startSSLTest() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ssl_testing',
                nonce: '<?php echo wp_create_nonce('ssl_testing_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status && response.data.status !== 'READY') {
                        // Test is still in progress, update status and poll again
                        $('#test-status').html('Status: ' + response.data.status + ' - ' + response.data.message);
                        setTimeout(startSSLTest, 10000); // Poll again after 10 seconds
                    } else {
                        // Test is complete, display results
                        $('#test-status').html(response.data);
                        setupSSLTabs(); // Initialize tabs after results are displayed
                        $('#test-ssl-button').prop('disabled', false);
                        $('#loading-icon').show(); // Show the loading icon
                        
                    }
                } else {
                    $('#test-status').text('Error testing SSL: ' + response.data);
                    $('#test-ssl-button').prop('disabled', false);
                    $('#loading-icon').hide(); // Hide the loading icon

                }
            },
            error: function() {
                $('#test-status').text('An error occurred while communicating with the server.');
                $('#test-ssl-button').prop('disabled', false);
                $('#loading-icon').hide();
            }
        });
    }
});

function initSSLTabs(containerId) {
    try {
        const container = jQuery(`#${containerId}`);
        if (container.length === 0) {
            console.error(`Container with ID "${containerId}" not found.`);
            return;
        }

        container.find('.ssl-tab-links a').on('click', function(e) {
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

// This function will be called after the AJAX request is complete
function setupSSLTabs() {
    const sslResultsContainer = document.querySelector('.ssl-test-results');
    if (sslResultsContainer) {
        initSSLTabs(sslResultsContainer.id);
        console.log("SSL Tabs initialized for container: " + sslResultsContainer.id);
    } else {
        console.log("SSL Results container not found");
    }
}

</script>