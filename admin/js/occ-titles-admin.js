(function($) {
    'use strict';

    $(document).ready(function() {
        
        var originalTitle = '';
        var retryCount = 0;
        var maxRetries = 1;

        // Add spinner wrapper and text to the body
        $('body').append(`
            <div id="occ_titles_spinner_wrapper" class="occ-spinner-wrapper">
                <div id="occ_titles_spinner" class="occ-spinner"></div>
                <div id="occ_titles_spinner_text" class="occ-spinner-text">Generating Titles...</div>
            </div>
        `);

        // Add revert button
        $('#titlediv').after('<button id="occ_titles_revert_button" style="display:none;">Revert Title</button>');

        $('#occ_titles_button').click(function(e) {
            // Prevent the default form submission
            e.preventDefault();

            // Save the original title
            originalTitle = $('#editor').length ? wp.data.select('core/editor').getEditedPostAttribute('title') : $('input#title').val();

            var content = $('#editor').length ? wp.data.select('core/editor').getEditedPostContent() : $('textarea#content').val();
            var nonce = occ_titles_admin_vars.occ_titles_ajax_nonce;

            // Show spinner
            $('#occ_titles_spinner_wrapper').fadeIn();

            // Reset retry count
            retryCount = 0;
            sendAjaxRequest(content, nonce);
        });

        $('#occ_titles_revert_button').click(function() {
            setTitleInEditor(originalTitle);
        });

        function sendAjaxRequest(content, nonce) {
            $.ajax({
                url: occ_titles_admin_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'occ_titles_generate_titles',
                    content: content,
                    nonce: nonce
                },
                success: function(response) {
                    // Hide spinner
                    $('#occ_titles_spinner_wrapper').fadeOut();

                    if (response.success) {
                        displayTitles(response.data.titles);
                        $('#occ_titles_revert_button').show();
                    } else {
                        handleAjaxError(response.data.message);
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    handleAjaxError('Error generating titles.');
                }
            });
        }

        function handleAjaxError(errorMessage) {
            if (retryCount < maxRetries) {
                retryCount++;
                sendAjaxRequest();
            } else {
                $('#occ_titles_spinner_wrapper').fadeOut();
                alert(errorMessage + ' Retrying...');
            }
        }

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

        function displayTitles(titles) {
            if (Array.isArray(titles)) {
                var titlesTable = $('<table id="occ_titles_table" class="widefat fixed" cellspacing="0"><thead><tr><th>Title</th><th>Character Count</th><th>Style</th><th>SEO Grade</th><th>Sentiment</th><th>Keyword Density</th><th>Readability</th><th>Overall Score</th></tr></thead><tbody></tbody></table>');
                var tableBody = titlesTable.find('tbody');
                var bestTitle = { score: 0 };

                titles.forEach(function(title) {
                    var charCount = title.text.length;
                    var seoGrade = calculateSEOGrade(charCount);
                    var sentiment = title.sentiment;
                    var sentimentEmoji = getEmojiForSentiment(sentiment);
                    var keywordDensity = calculateKeywordDensity(title.text, 'AI'); // Replace 'AI' with the actual keyword
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

                        // Set the selected title
                        setTitleInEditor(title.text);
                    }));

                    var charCountCell = $('<td></td>').text(charCount);
                    var styleCell = $('<td></td>').text(title.style);
                    var seoGradeCell = $('<td class="emoji"></td>').html(seoGrade.dot);
                    var sentimentCell = $('<td class="emoji"></td>').text(sentimentEmoji);
                    var keywordDensityCell = $('<td></td>').text((keywordDensity * 100).toFixed(2) + '%');
                    var readabilityCell = $('<td></td>').text(readabilityScore.toFixed(2));
                    var overallScoreCell = $('<td></td>').text(overallScore.toFixed(2));

                    titleRow.append(titleCell, charCountCell, styleCell, seoGradeCell, sentimentCell, keywordDensityCell, readabilityCell, overallScoreCell);
                    tableBody.append(titleRow);
                });

                // Highlight the best title
                tableBody.find('tr').each(function() {
                    var row = $(this);
                    var title = row.find('a').text();
                    if (title === bestTitle.title) {
                        row.css('background-color', '#d4edda');
                    }
                });

                $('#titlediv').after(titlesTable);
            } else {
                alert('Unexpected response format. Please try again.');
            }
        }

        function calculateSEOGrade(charCount) {
            var grade = '';
            var score = 0;

            if (charCount >= 50 && charCount <= 60) {
                grade = '🟢';
                score = 100;
            } else if (charCount < 50) {
                grade = '🟡';
                score = 75;
            } else {
                grade = '🔴';
                score = 50;
            }

            return { dot: grade, score: score };
        }

        function getEmojiForSentiment(sentiment) {
            switch (sentiment) {
                case 'Positive':
                    return '😊';
                case 'Negative':
                    return '😟';
                case 'Neutral':
                    return '😐';
                default:
                    return '❓';
            }
        }

        function calculateKeywordDensity(text, keyword) {
            var keywordCount = (text.match(new RegExp(keyword, 'gi')) || []).length;
            var wordCount = text.split(' ').length;
            return keywordCount / wordCount;
        }

        function calculateReadabilityScore(text) {
            // Simplified readability score calculation (e.g., Flesch-Kincaid, Gunning Fog Index)
            var wordCount = text.split(' ').length;
            var sentenceCount = text.split(/[.!?]+/).length;
            var syllableCount = text.split(/[aeiouy]+/).length;

            return ((wordCount / sentenceCount) + (syllableCount / wordCount)) * 0.4;
        }

        function calculateOverallScore(seoScore, sentiment, keywordDensity, readabilityScore) {
            var sentimentScore = sentiment === 'Positive' ? 100 : (sentiment === 'Neutral' ? 75 : 50);
            var keywordDensityScore = keywordDensity >= 0.01 && keywordDensity <= 0.03 ? 100 : 50;
            var readabilityScoreNormalized = 100 - Math.abs(readabilityScore - 10) * 10; // Assuming ideal readability score is 10

            return (seoScore + sentimentScore + keywordDensityScore + readabilityScoreNormalized) / 4;
        }
    });

})(jQuery);
