(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

})( jQuery );


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


