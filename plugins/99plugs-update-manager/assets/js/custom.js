jQuery(function($) {
    let noticeTimeout;
    // Add new row
    $('.p99-append-row').click(function() {
        let row = $('.rows-wrapper .row:first-child').prop('outerHTML').replaceAll('[0]', "[" + $('.rows-wrapper .row').length + "]");
        $('.rows-wrapper').append(row); // Append cloned row
        $('.rows-wrapper .row:last-child input').val(''); // Clear input values
        $('.rows-wrapper .row:last-child .no-clone').html(''); // Clear non-clone elements
    });

    // Handle form submission with AJAX
    $('#p99-form').ajaxForm({
        dataType: 'json',
        complete: function(xhr) {
            setTimeout(() => $('.status-message .notice').hide(), 5000);
            let noticeClass = xhr.responseJSON.status == 200 ? 'success' : 'error';
            $('.status-message').html(`<div class="notice notice-${noticeClass} is-dismissible"><p>${xhr.responseJSON.message}</p></div>`);
            if(xhr.responseJSON.status == 200) {
                xhr.responseJSON.names.forEach((name, i) => {
                    p99ButtonsHandler(xhr.responseJSON.statuses[i], name, xhr.responseJSON.keys[i], $('.rows-wrapper .row').eq(i));
                });
            }
        },
        error: function() {
            alert('Something went wrong!');
        }
    });

    // Remove a row
    $(document).on('click', '.remove-btn', function() {
        $(this).parents('.row').remove();
    });

    // Dynamic actions (Activate/Deactivate/Check)
    $(document).on('click', '.dynamic-actions', function() {
        let $elem = $(this), $parent = $elem.parents('.row');
        $parent.find('.response_status').html(' ');
        $.ajax({
            url: jsObject.ajaxURL,
            method: 'POST',
            dataType: "JSON",
            data: {
                action: $elem.attr('data-action'),
                name: $elem.attr('data-name'),
                key: $elem.attr('data-key')
            },
            success: function(response) {
                $parent.find('.response_status').html(response.message);
                p99ButtonsHandler(response.state, $elem.attr('data-name'), $elem.attr('data-key'), $parent);
                clearTimeout(noticeTimeout); // Clear any existing timeout
                noticeTimeout = setTimeout(() => $('.status-message .notice').hide(), 5000); // Set a new timeout
                let noticeClass = ['Activated successfully.', 'Deactivated successfully.', 'Your update key is active.'].includes(response.message) ? 'success' : 'error';
                $('.status-message').html(`<div class="notice notice-${noticeClass} is-dismissible"><p>${response.message}</p></div>`);
            }
        });
    });

    // Renew button action
    $(document).on('click', '.renew-button', function(e) {
        e.preventDefault();
        let name = $(this).data('name');
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'plugs99_renew',
                name: name
            },
            success: function(response) {
                if(response) window.open(response, '_blank');
            }
        });
    });

    // Dismiss notification
    $(document).on('click', '.p99-renew-now, #p99-expiration-notice .notice-dismiss', function(e) {
        let notificationId = $(this).closest('.notice').attr('id');
        $.ajax({
            url: jsObject.ajaxURL,
            type: 'POST',
            data: {
                action: 'p99_dismiss_notification',
                p99_nonce: jsObject.p99_nonce,
            },
            success: function() {
                $('#' + notificationId).fadeOut();
            },
            error: function(xhr) {
                console.error(xhr.responseText);
            }
        });
    });

    // Update UI based on action status
    const p99ButtonsHandler = (status, name, key, $parent) => {
        let html = '';
        if(status == 'Active') {
            html = `<button data-action="plugs99_check" type="button" data-name="${name}" class="button dynamic-actions">CHECK</button>
                    <button type="button" data-action="plugs99_deactivate" data-name="${name}" class="button dynamic-actions">DEACTIVATE</button>`;
        } else if(status == 'Inactive') {
            html = `<button type="button" data-action="plugs99_activate" data-name="${name}" class="button dynamic-actions">ACTIVATE</button>`;
        } else if (status === 'Expired') {
            html = `<button type="button" data-action="plugs99_activate" data-name="${name}" class="button dynamic-actions">ACTIVATE</button>
                    <button type="button" data-action="plugs99_renew" data-name="${name}" class="button dynamic-actions renew-button">RENEW</button>`;
        } else if(status == '') {
            html = `<button type="button" data-action="plugs99_activate" data-name="${name}" class="button dynamic-actions">ACTIVATE</button>`;
        }
        if(name == '' || key == '') html = ''; // Do not show buttons if key or name is missing

        $parent.find('.prime-actions').html(html);
        $parent.find('[name*="status"]').val(status);
        $parent.find('.response_status').html(status == '' ? 'Inactive' : status);

        let $key_input = $parent.find('input[name^="data["][name$="][keyvalue]"]');
        if($key_input.val() !== '') {
            $key_input.val("********************");
        }
    }
});
