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
?><div id="wp-hosting-benchmarking" class="wrap">
<h1>Google Data Center Latency Testing</h1>
<div id="latency-test-container">
    <button id="start-test" class="button button-primary">Start Latency Test</button>
    <button id="stop-test" class="button button-secondary" style="display:none;">Stop Latency Test</button>
    <button id="delete-results" class="button button-secondary delete-button">Delete All Results</button>
    <p id="test-status"></p>
    <div id="countdown"></div>
</div>
<div id="results-container">
<div>
    <label for="time-range">Select Time Range:</label>
    <select id="time-range" class="time-range-dropdown">
        <option value="24_hours">Last 24 Hours</option>
        <option value="7_days">Last 7 Days</option>
        <option value="90_days">Last 90 Days</option>
    </select>
</div>
    <h2>Latest Results</h2>
    <table id="latency-results" class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Region</th>
                <th>Current Latency (ms)</th>
                <th>Fastest Latency (ms)</th>
                <th>Slowest Latency (ms)</th>
                <th>Last Updated</th>
            </tr>
        </thead>
        <tbody>
            <!-- Results will be populated here -->
        </tbody>
    </table>
</div>
<div id="graphs-container">
    <h2>Graphs for Each Region</h2>
</div>

</div>


<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <h2>Discard all latency Data?</h2>
        <p>This cannot be undone.</p>
        <div class="modal-footer">
            <button id="cancelButton" class="button button-secondary">Cancel</button>
            <button id="confirmDelete" class="button discard-button">Discard</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var countdownInterval;
    var isRunning = false; // Initialize isRunning as false
    var lastResults = {};
    var chartInstances = {};

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
    function createGraphContainer(regionName) {
    var container = $('<div>').attr('id', 'graph-container-' + regionName).css({
        height: '300px',
        width: '100%',
        margin: '20px 0' // Add some spacing between graphs
    });

    var title = $('<h3>').text('Graph for ' + regionName); // Add a title to the graph
    var canvas = $('<canvas>').attr('id', 'graph-' + regionName);

    container.append(title); // Append the title to the container
    container.append(canvas); // Append the canvas to the container
    $('#graphs-container').append(container); // Append the new container to the main container
}

function renderGraphs(results) {
    // Group results by region, as we have multiple data points per region
    var regionData = {};

    results.forEach(function(result) {
        // Check if the region already exists in the regionData object
        if (!regionData[result.region_name]) {
            regionData[result.region_name] = {
                labels: [],
                latencies: [],
                lastUpdated: result.test_time // Store the latest update time
            };
        }

        // Parse test_time as a JavaScript Date object and latency as a number
        regionData[result.region_name].labels.push(new Date(result.test_time));
        regionData[result.region_name].latencies.push(parseFloat(result.latency));
        regionData[result.region_name].lastUpdated = result.test_time;

    });

    // Now, create or update charts for each region
    Object.keys(regionData).forEach(function(region) {
        // Dynamically create the canvas element if it doesn't exist
        if (!document.getElementById('graph-' + region)) {
            createGraphContainer(region);
        }

        if (regionData[region].latencies.length < 10) {
            // Show a message if there are fewer than 10 data points

            var ctx = document.getElementById('graph-' + region).getContext('2d');
            var message = "Awaiting more data for " + region;

            // Clear the canvas before drawing the message
            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
            ctx.font = "16px Arial";
            ctx.fillStyle = "black";
            ctx.textAlign = "center";
            ctx.fillText(message, ctx.canvas.width / 2, ctx.canvas.height / 2);
        } else {





                var ctx = document.getElementById('graph-' + region).getContext('2d');

                // Destroy the previous chart instance if it exists
                if (chartInstances[region]) {
                    chartInstances[region].destroy();
                }

                var lastUpdatedTimePlugin = {
                    id: 'lastUpdatedTimePlugin',
                    afterDraw: function(chart) {
                        var ctx = chart.ctx;
                        var chartArea = chart.chartArea;
                        var lastUpdated = regionData[region].lastUpdated;
                        ctx.save();
                        ctx.font = '12px Arial';
                        ctx.fillStyle = 'gray';
                        ctx.textAlign = 'center';

                        // Format the date for display
                        var formattedDate = new Date(lastUpdated).toLocaleString();
                        // Display the last updated time under the chart
                        ctx.fillText("Last updated: " + formattedDate, 
                            (chartArea.left + chartArea.right) / 2, // Centered under the X-axis
                            chartArea.bottom + 30 // Position it below the X-axis
                        );
                        ctx.restore();
                        }
                };


                // Create a new Chart.js instance for the region
                chartInstances[region] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: regionData[region].labels, // X-axis labels (time)
                        datasets: [{
                            label: 'Latency (ms) for ' + region, // Label for the dataset
                            data: regionData[region].latencies, // Y-axis data (latency)
                            borderColor: 'rgba(75, 192, 192, 1)', // Line color
                            borderWidth: 2,
                            fill: false
                        }]
                    },
                    options: {
                        scales: {
                            x: {
                                type: 'time', // Use time scale
                                time: {
                                        unit: 'hour', // Adjust based on your data granularity
                                        tooltipFormat: 'MMM D, h:mm A', // Format for the tooltips
                                        displayFormats: {
                                            hour: 'h:mm A' // Display format for x-axis
                                        }
                                },
                                ticks: {
                                        autoSkip: true, // Automatically skip ticks for better spacing
                                        maxTicksLimit: 6, // Limits the number of ticks shown
                                        source: 'auto',
                                }
                            },
                            y: {
                                beginAtZero: true // Start Y-axis at zero
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    },
                    plugins: [lastUpdatedTimePlugin] 
                });
            }
    });
}


    $('#time-range').on('change', function() {
        var timeRange = $(this).val();

        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'get_results_for_time_range',
                nonce: wpHostingBenchmarking.nonce,
                time_range: timeRange
            },
            success: function(response) {
                if (response.success) {
                    updateResultsTable(response.data);
                    renderGraphs(response.data);
                } else {
                    alert('No results found for the selected time range.');
                }
            }
        });
    });



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

    $('#delete-results').on('click', function() {
        $('#deleteModal').css('display', 'block'); // Show the modal
    });

    // When cancel button is clicked, hide the modal
    $('#cancelButton').on('click', function() {
        $('#deleteModal').css('display', 'none'); // Hide the modal
    });

    // When confirm delete button is clicked, perform the delete action
    $('#confirmDelete').on('click', function() {
        $.ajax({
            url: wpHostingBenchmarking.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_all_results',
                nonce: wpHostingBenchmarking.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').css('display', 'none'); // Hide the modal after successful deletion
                    alert('All latency data discarded');
                    // You may also want to refresh the results table here
                } else {
                    alert('Error discarding results');
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
    var regionData = {};
    var selectedRegion = wpHostingBenchmarking.selected_region; // Get selected region from localized script

    results.forEach(function(result) {
        var region = result.region_name;
        var latency = parseFloat(result.latency); // Parse latency as float
        var fastestLatency = parseFloat(result.fastest_latency);
        var slowestLatency = parseFloat(result.slowest_latency);
        var testTime = result.test_time; // Get the test time


        // If the region doesn't exist in regionData, initialize it
        if (!regionData[region]) {
            regionData[region] = {
                currentLatency: latency,
                fastestLatency: fastestLatency,
                slowestLatency: slowestLatency,
                lastUpdated: testTime
            };
        } else {
            // Update the current latency
            // Update current latency with the latest value
    
            // Update the fastest latency if the current one is smaller
            if (fastestLatency < regionData[region].fastestLatency) {
                regionData[region].fastestLatency = fastestLatency;
            }

            // Update the slowest latency if the current one is larger
            if (slowestLatency > regionData[region].slowestLatency) {
                regionData[region].slowestLatency = slowestLatency;
            }

            // Update last updated time if the current test is more recent
            if (new Date(testTime) > new Date(regionData[region].lastUpdated)) {
                regionData[region].currentLatency = latency;
                regionData[region].lastUpdated = testTime;
            }
        }
    });

    // Create table rows for each region
    Object.keys(regionData).forEach(function(region) {
        var row = $('<tr>');

        // Check if this is the selected region and highlight it
        if (result.region_name === selectedRegion) {
            row.addClass('highlight-row'); // Add a custom class to highlight the row
        }


        row.append($('<td>').text(region));
        row.append($('<td>').text(regionData[region].currentLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(regionData[region].fastestLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(regionData[region].slowestLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(formatDate(regionData[region].lastUpdated)));
        tableBody.append(row);
    });

    /*


    results.forEach(function(result) {
        var region = result.region_name;
        var row = $('<tr>');


        ///





///

        // Convert latency and latency difference to numbers
        var latency = parseFloat(result.latency);
        var latencyDifference = parseFloat(result.latency_difference);
        var fastestLatency = parseFloat(result.fastest_latency);
        var slowestLatency = parseFloat(result.slowest_latency);

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

        // Create the table row with added fastest and slowest latencies
        row.append($('<td>').text(result.region_name));
        row.append($('<td>').text(latency.toFixed(1) + ' ms'));
        row.append($('<td>').addClass(diffClass).text(diffText));
        row.append($('<td>').text(fastestLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(slowestLatency.toFixed(1) + ' ms'));
        row.append($('<td>').text(formatDate(result.test_time)));

        tableBody.append(row);

        // Update last results for future comparisons
        //lastResults[result.region_name] = latency;
    });
    */
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