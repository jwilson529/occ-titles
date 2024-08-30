(function($) {
    'use strict';

    initializeAutoSave();

    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function() {
            const context = this;
            const args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(context, args), wait);
        };
    }

    // Initialize auto-save functionality for settings fields
    function initializeAutoSave() {
        console.log('initializeAutoSave');
        $('.occ_titles-settings-form').find('input, select, textarea').on('input change', debounce(function() {
            showNotification('Saving settings....', 'success');
            autoSaveField($(this));
        }, 500));
    }

    let isProcessing = false; // Flag to prevent multiple simultaneous AJAX requests

    // Auto-save the field value via AJAX
    function autoSaveField($field) {
        if (isProcessing) return; // If already processing, exit to prevent multiple requests

        isProcessing = true; // Set the flag to indicate processing has started

        var fieldValue;
        var fieldName = $field.attr('name');

        // Handle checkbox fields
        if ($field.attr('type') === 'checkbox') {
            fieldValue = [];
            $('input[name="' + fieldName + '"]:checked').each(function() {
                fieldValue.push($(this).val());
            });
        } else {
            fieldValue = $field.val();
        }

        $.ajax({
                url: occ_titles_admin_vars.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'occ_titles_auto_save',
                    nonce: occ_titles_admin_vars.occ_titles_ajax_nonce,
                    field_name: fieldName.replace('[]', ''),
                    field_value: fieldValue
                }
            })
            .done(function(response) {
                if (response.success) {
                    if (response.data && response.data.new_assistant_id) {
                        $('input[name="occ_titles_assistant_id"]').val(response.data.new_assistant_id);
                        showNotification('New Assistant ID created and saved successfully.', 'success');
                    } else {
                        showNotification(response.data.message || 'Settings saved successfully.', 'success');
                    }
                } else {
                    showNotification(response.data.message || 'Failed to save settings.', 'error');
                }
            })
            .fail(function() {
                showNotification('Error saving settings.', 'error');
            })
            .always(function() {
                isProcessing = false; // Reset the flag after processing is complete
            });
    }

    /**
     * Show a notification message.
     *
     * @param {String} message The message to display.
     * @param {String} type The type of notification (success, error).
     */
    function showNotification(message, type = 'success') {
        // Hide and remove any existing notifications
        $('.occ_titles-notification').fadeOut('fast', function() {
            $(this).remove();
        });

        // Create and show the new notification
        var $notification = $('<div class="occ_titles-notification ' + type + '">' + message + '</div>');
        $('body').append($notification);
        $notification.fadeIn('fast');

        setTimeout(function() {
            $notification.fadeOut('slow', function() {
                $notification.remove();
            });
        }, type === 'success' && message.includes('New Assistant ID') ? 3000 : 2000); // Extend time for important messages
    }



})(jQuery);