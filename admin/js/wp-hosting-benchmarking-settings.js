jQuery(document).ready(function($) {
    var $providerSelect = $('#wp_hosting_benchmarking_selected_provider');
    var $packageSelect = $('#wp_hosting_benchmarking_selected_package');
    var selectedPackage = $packageSelect.val(); // Store the initially selected package

    function updatePackages(provider, callback) {
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
                        if (callback) callback();
                    }
                }
            });
        }
    }

    $providerSelect.on('change', function() {
        var provider = $(this).val();
        updatePackages(provider);
    });

    // If a provider is already selected on page load
    if ($providerSelect.val()) {
        updatePackages($providerSelect.val(), function() {
            // After populating packages, set the previously selected package
            if (selectedPackage) {
                $packageSelect.val(selectedPackage);
            }
        });
    }
});