<?php
/**
 * Forcekeys Shortcodes Class
 * 
 * Handles frontend shortcodes for translation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Forcekeys_Shortcodes {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('translate', array($this, 'translate_shortcode'));
        add_shortcode('fkt-translate', array($this, 'translate_shortcode')); // Alias
        add_shortcode('fkt-language-selector', array($this, 'language_selector_shortcode'));
        
        // Add button to TinyMCE editor
        add_action('admin_init', array($this, 'add_tinymce_button'));
    }

    /**
     * Translate shortcode
     * 
     * Usage: [translate text="Hello World" from="en" to="fr"]
     *        [translate from="auto" to="es"]Hello World[/translate]
     */
    public function translate_shortcode($atts, $content = null) {
        $atts = shortcode_atts(array(
            'text' => '',
            'from' => get_option('fkt_default_source_lang', 'auto'),
            'to' => get_option('fkt_default_target_lang', 'en'),
            'cache' => 'true',
            'loading' => 'true',
        ), $atts);

        // Get text from content or attribute
        $text = !empty($atts['text']) ? $atts['text'] : $content;
        
        if (empty($text)) {
            return '';
        }

        // If loading is disabled, return original text
        if ($atts['loading'] === 'false') {
            $api = new Forcekeys_API();
            $result = $api->translate($text, $atts['from'], $atts['to']);
            
            if (is_wp_error($result)) {
                return $text;
            }
            
            return $result;
        }

        // Return interactive translation element
        $id = 'fkt-' . uniqid();
        
        return sprintf(
            '<span class="fkt-translatable" data-text="%s" data-from="%s" data-to="%s" data-cache="%s" id="%s">%s</span>',
            esc_attr($text),
            esc_attr($atts['from']),
            esc_attr($atts['to']),
            esc_attr($atts['cache']),
            esc_attr($id),
            esc_html($text)
        );
    }

    /**
     * Language selector shortcode
     * 
     * Usage: [fkt-language-selector]
     */
    public function language_selector_shortcode($atts) {
        $atts = shortcode_atts(array(
            'style' => 'dropdown', // dropdown, list, flags
            'show_flags' => 'true',
            'class' => '',
        ), $atts);

        $api = new Forcekeys_API();
        $languages = $api->get_languages();
        
        if (is_wp_error($languages)) {
            return '';
        }

        $current_lang = isset($_COOKIE['fkt_target_lang']) ? $_COOKIE['fkt_target_lang'] : get_option('fkt_default_target_lang', 'en');
        
        $output = '<div class="fkt-language-selector ' . esc_attr($atts['class']) . '" data-style="' . esc_attr($atts['style']) . '">';
        
        if ($atts['style'] === 'dropdown') {
            $output .= '<select class="fkt-lang-select" data-current="' . esc_attr($current_lang) . '">';
            foreach ($languages as $lang) {
                $selected = ($lang['code'] === $current_lang) ? 'selected' : '';
                $flag = $atts['show_flags'] === 'true' ? $this->get_language_flag($lang['code']) . ' ' : '';
                $output .= '<option value="' . esc_attr($lang['code']) . '" ' . $selected . '>' . $flag . esc_html($lang['name']) . '</option>';
            }
            $output .= '</select>';
        } elseif ($atts['style'] === 'list') {
            $output .= '<ul class="fkt-lang-list">';
            foreach ($languages as $lang) {
                $active = ($lang['code'] === $current_lang) ? ' class="active"' : '';
                $flag = $atts['show_flags'] === 'true' ? $this->get_language_flag($lang['code']) : '';
                $output .= '<li' . $active . '><a href="#" data-lang="' . esc_attr($lang['code']) . '">' . $flag . ' ' . esc_html($lang['name']) . '</a></li>';
            }
            $output .= '</ul>';
        }
        
        $output .= '</div>';
        
        return $output;
    }

    /**
     * Get language flag emoji
     */
    private function get_language_flag($code) {
        $flags = array(
            'en' => '🇺🇸', 'fr' => '🇫🇷', 'es' => '🇪🇸', 'de' => '🇩🇪',
            'it' => '🇮🇹', 'pt' => '🇵🇹', 'ru' => '🇷🇺', 'zh' => '🇨🇳',
            'ja' => '🇯🇵', 'ko' => '🇰🇷', 'ar' => '🇸🇦', 'nl' => '🇳🇱',
            'pl' => '🇵🇱', 'tr' => '🇹🇷', 'uk' => '🇺🇦', 'sv' => '🇸🇪',
            'da' => '🇩🇰', 'fi' => '🇫🇮', 'no' => '🇳🇴', 'cs' => '🇨🇿',
            'el' => '🇬🇷', 'he' => '🇮🇱', 'hi' => '🇮🇳', 'th' => '🇹🇭',
            'vi' => '🇻🇳', 'id' => '🇮🇩', 'ms' => '🇲🇾', 'ro' => '🇷🇴',
            'hu' => '🇭🇺', 'sk' => '🇸🇰', 'bg' => '🇧🇬', 'hr' => '🇭🇷',
        );
        
        return isset($flags[$code]) ? $flags[$code] : '🌐';
    }

    /**
     * Add TinyMCE button
     */
    public function add_tinymce_button() {
        if (!current_user_can('edit_posts') && !current_user_can('edit_pages')) {
            return;
        }

        if (get_user_option('rich_editing') !== 'true') {
            return;
        }

        add_filter('mce_external_plugins', array($this, 'register_tinymce_plugin'));
        add_filter('mce_buttons', array($this, 'add_tinymce_button_to_toolbar'));
    }

    /**
     * Register TinyMCE plugin
     */
    public function register_tinymce_plugin($plugins) {
        $plugins['forcekeys_translation'] = FKT_PLUGIN_URL . 'assets/js/tinymce-plugin.js';
        return $plugins;
    }

    /**
     * Add button to toolbar
     */
    public function add_tinymce_button_to_toolbar($buttons) {
        $buttons[] = 'forcekeys_translation';
        return $buttons;
    }
}
