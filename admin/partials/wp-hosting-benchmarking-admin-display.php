<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://fastestwordpress.com
 * @since      1.0.0
 *
 * @package    Wp_Hosting_Benchmarking
 * @subpackage Wp_Hosting_Benchmarking/admin/partials
 */
?><div class="wrap">
<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
<div id="latency-test-container">
    <button id="start-test" class="button button-primary">Start Latency Test</button>
    <button id="stop-test" class="button button-secondary" style="display:none;">Stop Latency Test</button>
    <p id="test-status"></p>
    <div id="countdown"></div>
</div>
<div id="results-container">
    <h2>Latest Results</h2>
    <table id="latency-results" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Region</th>
                <th>Current Latency (ms)</th>
                <th>Difference</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be populated here -->
        </tbody>
    </table>
</div>
<div id="latency-graph"></div>
<button id="delete-results" class="button button-secondary">Delete All Results</button>
</div>

<style>
.latency-faster { color: green; }
.latency-slower { color: red; }
#latency-results th, #latency-results td { padding: 8px; }
</style>

<script>
jQuery(document).ready(function($) {
    var countdownInterval;
    var isRunning = false; // Initialize isRunning as false
    var lastResults = {};

    function updateButtonState(isRunning) {
        $('#start-test').prop('disabled', isRunning); // Disable start button if running
        $('#start-test').toggle(!isRunning);
        $('#stop-test').toggle(isRunning);
    }

    function startCountdown(duration, startTime) {
        var timer = duration - (Math.floor(Date.now() / 1000) - startTime), minutes, seconds;
        countdownInterval = setInterval(function () {
            if (timer <= 0) {
                clearInterval(countdownInterval);
                $('#test-status').text('Test completed.');
                isRunning = false; // Update running status
                updateButtonState(false); // Re-enable the start button
                return;
            }

            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#countdown').text(minutes + ":" + seconds);
            timer--;
        }, 1000);
    }

    function checkTestStatus() {
        var startTime = parseInt(wpHostingBenchmarking.start_time, 10);
        if (startTime) {
            var currentTime = Math.floor(Date.now() / 1000);
            var elapsedTime = currentTime - startTime;
            if (elapsedTime < 3600) {
                $('#test-status').text('Test running...');
                isRunning = true; // Update running status
                updateButtonState(true); // Disable start button while running
                startCountdown(3600, startTime);
            }
        }
    }


$('#reset-test').on('click', function() {
    $.ajax({
        url: wpHostingBenchmarking.ajax_url,
        type: 'POST',
        data: {
            action: 'reset_latency_test',
            nonce: wpHostingBenchmarking.nonce
        },
        success: function(response) {
            if (response.success) {
                $('#test-status').text('Test reset successfully.');
                updateButtonState(false);
                clearInterval(countdownInterval);
                $('#countdown').text('');
            } else {
                alert(response.data);
            }
        }
    });
});


        $('#start-test').prop('disabled', isRunning).toggle(!isRunning);
        $('#stop-test').toggle(isRunning);
    

     $('#start-test').on('click', function() {
        isRunning = true; // Set running status
        updateButtonState(true); // Disable start button

        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'start_latency_test',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Test started. Running for 1 hour.');
                    startCountdown(3600, Math.floor(Date.now() / 1000));
                } else {
                    alert(response.data);
                    isRunning = false; // Reset running status if there's an error
                    updateButtonState(false); // Re-enable start button
                }
            }
        });
    });

    // Stop test button click handler
    $('#stop-test').on('click', function() {
        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'stop_latency_test',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#test-status').text('Test stopped.');
                    clearInterval(countdownInterval); // Stop the countdown
                    isRunning = false; // Update running status
                    updateButtonState(false); // Re-enable start button
                    $('#countdown').text('');
                }
            }
        });
    });


    function startCountdown(duration) {
        var timer = duration, minutes, seconds;
        countdownInterval = setInterval(function () {
            minutes = parseInt(timer / 60, 10);
            seconds = parseInt(timer % 60, 10);

            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;

            $('#countdown').text(minutes + ":" + seconds);

            if (--timer < 0) {
                stopCountdown();
                updateButtonState(false);
                $('#test-status').text('Test completed.');
            }
        }, 1000);
    }

    function stopCountdown() {
        clearInterval(countdownInterval);
        $('#countdown').text('');
    }

    function updateResults() {
    $.ajax({
        url: wpHostingBenchmarking.ajax_url,
        type: 'POST',
        data: {
            action: 'get_latest_results',
            nonce: wpHostingBenchmarking.nonce
        },
        success: function(response) {
            console.log('Server response:', response); // Add this line
            if (response.success) {
                updateResultsTable(response.data);
                updateGraph(response.data);
            } else {
                console.error('Error in server response:', response);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error('AJAX request failed:', textStatus, errorThrown);
        }
    });
}
function updateResultsTable(results) {
    var tableBody = $('#latency-results tbody');
    tableBody.empty();

    results.forEach(function(result) {
        var row = $('<tr>');

        // Convert latency to a number
        var latency = parseFloat(result.latency);
        var latencyDifference = parseFloat(result.latency_difference);

        // Check if latency is a valid number
        if (isNaN(latency)) {
            console.error('Invalid latency value:', result.latency);
            latency = 0; // Default value if invalid
        }

        // Check if latency difference is valid
        var diffText = 'N/A';
        var diffClass = '';
        if (!isNaN(latencyDifference)) {
            diffText = latencyDifference > 0 ? '+' + latencyDifference.toFixed(1) : latencyDifference.toFixed(1);
            diffClass = latencyDifference < 0 ? 'latency-faster' : (latencyDifference > 0 ? 'latency-slower' : '');
        }

        // Create the table row
        row.append($('<td>').text(result.region_name));
        row.append($('<td>').text(latency.toFixed(1) + ' ms'));
        row.append($('<td>').addClass(diffClass).text(diffText));
        row.append($('<td>').text(formatDate(result.test_time)));

        tableBody.append(row);

        // Update last results for future comparisons
        lastResults[result.region_name] = latency;
    });
}
function calculateLatencyDiff(region, currentLatency) {
    if (lastResults.hasOwnProperty(region)) {
        return currentLatency - lastResults[region];
    }
    return null;
}

function formatDate(dateString) {
    var date = new Date(dateString);
    return date.toLocaleString();
}

function updateGraph(data) {
    // Implement this function to update the graph
    // You might want to use a library like Chart.js for this
}

// ... (rest of the JavaScript code remains the same)

// Initial update and set interval for periodic updates
checkTestStatus();
updateResults();
setInterval(updateResults, 60000); // Update every minute

});
</script>