jQuery(document).ready(function($) {
    var tab = $('#tab_gravityformsswipego');

    tab.find('#business').on('change', function(e) {
        var business_input       = $(this),
            selected_business_id = business_input.val(),
            api_key_input        = tab.find('#api_key'),
            signature_key_input  = tab.find('#signature_key'),
            integration_input    = tab.find('#integration');

        if (selected_business_id === undefined || selected_business_id === null || selected_business_id === '') {
            api_key_input.val('');
            signature_key_input.val('');
        } else {
            $.ajax({
                url: swipego_gf_retrieve_api_credentials.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'swipego_gf_retrieve_api_credentials',
                    nonce: swipego_gf_retrieve_api_credentials.nonce,
                    business_id: selected_business_id,
                },
                beforeSend: function() {
                    business_input.prop('disabled', true);
                    $('body').css('cursor', 'wait');
                },
                success: function(response) {
                    business_input.prop('disabled', false);
                    $('body').css('cursor', 'auto');

                    if (response.data !== undefined) {
                        if (response.data.api_key !== undefined) {
                            api_key_input.val(response.data.api_key);
                        }

                        if (response.data.signature_key !== undefined) {
                            signature_key_input.val(response.data.signature_key);
                        }

                        if (response.data.integration_id !== undefined) {
                            integration_input.val(response.data.integration_id);
                        }
                    }
                },
                error: function(xhr) {
                    business_input.prop('disabled', false);
                    $('body').css('cursor', 'auto');

                    var error = JSON.parse(xhr.responseText);

                    if (error && error.data && error.data.message) {
                        var message = '<span class="font-medium">Error!</span> ' + error.data.message + '.';
                    } else {
                        var message = 'An error occured! Please try again.';
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        html: message,
                        timer: 3000,
                    });
                }
            });
        }

        e.preventDefault();
    });

    // Handle set webhook
    tab.find('#set-webhook').on('click', function(e) {
        var btn            = $(this),
            btn_text       = btn.text(),
            business_id    = tab.find('#business').val(),
            integration_id = tab.find('#integration').val();

        $.ajax({
            url: swipego_gf_set_webhook.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'swipego_gf_set_webhook',
                nonce: swipego_gf_set_webhook.nonce,
                business_id: business_id,
                integration_id: integration_id,
            },
            beforeSend: function() {
                btn.prop('disabled', true);
                btn.css('cursor', 'wait');
            },
            success: function(response) {
                btn.prop('disabled', false);
                btn.css('cursor', 'pointer');

                Swal.fire({
                    icon: 'success',
                    title: 'Webhook Set!',
                    text: 'Your Gravity Forms webhook URL have been successfully saved in Swipe.',
                    timer: 3000,
                });
            },
            error: function(xhr) {
                btn.prop('disabled', false);
                btn.css('cursor', 'pointer');

                var error = JSON.parse(xhr.responseText);

                if (error && error.data && error.data.message) {
                    var message = '<span class="font-medium">Error!</span> ' + error.data.message + '.';
                } else {
                    var message = 'An error occured! Please try again.';
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Oops...',
                    html: message,
                    timer: 3000,
                });
            }
        });

        e.preventDefault();
    });
});
