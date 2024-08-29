(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Move the "Generate Titles" meta box directly under the post title.
         */
        var $metaBox = $('#occ_titles_meta_box');
        var $titleDiv = $('#titlediv');

        if ($metaBox.length && $titleDiv.length) {
            $metaBox.insertAfter($titleDiv);
        }

        /**
         * Initialize auto-save for all settings fields.
         */
        initializeAutoSave();

        var hasGenerated = false; // Flag to track if titles have been generated

        // Set initial button text
        $('#occ_titles_button').html('Generate Titles');

        // Event listener for style dropdown change
        $('#occ_titles_style').on('change', function() {
            updateButtonText();
        });

        /**
         * Update the button text based on the selected style.
         */
        function updateButtonText() {
            var selectedStyle = $('#occ_titles_style').val();
            var styleText = $('#occ_titles_style option:selected').text();

            // Set button text based on whether titles have been generated
            var buttonText = hasGenerated && selectedStyle ? 
                'Generate 5 More ' + styleText + ' Titles' : 
                'Generate Titles';

            $('#occ_titles_button').html(buttonText);
        }

        /**
         * Initialize auto-save functionality for settings fields.
         */
        function initializeAutoSave() {
            $('.occ_titles-settings-form').find('input, select, textarea').on('input change', debounce(function() {
                autoSaveField($(this));
            }, 500));
        }

        /**
         * Auto-save the field value via AJAX.
         *
         * @param {Object} $field The jQuery object for the field.
         */
        function autoSaveField($field) {
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
                showNotification(response.success ? 'Settings saved successfully.' : 'Failed to save settings.', response.success ? 'success' : 'error');
            })
            .fail(function() {
                showNotification('Error saving settings.', 'error');
            });
        }

        /**
         * Debounce function to limit the rate at which a function can fire.
         *
         * @param {Function} func The function to debounce.
         * @param {Number} wait The time to wait before executing the function.
         * @returns {Function} The debounced function.
         */
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this;
                const args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        /**
         * Show a notification message.
         *
         * @param {String} message The message to display.
         * @param {String} type The type of notification (success, error).
         */
        function showNotification(message, type = 'success') {
            var $notification = $('<div class="occ_titles-notification ' + type + '">' + message + '</div>');
            $('body').append($notification);
            $notification.fadeIn('fast');

            setTimeout(function() {
                $notification.fadeOut('slow', function() {
                    $notification.remove();
                });
            }, 2000);
        }

        // Variables for handling title generation and retry logic
        var originalTitle = '';
        var retryCount = 0;
        var maxRetries = 1;

        // Add spinner and loading text to the DOM
        $('body').append(`
            <div id="occ_titles_spinner_wrapper" class="occ-spinner-wrapper">
                <div id="occ_titles_spinner" class="occ-spinner"></div>
                <div id="occ_titles_spinner_text" class="occ-spinner-text">Generating Titles...</div>
            </div>
        `);

        // Add revert button and keywords display area after title div
        $('#titlediv').after(`
            <button id="occ_titles_revert_button" class="occ-titles-revert-button" style="display:none;">
                <span class="dashicons dashicons-arrow-left-alt2" style="margin-right: 5px;"></span> Revert Title
            </button>
            <div id="occ_keywords_display" style="margin-top: 20px; font-weight: bold;"></div>
        `);

        // Event handler for the "Generate Titles" button
        $('#occ_titles_button').click(function(e) {
            e.preventDefault();

            hasGenerated = true; // Mark titles as generated
            updateButtonText();

            // Show the dropdown and label after the first click
            if (hasGenerated) {
                $('.occ_titles_style_label, .occ_titles_style_dropdown').show();
            }

            originalTitle = $('#editor').length ? 
                wp.data.select('core/editor').getEditedPostAttribute('title') : 
                $('input#title').val();
            var content = $('#editor').length ? 
                wp.data.select('core/editor').getEditedPostContent() : 
                $('textarea#content').val();
            var style = $('#occ_titles_style').val() || ''; // Get the selected style
            var nonce = occ_titles_admin_vars.occ_titles_ajax_nonce;

            $('#occ_titles_spinner_wrapper').fadeIn();
            retryCount = 0;
            sendAjaxRequest(content, style, nonce);
        });

        // Event handler for the "Revert Title" button
        $('#occ_titles_revert_button').click(function(e) {
            e.preventDefault(); 
            setTitleInEditor(originalTitle);
        });

        /**
         * Send AJAX request to generate titles based on post content and selected style.
         *
         * @param {string} content The content of the post.
         * @param {string} style The selected style for title generation.
         * @param {string} nonce The AJAX nonce for security.
         */
        function sendAjaxRequest(content, style, nonce) {
            $.ajax({
                url: occ_titles_admin_vars.ajax_url + '?' + new Date().getTime(),
                type: 'POST',
                data: {
                    action: 'occ_titles_generate_titles',
                    content: content,
                    style: style,
                    nonce: nonce,
                },
                success: function(response) {
                    if (response.success) {
                        var titles = response.data.titles || [];
                        var keywords = extractKeywordsFromTitles(titles);
                        displayKeywords(keywords);
                        displayTitles(titles);
                        $('#occ_titles_spinner_wrapper').fadeOut();

                        if (titles.length > 0) {
                            $('#occ_titles_revert_button').show();
                        }
                    } else {
                        handleAjaxError(response.data.message, content, style, nonce);
                    }
                },
                error: function() {
                    handleAjaxError('Error generating titles.', content, style, nonce);
                }
            });
        }

        /**
         * Extract keywords from an array of titles.
         *
         * @param {Array} titles An array of title objects.
         * @return {Array} An array of extracted keywords.
         */
        function extractKeywordsFromTitles(titles) {
            var allKeywords = [];
            titles.forEach(function(title) {
                if (title.keywords && Array.isArray(title.keywords)) {
                    allKeywords = allKeywords.concat(title.keywords);
                }
            });
            return allKeywords;
        }

        /**
         * Handle AJAX errors with retry logic.
         *
         * @param {string} errorMessage The error message to display.
         */
        function handleAjaxError(errorMessage, content, style, nonce) {
            if (retryCount < maxRetries) {
                retryCount++;
                sendAjaxRequest(content, style, nonce);
            } else {
                $('#occ_titles_spinner_wrapper').fadeOut();
                alert(errorMessage + ' Retrying...');
            }
        }

        /**
         * Set the post title in the WordPress editor.
         *
         * @param {string} title The title to set in the editor.
         */
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

        /**
         * Display extracted keywords in the UI.
         *
         * @param {Array} keywords An array of keywords to display.
         */
        function displayKeywords(keywords) {
            var keywordsDisplay = $('#occ_keywords_display');
            keywordsDisplay.html(keywords.length ? 
                'Keywords used in density calculation: ' + keywords.join(', ') : 
                'No keywords generated.');
        }

        /**
         * Display generated titles in a table format in the UI.
         *
         * @param {Array} titles An array of title objects to display.
         */
        function displayTitles(titles) {
            $('#occ_titles_table').remove(); // Remove existing titles table

            if (Array.isArray(titles)) {
                var titlesTable = $('<table id="occ_titles_table" class="widefat fixed" cellspacing="0"><thead><tr><th>Title</th><th>Character Count</th><th>Style</th><th>SEO Grade</th><th>Sentiment</th><th>Keyword Density</th><th>Readability</th><th>Overall Score</th></tr></thead><tbody></tbody></table>');
                var tableBody = titlesTable.find('tbody');
                var bestTitle = { score: 0 };

                titles.forEach(function(title) {
                    var charCount = title.text.length;
                    var seoGrade = calculateSEOGrade(charCount);
                    var sentiment = title.sentiment;
                    var sentimentEmoji = getEmojiForSentiment(sentiment);
                    var keywordDensity = calculateKeywordDensity(title.text, title.keywords || []);
                    var readabilityScore = calculateReadabilityScore(title.text);
                    var overallScore = calculateOverallScore(seoGrade.score, sentiment, keywordDensity, readabilityScore);

                    if (overallScore > bestTitle.score) {
                        bestTitle = { title: title.text, score: overallScore };
                    }

                    var titleRow = $('<tr></tr>');
                    var titleCell = $('<td></td>').append($('<a href="#">').text(title.text).click(function(e) {
                        e.preventDefault();
                        $('#occ_titles_table a').css('font-weight', 'normal');
                        $(this).css('font-weight', 'bold');
                        setTitleInEditor(title.text);
                    }));

                    titleRow.append(
                        titleCell,
                        $('<td></td>').text(charCount),
                        $('<td></td>').text(title.style),
                        $('<td class="emoji"></td>').html(seoGrade.dot).attr('title', seoGrade.label),
                        $('<td class="emoji"></td>').text(sentimentEmoji).attr('title', sentiment),
                        $('<td></td>').text((keywordDensity * 100).toFixed(2) + '%'),
                        $('<td></td>').text(readabilityScore.toFixed(2)),
                        $('<td></td>').text(overallScore.toFixed(2))
                    );

                    tableBody.append(titleRow);
                });

                tableBody.find('tr').each(function() {
                    if ($(this).find('a').text() === bestTitle.title) {
                        $(this).css('background-color', '#d4edda');
                    }
                });

                $('#titlediv').after(titlesTable);
            } else {
                alert('Unexpected response format. Please try again.');
            }
        }

    });

})(jQuery);
