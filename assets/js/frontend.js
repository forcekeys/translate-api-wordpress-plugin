/**
 * Forcekeys Translation - Frontend JavaScript
 */

(function($) {
    'use strict';

    // Wait for DOM
    $(document).ready(function() {
        ForcekeysTranslation.init();
    });

    const ForcekeysTranslation = {
        init: function() {
            this.bindEvents();
            this.initLanguageSelector();
        },

        bindEvents: function() {
            // Translation shortcode elements
            $('.fkt-translatable').on('click', this.handleTranslateClick.bind(this));
            
            // Language selector
            $(document).on('change', '.fkt-lang-select', this.handleLanguageChange.bind(this));
            $(document).on('click', '.fkt-lang-list a', this.handleLanguageListClick.bind(this));
        },

        /**
         * Handle click on translatable element
         */
        handleTranslateClick: function(e) {
            e.preventDefault();
            
            const $element = $(e.currentTarget);
            const text = $element.data('text');
            const from = $element.data('from');
            const to = $element.data('to');
            const id = $element.attr('id');
            
            if (!text) return;

            // Show loading state
            const originalText = $element.text();
            $element.text(fktConfig.i18n.translating).addClass('fkt-loading');

            // Make AJAX request
            $.ajax({
                url: fktConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fkt_translate_text',
                    nonce: fktConfig.nonce,
                    text: text,
                    source_lang: from,
                    target_lang: to
                },
                success: function(response) {
                    if (response.success) {
                        $element.fadeOut(200, function() {
                            $(this).html(response.data.translation).fadeIn(200);
                        });
                    } else {
                        $element.text(originalText).removeClass('fkt-loading');
                        alert(fktConfig.i18n.error + ': ' + response.data.message);
                    }
                },
                error: function() {
                    $element.text(originalText).removeClass('fkt-loading');
                    alert(fktConfig.i18n.error);
                }
            });
        },

        /**
         * Initialize language selector dropdown
         */
        initLanguageSelector: function() {
            const $selector = $('.fkt-language-selector');
            
            if ($selector.length === 0) return;

            // Check for saved preference
            const savedLang = this.getCookie('fkt_target_lang');
            if (savedLang) {
                this.translatePage(savedLang);
            }
        },

        /**
         * Handle dropdown language change
         */
        handleLanguageChange: function(e) {
            const lang = $(e.currentTarget).val();
            this.setCookie('fkt_target_lang', lang, 30);
            this.translatePage(lang);
        },

        /**
         * Handle list language click
         */
        handleLanguageListClick: function(e) {
            e.preventDefault();
            const lang = $(e.currentTarget).data('lang');
            this.setCookie('fkt_target_lang', lang, 30);
            this.translatePage(lang);
            
            // Update active state
            $(e.currentTarget).closest('.fkt-lang-list').find('li').removeClass('active');
            $(e.currentTarget).closest('li').addClass('active');
        },

        /**
         * Translate entire page content
         */
        translatePage: function(targetLang) {
            const self = this;
            
            // Get all translatable elements
            const $elements = $('[data-translate]');
            
            if ($elements.length === 0) return;

            // Check if page has translatable content
            const textElements = $('h1, h2, h3, h4, h5, h6, p, li, td, th, span, a, div').filter(function() {
                return $(this).children().length === 0 && $.trim($(this).text()).length > 0;
            });

            // Store original texts
            const texts = [];
            textElements.each(function() {
                const $el = $(this);
                const text = $.trim($el.text());
                
                if (text.length > 3 && text.length < 2000) {
                    texts.push({
                        element: this,
                        original: text
                    });
                }
            });

            if (texts.length === 0) return;

            // Show loading indicator
            $('body').addClass('fkt-translating');

            // Batch translate
            $.ajax({
                url: fktConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'fkt_batch_translate',
                    nonce: fktConfig.nonce,
                    texts: texts.map(t => t.original),
                    target_lang: targetLang
                },
                success: function(response) {
                    if (response.success) {
                        response.data.translations.forEach((translation, index) => {
                            if (!translation.error && texts[index]) {
                                $(texts[index].element).fadeOut(100, function() {
                                    $(this).text(translation.translation).fadeIn(100);
                                });
                            }
                        });
                    }
                },
                complete: function() {
                    $('body').removeClass('fkt-translating');
                }
            });
        },

        /**
         * Cookie utilities
         */
        setCookie: function(name, value, days) {
            const expires = new Date();
            expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
            document.cookie = name + '=' + encodeURIComponent(value) + ';expires=' + expires.toUTCString() + ';path=/';
        },

        getCookie: function(name) {
            const nameEQ = name + '=';
            const ca = document.cookie.split(';');
            for (let i = 0; i < ca.length; i++) {
                let c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
            return null;
        }
    };

    // Expose globally
    window.ForcekeysTranslation = ForcekeysTranslation;

})(jQuery);
