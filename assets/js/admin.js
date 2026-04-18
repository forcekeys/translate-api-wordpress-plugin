/**
 * Forcekeys Translation - Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Product meta box translation button
        $('#fkt-translate-product').on('click', function() {
            var btn = $(this);
            var spinner = $('#fkt-translate-spinner');
            var status = $('#fkt-translate-status');
            var productId = $('#post_ID').val();
            var targetLang = $('#fkt_translate_lang').val();

            btn.prop('disabled', true);
            spinner.css('visibility', 'visible');
            status.hide();

            $.ajax({
                url: fktAdminConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fkt_translate_product',
                    nonce: fktAdminConfig.nonce,
                    product_id: productId,
                    target_lang: targetLang
                },
                success: function(response) {
                    if (response.success) {
                        status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>').show();
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        status.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>').show();
                    }
                },
                error: function() {
                    status.html('<div class="notice notice-error"><p>Translation failed. Please try again.</p></div>').show();
                },
                complete: function() {
                    btn.prop('disabled', false);
                    spinner.css('visibility', 'hidden');
                }
            });
        });

        // Cache clear button
        $('#fkt-clear-cache').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('Are you sure you want to clear all cached translations?')) {
                return;
            }

            var btn = $(this);
            btn.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: fktAdminConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fkt_clear_cache',
                    nonce: fktAdminConfig.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert('Cache cleared successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    btn.prop('disabled', false).text('Clear Cache');
                }
            });
        });
    });

})(jQuery);
