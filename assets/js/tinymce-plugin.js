/**
 * Forcekeys Translation - TinyMCE Plugin
 * 
 * Adds a translation button to the WordPress editor
 */

(function() {
    'use strict';

    tinymce.PluginManager.add('forcekeys_translation', function(editor, url) {
        // Add button
        editor.addButton('forcekeys_translation', {
            title: 'Insert Translation',
            icon: 'dashicons-translator',
            onclick: function() {
                // Open modal
                editor.windowManager.open({
                    title: 'Insert Translation',
                    width: 500,
                    height: 400,
                    body: [
                        {
                            type: 'textbox',
                            name: 'text',
                            label: 'Text to Translate',
                            multiline: true,
                            minHeight: 100
                        },
                        {
                            type: 'listbox',
                            name: 'from',
                            label: 'From Language',
                            values: [
                                { text: 'Auto Detect', value: 'auto' },
                                { text: 'English', value: 'en' },
                                { text: 'French', value: 'fr' },
                                { text: 'Spanish', value: 'es' },
                                { text: 'German', value: 'de' },
                                { text: 'Italian', value: 'it' },
                                { text: 'Portuguese', value: 'pt' },
                                { text: 'Russian', value: 'ru' },
                                { text: 'Chinese', value: 'zh' },
                                { text: 'Japanese', value: 'ja' },
                                { text: 'Korean', value: 'ko' }
                            ],
                            value: 'auto'
                        },
                        {
                            type: 'listbox',
                            name: 'to',
                            label: 'To Language',
                            values: [
                                { text: 'English', value: 'en' },
                                { text: 'French', value: 'fr' },
                                { text: 'Spanish', value: 'es' },
                                { text: 'German', value: 'de' },
                                { text: 'Italian', value: 'it' },
                                { text: 'Portuguese', value: 'pt' },
                                { text: 'Russian', value: 'ru' },
                                { text: 'Chinese', value: 'zh' },
                                { text: 'Japanese', value: 'ja' },
                                { text: 'Korean', value: 'ko' }
                            ],
                            value: 'en'
                        },
                        {
                            type: 'checkbox',
                            name: 'loading',
                            label: 'Interactive (click to translate)',
                            checked: true
                        }
                    ],
                    onsubmit: function(e) {
                        var text = e.data.text;
                        var from = e.data.from;
                        var to = e.data.to;
                        var loading = e.data.loading;

                        if (!text) {
                            alert('Please enter text to translate');
                            return;
                        }

                        var shortcode;

                        if (loading) {
                            // Interactive shortcode
                            shortcode = '[translate text="' + 
                                text.replace(/"/g, '"') + 
                                '" from="' + from + 
                                '" to="' + to + 
                                '" loading="true"]';
                        } else {
                            // Static shortcode (server-side translation)
                            shortcode = '[translate text="' + 
                                text.replace(/"/g, '"') + 
                                '" from="' + from + 
                                '" to="' + to + 
                                '" loading="false"]';
                        }

                        editor.insertContent(shortcode);
                    }
                });
            }
        });
    });

})();
