jQuery(document).ready(function($) {
    var $providerSelect = $('#wp_hosting_benchmarking_selected_provider');
    var $packageSelect = $('#wp_hosting_benchmarking_selected_package');

    $providerSelect.on('change', function() {
        var provider = $(this).val();
        $packageSelect.empty().append('<option value="">Select a package</option>');

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
                        packages.forEach(function(package) {
                            $packageSelect.append($('<option>', {
                                value: package.type,
                                text: package.type
                            }));
                        });
                    }
                }
            });
        }
    });

    // Trigger change event on page load if a provider is already selected
    if ($providerSelect.val()) {
        $providerSelect.trigger('change');
    }
});