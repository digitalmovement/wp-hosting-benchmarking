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

    // Handle SSL testing
    $('#ssl-testing-form').on('submit', function(event) {
        event.preventDefault();

        $('#test-status').text('Testing SSL, please wait...');
        $('#test-ssl-button').prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ssl_testing',
                nonce: '<?php echo wp_create_nonce('ssl_testing_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').html('<strong>SSL Test Result:</strong><br>' + response.data);
                } else {
                    $('#test-status').text('Error testing SSL.');
                }
                $('#test-ssl-button').prop('disabled', false); // Re-enable button
            }
        });
    });
});
</script>
