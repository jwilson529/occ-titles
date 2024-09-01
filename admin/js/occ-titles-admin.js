(function($) {
    'use strict';

    $(document).ready(function() {
        console.log("SVG URL:", occ_titles_admin_vars.svg_url);

        // Function to check the editor mode
        function checkEditorMode() {
            var isClassicEditor = document.querySelector('.wp-editor-area') !== null;
            var isBlockEditor = !isClassicEditor;
            console.log("Is Classic Editor:", isClassicEditor);
            console.log("Is Block Editor:", isBlockEditor);
            return { isClassicEditor, isBlockEditor };
        }

        // Initialize the editor mode
        let editorMode = checkEditorMode();

        // Function to get SVG image
        function getSvgImage() {
            console.log("Generating SVG Image...");
            return '<img src="' + occ_titles_admin_vars.svg_url + '" alt="Generate Titles" />';
        }

        // Function to add elements in Classic Editor
        function addClassicEditorElements() {
            var $titleInput = $('#title');
            if ($titleInput.length) {
                console.log("Title Input Found:", $titleInput);
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
                console.log("Generate Button and Container added in Classic Editor");
            } else {
                console.log("Title Input Not Found in Classic Editor");
            }
        }

        // Function to add elements in Block Editor
        function addBlockEditorElements() {
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
                        console.log("SVG Button Added After Title Element in Block Editor");

                        // Insert the container for the titles table
                        $blockTitle.closest('.wp-block-post-title').after('<div id="occ_titles_table_container" style="margin-top: 20px;"></div>');

                        // Add controls container in Block Editor
                        $('#occ_titles_table_container').after(`
                            <div id="occ_titles_controls_container" style="margin-top: 20px;">
                                <button id="occ_titles_revert_button" class="occ-titles-revert-button" style="display:none;">
                                    <span class="dashicons dashicons-undo" style="margin-right: 5px;"></span> Revert To Original Title
                                </button>
                                
                                <div id="occ_titles--controls-wrapper" style="margin-bottom: 20px; display: none;">
                                    <label for="occ_titles_style" style="margin-right: 10px;" class="occ_titles_style_label">Select Style:</label>
                                    <select id="occ_titles_style" name="occ_titles_style" class="occ_titles_style_dropdown">
                                        <option value="" disabled selected>Choose a Style...</option>
                                        ${['How-To', 'Listicle', 'Question', 'Command', 'Intriguing Statement', 'News Headline', 'Comparison', 'Benefit-Oriented', 'Storytelling', 'Problem-Solution']
                                            .map(style => `<option value="${style.toLowerCase()}">${style}</option>`).join('')}
                                    </select>
                                    <button id="occ_titles_button" class="button button-primary">Generate Titles</button>
                                </div>

                                <div id="occ_keywords_display" style="margin-top: 20px; font-weight: bold;"></div>
                            </div>
                        `);

                        observer.disconnect(); // Stop observing once the element is added
                        console.log("MutationObserver disconnected in Block Editor");
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });

            console.log("MutationObserver started in Block Editor");
        }

        // Logic to apply based on the detected editor
        if (editorMode.isClassicEditor) {
            console.log("Classic Editor Detected");
            addClassicEditorElements();
        } else if (editorMode.isBlockEditor) {
            console.log("Block Editor Detected");
            addBlockEditorElements();
        }

        console.log("Adding controls container and buttons.");

        // Add revert button, dropdown, and keywords display area after title div
        $('#occ_titles_table_container').after(`
            <div id="occ_titles_controls_container" style="margin-top: 20px;">
                <button id="occ_titles_revert_button" class="occ-titles-revert-button" style="display:none;">
                    <span class="dashicons dashicons-undo" style="margin-right: 5px;"></span> Revert To Original Title
                </button>
                
                <div id="occ_titles--controls-wrapper" style="margin-bottom: 20px; display: none;">
                    <label for="occ_titles_style" style="margin-right: 10px;" class="occ_titles_style_label">Select Style:</label>
                    <select id="occ_titles_style" name="occ_titles_style" class="occ_titles_style_dropdown">
                        <option value="" disabled selected>Choose a Style...</option>
                        ${['How-To', 'Listicle', 'Question', 'Command', 'Intriguing Statement', 'News Headline', 'Comparison', 'Benefit-Oriented', 'Storytelling', 'Problem-Solution']
                            .map(style => `<option value="${style.toLowerCase()}">${style}</option>`).join('')}
                    </select>
                    <button id="occ_titles_button" class="button button-primary">Generate Titles</button>
                </div>

                <div id="occ_keywords_display" style="margin-top: 20px; font-weight: bold;"></div>
            </div>
        `);

        console.log("Controls container added.");

        var hasGenerated = false; // Flag to track if titles have been generated

        // Set initial button text
        $('#occ_titles_button').html('Generate Titles');
        console.log("Initial button text set.");

        // Event listener for style dropdown change
        $('#occ_titles_style').on('change', function() {
            updateButtonText();
        });

        function updateButtonText() {
            var selectedStyle = $('#occ_titles_style').val();
            var styleText = $('#occ_titles_style option:selected').text();

            var buttonText = hasGenerated && selectedStyle ? 
                'Generate 5 More ' + styleText + ' Titles' : 
                'Generate Titles';

            $('#occ_titles_button').html(buttonText);
            console.log("Button text updated to:", buttonText);
        }

        var originalTitle = '';
        var retryCount = 0;
        var maxRetries = 1;

        $('body').append(`
            <div id="occ_titles_spinner_wrapper" class="occ-spinner-wrapper">
                <div id="occ_titles_spinner" class="occ-spinner"></div>
                <div id="occ_titles_spinner_text" class="occ-spinner-text">Generating Titles...</div>
            </div>
        `);

        $(document).on('click', '#occ_titles_generate_button, #occ_titles_button, #occ_titles_svg_button', function(e) {
            e.preventDefault();

            hasGenerated = true; 
            updateButtonText();

            if (hasGenerated) {
                $('.occ_titles_style_label, .occ_titles_style_dropdown').show();
            }

            originalTitle = editorMode.isBlockEditor ? 
                wp.data.select('core/editor').getEditedPostAttribute('title') : 
                $('input#title').val();
            var content = editorMode.isBlockEditor ? 
                wp.data.select('core/editor').getEditedPostContent() : 
                $('textarea#content').val();

            console.log('Original Title:', originalTitle);
            console.log('Content:', content);

            var style = $('#occ_titles_style').val() || ''; 
            var nonce = occ_titles_admin_vars.occ_titles_ajax_nonce;

            $('#occ_titles_spinner_wrapper').fadeIn();
            retryCount = 0;
            sendAjaxRequest(content, style, nonce);
        });

        $(document).on('click', '#occ_titles_revert_button', function(e) {
            e.preventDefault(); 
            setTitleInEditor(originalTitle);
        });

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
                            $('#occ_titles--controls-wrapper').show();
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

        function extractKeywordsFromTitles(titles) {
            var allKeywords = [];
            titles.forEach(function(title) {
                if (title.keywords && Array.isArray(title.keywords)) {
                    allKeywords = allKeywords.concat(title.keywords);
                }
            });
            return allKeywords;
        }

        function displayKeywords(keywords) {
            var keywordsDisplay = $('#occ_keywords_display');
            keywordsDisplay.html(keywords.length ? 
                'Keywords used in density calculation: ' + keywords.join(', ') : 
                'No keywords generated.');
        }

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

        function setTitleInEditor(title) {
            if (editorMode.isBlockEditor) {
                wp.data.dispatch('core/editor').editPost({ title: title });
            } else if ($('input#title').length) {
                var titleInput = $('input#title');
                $('#title-prompt-text').empty();
                titleInput.val(title).focus().blur();
            } else {
                console.error('Unable to set the title: Editor element not found.');
            }
        }

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
