(function($) {
    'use strict';

    $(document).ready(function() {
        $('#occ_titles_button').click(function(e) {
            // Prevent the default form submission
            e.preventDefault();

            var content = $('#editor').length ? wp.data.select('core/editor').getEditedPostContent() : $('textarea#content').val();
            var nonce = occ_titles_admin_vars.occ_titles_ajax_nonce;

            console.log('AJAX request data:', {
                action: 'occ_titles_generate_titles',
                content: content,
                nonce: nonce
            });

            $.ajax({
                url: occ_titles_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'occ_titles_generate_titles',
                    content: content,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('AJAX success response:', response);
                    if (response.success) {
                        var titles = response.data.titles;
                        console.log('Titles data:', titles);

                        if (Array.isArray(titles)) {
                            var titlesList = $('<ol id="occ_titles_titles_list"></ol>');

                            titles.forEach(function(title) {
                                var titleItem = $('<li></li>');
                                var titleLink = $('<a href="#">').text(title.text);
                                titleLink.click(function(e) {
                                    e.preventDefault();
                                    $('#occ_titles_titles_list a').css('font-weight', 'normal');
                                    $(this).css('font-weight', 'bold');

                                    // Set the selected title
                                    setTitleInEditor(title.text);
                                });
                                titleItem.append(titleLink);
                                titlesList.append(titleItem);
                            });

                            $('#titlediv').after(titlesList);
                        } else {
                            console.error('Expected titles to be an array, received:', titles);
                            alert('Unexpected response format. Please try again.');
                        }
                    } else {
                        alert(response.data.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX error:', textStatus, errorThrown);
                    console.log('AJAX error response:', jqXHR.responseText);
                    console.log('AJAX request status:', jqXHR.status);
                    console.log('AJAX request status text:', jqXHR.statusText);
                    alert('Error generating titles.');
                }
            });
        });

        function setTitleInEditor(title) {
            if ($('#editor').length) {
                wp.data.dispatch('core/editor').editPost({ title: title });
            } else if ($('input#title').length) {
                var titleInput = $('input#title');
                $('#title-prompt-text').empty();
                titleInput.val(title).focus().blur();
            } else {
                console.error('Unable to set the title: Editor element not found.');
            }
        }
    });

})(jQuery);
