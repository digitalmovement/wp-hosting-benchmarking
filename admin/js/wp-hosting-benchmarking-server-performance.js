jQuery(document).ready(function($) {
    'use strict';

    var testStatus = wpHostingBenchmarkingPerformance.testStatus;
    var chart;

    // Initialize tabs
    $('#server-performance-tabs').tabs();

    function updateButtonState(status) {
        var $button = $('#start-stop-test');
        $button.data('status', status);
        $button.text(status === 'running' ? 'Stop Test' : 'Start Test');
        $('#test-progress').toggle(status === 'running');
    }

    $('#start-stop-test').on('click', function() {
        var status = $(this).data('status');
        var newStatus = status === 'running' ? 'stopped' : 'running';

        $.ajax({
            url: wpHostingBenchmarkingPerformance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_hosting_benchmarking_performance_toggle_test',
                status: newStatus,
                _ajax_nonce: wpHostingBenchmarkingPerformance.nonce
            },
            success: function(response) {
                if (response.success) {
                    updateButtonState(newStatus);
                    if (newStatus === 'running') {
                        startBackgroundTest();
                    }
                } else {
                    alert('Failed to toggle test status. Please try again.');
                }
            }
        });
    });

    function startBackgroundTest() {
        $.ajax({
            url: wpHostingBenchmarkingPerformance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_hosting_benchmarking_performance_run_test',
                _ajax_nonce: wpHostingBenchmarkingPerformance.nonce
            }
        });
    }

    function loadResults() {
        $.ajax({
            url: wpHostingBenchmarkingPerformance.ajaxurl,
            method: 'POST',
            data: {
                action: 'wp_hosting_benchmarking_performance_get_results',
                _ajax_nonce: wpHostingBenchmarkingPerformance.nonce
            },
            success: function(response) {
                if (response.success && response.data) {
                    displayResults(response.data);
                }
            }
        });
    }

    function displayResults(data) {
        var ctx = document.getElementById('results-chart').getContext('2d');

        if (chart) {
            chart.destroy();
        }

        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['CPU & Memory', 'Filesystem', 'Database', 'Object Cache'],
                datasets: [
                    {
                        label: 'Your Results',
                        data: [
                            data.cpu_memory,
                            data.filesystem,
                            data.database,
                            data.object_cache
                        ],
                        backgroundColor: 'rgba(75, 192, 192, 0.6)'
                    },
                    {
                        label: 'Industry Average',
                        data: [
                            data.industry_avg.cpu_memory,
                            data.industry_avg.filesystem,
                            data.industry_avg.database,
                            data.industry_avg.object_cache
                        ],
                        backgroundColor: 'rgba(153, 102, 255, 0.6)'
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Time (seconds)'
                        }
                    }
                }
            }
        });
    }

    updateButtonState(testStatus);
    loadResults();
});