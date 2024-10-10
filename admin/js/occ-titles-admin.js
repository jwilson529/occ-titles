(function($) {
	'use strict';

	$(document).ready(function() {

		// Array of helpful sentences about what makes a great title
		const titleTips = [
			"Keep your title concise but descriptive.",
			"Use numbers to create structure, e.g., '5 Ways to...'.",
			"Incorporate power words like 'amazing', 'effective', or 'ultimate'.",
			"Use questions to spark curiosity.",
			"Focus on benefits and what the reader will learn.",
			"Include keywords for better SEO and searchability.",
			"Create a sense of urgency or importance.",
			"Use action-oriented language to encourage engagement.",
			"Highlight a problem and promise a solution.",
			"Make use of 'How-To' titles for instructional content.",
			"Keep your audience in mind—what do they want to know?",
			"Try using comparisons or contrasts, like 'This vs. That'.",
			"Use storytelling elements to connect emotionally.",
			"Avoid clickbait—be honest and accurate in your titles.",
			"Try adding a surprising element to pique interest.",
			"Match your title style to the content type (news, opinion, etc.).",
			"Leverage trends and current events when appropriate.",
			"Experiment with different lengths and word choices.",
			"Focus on clarity—what is the main takeaway for the reader?",
			"Ask yourself, 'Would I click on this title?'"
		];

		let tipIndex = 0; // To track the current index of title tips
		let tipInterval; // Variable to store the interval ID

		// Function to cycle through title tips every few seconds
		function displayNextTip() {
			const tipContainer = $('#occ_titles_spinner_text');
			tipContainer.fadeOut(function() {
				// Change the text to the next tip and then fade it back in
				tipContainer.html(titleTips[tipIndex]);
				tipContainer.fadeIn();
				// Update index and reset if at the end of the array
				tipIndex = (tipIndex + 1) % titleTips.length;
			});
		}

		// Append spinner to the body for loading state with the initial message
		$('body').append(`
			<div id="occ_titles_spinner_wrapper" class="occ-spinner-wrapper">
				<div id="occ_titles_spinner" class="occ-spinner"></div>
				<div id="occ_titles_spinner_text" class="occ-spinner-text">Generating Titles...</div>
			</div>
		`);

		// Function to start displaying tips while the spinner is shown
		function startDisplayingTips() {
			// Show the first tip and then start cycling through others every 4 seconds
			$('#occ_titles_spinner_text').html(titleTips[tipIndex]);
			tipIndex = (tipIndex + 1) % titleTips.length;
			tipInterval = setInterval(displayNextTip, 4000); // Change every 4 seconds
		}

		// Function to stop displaying tips when the spinner is hidden
		function stopDisplayingTips() {
			clearInterval(tipInterval); // Stop the interval
			tipIndex = 0; // Reset the index
		}

		/* ============================================= */
		/* ===== Initial Setup & Mode Detection ======== */
		/* ============================================= */

		// Function to check the current editor mode (Classic or Block)
		function checkEditorMode() {
			var isClassicEditor = document.querySelector('.wp-editor-area') !== null;
			var isBlockEditor = !isClassicEditor;
			return { isClassicEditor, isBlockEditor };
		}

		// Initialize the editor mode
		let editorMode = checkEditorMode();

		/* ============================================= */
		/* ===== Utility Functions ===================== */
		/* ============================================= */

		// Get SVG Image for the button
		function getSvgImage() {
			return `<img src="${occ_titles_admin_vars.svg_url}" alt="Generate Titles" />`;
		}

		/* ============================================= */
		/* ===== Classic Editor Setup ================== */
		/* ============================================= */

		// Function to add UI elements in Classic Editor
		function addClassicEditorElements() {
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
		}

		/* ============================================= */
		/* ===== Block Editor Setup ==================== */
		/* ============================================= */

		// Function to add UI elements in Block Editor
		function addBlockEditorElements() {
			var observer = new MutationObserver(function(mutations) {
				mutations.forEach(function(mutation) {
					var $blockTitle = $('h1.wp-block-post-title');
					if ($blockTitle.length && $('#occ_titles_svg_button').length === 0) {
						// Create and insert SVG button
						var svgButton = '<button id="occ_titles_svg_button" title="Generate Titles">' + getSvgImage() + '</button>';
						$blockTitle.parent().css('position', 'relative');
						$(svgButton).insertAfter($blockTitle);

						// Insert table container
						$blockTitle.closest('.wp-block-post-title').after('<div id="occ_titles_table_container" style="margin-top: 20px;"></div>');

						// Insert controls container for Block Editor
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
					}
				});
			});

			// Start observing changes in the document body
			observer.observe(document.body, {
				childList: true,
				subtree: true
			});
		}

		/* ============================================= */
		/* ===== Logic for Adding Elements ============= */
		/* ============================================= */

		// Add elements based on detected editor mode
		if (editorMode.isClassicEditor) {
			addClassicEditorElements();
		} else if (editorMode.isBlockEditor) {
			addBlockEditorElements();
		}

		/* ============================================= */
		/* ===== Ajax Request to Generate Titles ======= */
		/* ============================================= */

		// Function to send an Ajax request to generate titles
		function sendAjaxRequest(content, style, nonce) {
			$.ajax({
				url: `${occ_titles_admin_vars.ajax_url}?${new Date().getTime()}`,
				type: 'POST',
				data: {
					action: 'occ_titles_generate_titles',
					content: content,
					style: style,
					nonce: occ_titles_admin_vars.occ_titles_ajax_nonce,
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
							$('#occ_titles--controls-wrapper').show();
							$('#occ_titles_spinner_wrapper, #occ_titles_svg_button').fadeOut();
							stopDisplayingTips(); // Stop displaying tips after completion
						}
					} else {
						// Handle known error scenario with custom message
						displayCustomError(response.data.message || "An unknown error occurred.");
					}
				},
				error: function() {
					// Handle general error scenario with a friendly message
					displayCustomError('We encountered an issue connecting to the server. Please check your API key and try again.');
				}
			});
		}

		// Function to display a custom error message in the spinner area
		function displayCustomError(errorMessage) {
			stopDisplayingTips(); // Stop displaying tips
			$('#occ_titles_spinner_text')
				.hide()
				.removeClass('occ-spinner-text')
				.addClass('occ-error-text') // Apply error styling
				.html(errorMessage)
				.fadeIn();

			setTimeout(() => {
				$('#occ_titles_spinner_wrapper').fadeOut(); // Hide spinner after showing the error
			}, 5000); // Hide after 5 seconds or customize as needed
		}

		/* ============================================= */
		/* ===== Functions for Keyword and Title Display */
		/* ============================================= */

		// Extract keywords from the generated titles
		function extractKeywordsFromTitles(titles) {
			var allKeywords = [];
			titles.forEach(function(title) {
				if (title.keywords && Array.isArray(title.keywords)) {
					allKeywords = allKeywords.concat(title.keywords);
				}
			});
			return allKeywords;
		}

		// Display extracted keywords in the designated area
		function displayKeywords(keywords) {
			var keywordsDisplay = $('#occ_keywords_display');
			keywordsDisplay.html(keywords.length ?
				'Keywords used in density calculation: ' + keywords.join(', ') :
				'No keywords generated.');
		}

		// Display generated titles in a table format
		function displayTitles(titles) {
			$('#occ_titles_controls_container').show();
			$('#occ_titles_table').remove(); // Remove existing titles table if any

			if (Array.isArray(titles)) {
				// Create titles table
				var titlesTable = $(
					`<table id="occ_titles_table" class="widefat fixed" cellspacing="0" style="width: 100%;">
						<thead><tr><th>Title</th><th>Character Count</th><th>Style</th><th>SEO Grade</th><th>Sentiment</th><th>Keyword Density</th><th>Readability</th><th>Overall Score</th></tr></thead>
						<tbody></tbody>
					</table>`
				);

				var tableBody = titlesTable.find('tbody');
				var bestTitle = { score: 0 };

				// Loop through each title and append rows to the table
				titles.forEach(function(title) {
					var charCount = title.text.length;
					var seoGrade = calculateSEOGrade(charCount);
					var sentiment = title.sentiment;
					var sentimentEmoji = getEmojiForSentiment(sentiment);
					var keywordDensity = calculateKeywordDensity(title.text, title.keywords || []);
					var readabilityScore = calculateReadabilityScore(title.text);
					var overallScore = calculateOverallScore(seoGrade.score, sentiment, keywordDensity, readabilityScore);

					// Identify the best title based on overall score
					if (overallScore > bestTitle.score) {
						bestTitle = { title: title.text, score: overallScore };
					}

					// Create a row for the title
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

			} else {
				alert('Unexpected response format. Please try again.');
			}
		}

		/* ============================================= */
		/* ===== Helper Functions ====================== */
		/* ============================================= */

		// Set the title in the editor
		function setTitleInEditor(title) {
			if (editorMode.isBlockEditor) {
				wp.data.dispatch('core/editor').editPost({ title: title });
			} else if ($('input#title').length) {
				var titleInput = $('input#title');
				$('#title-prompt-text').empty();
				titleInput.val(title).focus().blur();
			}
		}

		// Handle Ajax errors with retry mechanism
		function handleAjaxError(errorMessage, content, style, nonce) {
			if (retryCount < maxRetries) {
				retryCount++;
				sendAjaxRequest(content, style, nonce);
			} else {
				$('#occ_titles_spinner_wrapper').fadeOut();
				alert(errorMessage + ' Retrying...');
			}
		}

		/* ============================================= */
		/* ===== Event Listeners & DOM Manipulation ==== */
		/* ============================================= */

		var hasGenerated = false; // Track if titles have been generated
		var originalTitle = ''; // Store original title for revert functionality
		var retryCount = 0; // Retry counter for Ajax errors
		var maxRetries = 1; // Max retry count

		// Set initial button text
		$('#occ_titles_button').html('Generate Titles');

		// Event listener for style dropdown change
		$('#occ_titles_style').on('change', function() {
			updateButtonText();
		});

		// Function to update button text based on dropdown selection and generation state
		function updateButtonText() {
			var selectedStyle = $('#occ_titles_style').val();
			var styleText = $('#occ_titles_style option:selected').text();
			var buttonText = hasGenerated && selectedStyle ?
				'Generate 5 More ' + styleText + ' Titles' :
				'Generate Titles';
			$('#occ_titles_button').html(buttonText);
		}

		// Append spinner to the body for loading state
		$('body').append(`
			<div id="occ_titles_spinner_wrapper" class="occ-spinner-wrapper">
				<div id="occ_titles_spinner" class="occ-spinner"></div>
				<div id="occ_titles_spinner_text" class="occ-spinner-text">Generating Titles...</div>
			</div>
		`);

		// Event listener for title generation buttons (Classic and Block Editor)
		$(document).on('click', '#occ_titles_generate_button, #occ_titles_button, #occ_titles_svg_button', function(e) {
			e.preventDefault();

			// Start displaying tips and show the spinner
			startDisplayingTips();
			$('#occ_titles_spinner_wrapper').fadeIn();

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

			var style = $('#occ_titles_style').val() || '';
			var nonce = occ_titles_admin_vars.occ_titles_ajax_nonce;

			$('#occ_titles_spinner_wrapper').fadeIn();
			retryCount = 0;
			sendAjaxRequest(content, style, nonce);
		});

		// Event listener for revert button
		$(document).on('click', '#occ_titles_revert_button', function(e) {
			e.preventDefault();
			setTitleInEditor(originalTitle);
		});

	});
})(jQuery);