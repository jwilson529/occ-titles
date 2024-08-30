(function($) {
    'use strict';

    $(document).ready(function() {
        console.log( occ_titles_admin_vars.svg_url );
        var isBlockEditor = typeof wp !== 'undefined' && typeof wp.data !== 'undefined';

        function getSvgImage() {
            return '<img src="' + occ_titles_admin_vars.svg_url + '" alt="Generate Titles" style="width: 30px; height: 30px; vertical-align: bottom; cursor: pointer;" />';
        }

        if (!isBlockEditor) {
            // Classic Editor Logic
            var $titleInput = $('#title');
            if ($titleInput.length) {
                $titleInput.css('position', 'relative');
                $titleInput.after('<button id="occ_titles_generate_button" class="button" type="button" title="Generate Titles">' + getSvgImage() + '</button>');

                // Position the button within the input field
                $('#occ_titles_generate_button').css({
                    position: 'absolute',
                    right: '0px',
                    top: '3px',
                    background: 'transparent',
                    border: 'none',
                    cursor: 'pointer'
                });

                // Insert the container after the title in Classic Editor
                $('#titlediv').after('<div id="occ_titles_table_container" style="margin-top: 20px;"></div>');
            }
        } else {
            // Block Editor Logic
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    var $blockTitle = $('h1.wp-block-post-title');
                    if ($blockTitle.length && $('#occ_titles_svg_button').length === 0) {
                        console.log("Found Block Editor Title Element:", $blockTitle);

                        // Create the SVG button
                        var svgButton = '<button id="occ_titles_svg_button" title="Generate Titles">' + getSvgImage() + '</button>';
                        
                        // Insert the button after the title element
                        $blockTitle.parent().css('position', 'relative');
                        $(svgButton).insertAfter($blockTitle);

                        // Insert the container for the titles table
                        $blockTitle.closest('.wp-block-post-title').after('<div id="occ_titles_table_container" style="margin-top: 20px;"></div>');

                        observer.disconnect(); // Stop observing once the element is added
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        // Add revert button and keywords display area after title div
        $('#occ_titles_table_container').after(`
            <button id="occ_titles_revert_button" class="occ-titles-revert-button" style="display:none;">
                <span class="dashicons dashicons-undo" style="margin-right: 5px;"></span> Revert To Original Title
            </button>
            <div id="occ_keywords_display" style="margin-top: 20px; font-weight: bold;"></div>
        `);

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

        // Event handler for the "Generate Titles" button
        $(document).on('click', '#occ_titles_generate_button, #occ_titles_button, #occ_titles_svg_button', function(e) {
            e.preventDefault();

            hasGenerated = true; // Mark titles as generated
            updateButtonText();

            // Show the dropdown and label after the first click
            if (hasGenerated) {
                $('.occ_titles_style_label, .occ_titles_style_dropdown').show();
            }

            originalTitle = isBlockEditor ? 
                wp.data.select('core/editor').getEditedPostAttribute('title') : 
                $('input#title').val();
            var content = isBlockEditor ? 
                wp.data.select('core/editor').getEditedPostContent() : 
                $('textarea#content').val();

            console.log('Original Title:', originalTitle);
            console.log('Content:', content);

            var style = $('#occ_titles_style').val() || ''; // Get the selected style
            var nonce = occ_titles_admin_vars.occ_titles_ajax_nonce;

            $('#occ_titles_spinner_wrapper').fadeIn();
            retryCount = 0;
            sendAjaxRequest(content, style, nonce);
        });

        // Event handler for the "Revert Title" button
        $(document).on('click', '#occ_titles_revert_button', function(e) {
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
            console.log('Sending AJAX request with content:', content, 'style:', style);
            $.ajax({
                url: occ_titles_admin_vars.ajax_url + '?' + new Date().getTime(),
                type: 'POST',
                data: {
                    action: 'occ_titles_generate_titles',
                    content: content,
                    style: style,
                    nonce: occ_titles_admin_vars.occ_titles_ajax_nonce,
                },
                success: function(response) {
                    console.log('AJAX response:', response);
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
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
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

            console.log('Titles received for display:', titles);

            if (Array.isArray(titles)) {
                var titlesTable = $('<table id="occ_titles_table" class="widefat fixed" cellspacing="0" style="width: 100%;"><thead><tr><th>Title</th><th>Character Count</th><th>Style</th><th>SEO Grade</th><th>Sentiment</th><th>Keyword Density</th><th>Readability</th><th>Overall Score</th></tr></thead><tbody></tbody></table>');
                var tableBody = titlesTable.find('tbody');
                var bestTitle = { score: 0 };

                titles.forEach(function(title) {
                    console.log('Processing title:', title);

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

                $('#occ_titles_table_container').append(titlesTable);
                console.log('Titles table appended to container');
            } else {
                alert('Unexpected response format. Please try again.');
            }
        }

        /**
         * Set the post title in the WordPress editor.
         *
         * @param {string} title The title to set in the editor.
         */
        function setTitleInEditor(title) {
            if (isBlockEditor) {
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
         * Handle AJAX errors with retry logic.
         *
         * @param {string} errorMessage The error message to display.
         */
        function handleAjaxError(errorMessage, content, style, nonce) {
            if (retryCount < maxRetries) {
                retryCount++;
                console.log('Retrying AJAX request. Attempt:', retryCount);
                sendAjaxRequest(content, style, nonce);
            } else {
                $('#occ_titles_spinner_wrapper').fadeOut();
                alert(errorMessage + ' Retrying...');
            }
        }

    });
})(jQuery);
