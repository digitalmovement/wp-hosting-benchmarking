

jQuery(document).ready(function($) {
    $('#wp_hosting_benchmarking_selected_provider').on('change', function() {
        var provider = $(this).val();
        if (provider) {
            $.ajax({
                url: wpHostingBenchmarkingSettings.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_provider_packages',
                    provider: provider,
                    nonce: wpHostingBenchmarkingSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var packages = response.data;
                        var packagesHtml = '<h3>Available Packages:</h3><ul>';
                        packages.forEach(function(package) {
                            packagesHtml += '<li><strong>' + package.type + '</strong>: ' + package.description + '</li>';
                        });
                        packagesHtml += '</ul>';
                        $('#provider_packages').html(packagesHtml);
                    }
                }
            });
        } else {
            $('#provider_packages').empty();
        }
    });
});


