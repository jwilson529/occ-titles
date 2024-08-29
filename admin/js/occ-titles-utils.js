(function($) {
    'use strict';

    $(document).ready(function() {
        /**
         * Calculate SEO grade based on character count.
         *
         * @param {number} charCount The character count of the title.
         * @return {Object} The SEO grade, score, and label.
         */
        function calculateSEOGrade(charCount) {
            var grade = '';
            var score = 0;
            var label = '';

            if (charCount >= 50 && charCount <= 60) {
                grade = '🟢';
                score = 100;
                label = 'Excellent (50-60 characters)';
            } else if (charCount < 50) {
                grade = '🟡';
                score = 75;
                label = 'Average (below 50 characters)';
            } else {
                grade = '🔴';
                score = 50;
                label = 'Poor (above 60 characters)';
            }

            return { dot: grade, score: score, label: label };
        }

        /**
         * Get emoji representation for the sentiment analysis result.
         *
         * @param {string} sentiment The sentiment of the title.
         * @return {string} The emoji representing the sentiment.
         */
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

        /**
         * Calculate keyword density in the title text.
         *
         * @param {string} text The title text.
         * @param {Array} keywords The keywords to check for density.
         * @return {number} The keyword density as a percentage.
         */
        function calculateKeywordDensity(text, keywords) {
            if (!keywords || !keywords.length) {
                return 0;
            }

            var keywordCount = keywords.reduce(function(count, keyword) {
                return count + (text.match(new RegExp(keyword, 'gi')) || []).length;
            }, 0);
            var wordCount = text.split(' ').length;
            return keywordCount / wordCount;
        }

        /**
         * Calculate readability score of the title text.
         *
         * @param {string} text The title text.
         * @return {number} The readability score.
         */
        function calculateReadabilityScore(text) {
            var wordCount = text.split(' ').length;
            var sentenceCount = text.split(/[.!?]+/).length;
            var syllableCount = text.split(/[aeiouy]+/).length;

            return ((wordCount / sentenceCount) + (syllableCount / wordCount)) * 0.4;
        }

        /**
         * Calculate the overall score of the title based on multiple factors.
         *
         * @param {number} seoScore The SEO score of the title.
         * @param {string} sentiment The sentiment of the title.
         * @param {number} keywordDensity The keyword density in the title.
         * @param {number} readabilityScore The readability score of the title.
         * @return {number} The overall score of the title.
         */
        function calculateOverallScore(seoScore, sentiment, keywordDensity, readabilityScore) {
            var sentimentScore = sentiment === 'Positive' ? 100 : (sentiment === 'Neutral' ? 75 : 50);
            var keywordDensityScore = keywordDensity >= 0.01 && keywordDensity <= 0.03 ? 100 : 50;
            var readabilityScoreNormalized = 100 - Math.abs(readabilityScore - 10) * 10;

            return (seoScore + sentimentScore + keywordDensityScore + readabilityScoreNormalized) / 4;
        }

        // Make functions available globally
        window.calculateSEOGrade = calculateSEOGrade;
        window.getEmojiForSentiment = getEmojiForSentiment;
        window.calculateKeywordDensity = calculateKeywordDensity;
        window.calculateReadabilityScore = calculateReadabilityScore;
        window.calculateOverallScore = calculateOverallScore;

    });

})(jQuery);
