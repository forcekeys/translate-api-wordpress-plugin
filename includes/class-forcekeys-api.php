<?php
/**
 * Forcekeys Translation API Class
 * 
 * Handles all API communication with Forcekeys Translation API
 */

if (!defined('ABSPATH')) {
    exit;
}

class Forcekeys_API {

    /**
     * API base URL
     */
    private $api_base_url = FKT_API_BASE_URL;

    /**
     * Constructor
     */
    public function __construct() {
        // API URL can be overridden via filter
        $this->api_base_url = apply_filters('fkt_api_url', get_option('fkt_api_url', FKT_API_BASE_URL));
    }

    /**
     * Get API key
     */
    private function get_api_key() {
        $api_key = get_option('fkt_api_key', '');
        
        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('API key not configured', 'forcekeys-translation'));
        }
        
        return $api_key;
    }

    /**
     * Make API request
     */
    private function make_request($endpoint, $method = 'GET', $body = null) {
        $api_key = $this->get_api_key();
        
        if (is_wp_error($api_key)) {
            return $api_key;
        }

        $url = $this->api_base_url . $endpoint;
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ),
            'timeout' => 30,
        );

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 200 && $status_code < 300) {
            return $data;
        } else {
            $error_message = isset($data['message']) ? $data['message'] : __('Unknown API error', 'forcekeys-translation');
            return new WP_Error('api_error', $error_message, $data);
        }
    }

    /**
     * Translate text
     */
    public function translate($text, $source_lang = 'auto', $target_lang = 'en') {
        // Check cache first
        $cached = $this->get_cached_translation($text, $source_lang, $target_lang);
        if ($cached) {
            return $cached;
        }

        $result = $this->make_request('/translate', 'POST', array(
            'text' => $text,
            'source' => $source_lang,
            'target' => $target_lang,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        if (isset($result['translated_text'])) {
            // Cache the translation
            $this->cache_translation($text, $result['translated_text'], $source_lang, $target_lang);
            return $result['translated_text'];
        }

        return new WP_Error('invalid_response', __('Invalid API response', 'forcekeys-translation'));
    }

    /**
     * Detect language
     */
    public function detect_language($text) {
        $result = $this->make_request('/detect', 'POST', array(
            'text' => $text,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        if (isset($result['detected_language'])) {
            return $result;
        }

        return new WP_Error('invalid_response', __('Invalid API response', 'forcekeys-translation'));
    }

    /**
     * Get available languages
     */
    public function get_languages() {
        $cached = get_transient('fkt_languages');
        
        if ($cached) {
            return $cached;
        }

        $result = $this->make_request('/languages', 'GET');

        if (is_wp_error($result)) {
            return $result;
        }

        if (isset($result['languages'])) {
            set_transient('fkt_languages', $result['languages'], WEEK_IN_SECONDS);
            return $result['languages'];
        }

        // Return default languages if API fails
        return $this->get_default_languages();
    }

    /**
     * Default languages fallback
     */
    private function get_default_languages() {
        return array(
            array('code' => 'en', 'name' => 'English'),
            array('code' => 'fr', 'name' => 'French'),
            array('code' => 'es', 'name' => 'Spanish'),
            array('code' => 'de', 'name' => 'German'),
            array('code' => 'it', 'name' => 'Italian'),
            array('code' => 'pt', 'name' => 'Portuguese'),
            array('code' => 'ru', 'name' => 'Russian'),
            array('code' => 'zh', 'name' => 'Chinese'),
            array('code' => 'ja', 'name' => 'Japanese'),
            array('code' => 'ko', 'name' => 'Korean'),
            array('code' => 'ar', 'name' => 'Arabic'),
            array('code' => 'nl', 'name' => 'Dutch'),
            array('code' => 'pl', 'name' => 'Polish'),
            array('code' => 'tr', 'name' => 'Turkish'),
        );
    }

    /**
     * Register site with Forcekeys API
     */
    public function register_site($site_name, $site_url, $plugin_version) {
        $api_key = $this->get_api_key();
        
        if (is_wp_error($api_key)) {
            return $api_key;
        }

        $result = $this->make_request('/wordpress/register', 'POST', array(
            'api_key' => $api_key,
            'site_name' => $site_name,
            'site_url' => $site_url,
            'plugin_version' => $plugin_version,
        ));

        if (is_wp_error($result)) {
            return $result;
        }

        if (isset($result['wordpress_api_key'])) {
            // Store the WordPress API key
            update_option('fkt_wordpress_api_key', $result['wordpress_api_key']);
            update_option('fkt_wordpress_integration_id', $result['integration']['id']);
            
            // Store integration UUID
            update_option('fkt_site_uuid', $result['integration']['site_uuid']);
        }

        return $result;
    }

    /**
     * Get WordPress API key
     */
    private function get_wordpress_api_key() {
        return get_option('fkt_wordpress_api_key', '');
    }

    /**
     * Get site UUID
     */
    public function get_site_uuid() {
        return get_option('fkt_site_uuid', '');
    }

    /**
     * Translate using WordPress-specific API key (with tracking)
     */
    public function translate_with_tracking($text, $source_lang = 'auto', $target_lang = 'en') {
        $wp_api_key = $this->get_wordpress_api_key();
        
        if (empty($wp_api_key)) {
            // Fall back to regular translation
            return $this->translate($text, $source_lang, $target_lang);
        }

        $url = $this->api_base_url . '/wordpress/translate';
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $wp_api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'text' => $text,
                'source' => $source_lang,
                'target' => $target_lang,
            )),
            'timeout' => 30,
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            // Fall back to regular translation
            return $this->translate($text, $source_lang, $target_lang);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code >= 200 && $status_code < 300 && isset($data['translated_text'])) {
            return $data['translated_text'];
        }
        
        // Fall back to regular translation
        return $this->translate($text, $source_lang, $target_lang);
    }

    /**
     * Sync with Forcekeys API
     */
    public function sync_site() {
        $wp_api_key = $this->get_wordpress_api_key();
        $integration_id = get_option('fkt_wordpress_integration_id', 0);
        
        if (empty($wp_api_key) || empty($integration_id)) {
            return false;
        }

        $result = $this->make_request('/wordpress/sync', 'POST', array(
            'integration_id' => $integration_id,
            'plugin_version' => FKT_VERSION,
        ));

        return !is_wp_error($result);
    }

    /**
     * Get cached translation
     */
    private function get_cached_translation($text, $source_lang, $target_lang) {
        if (!get_option('fkt_cache_enabled', '1')) {
            return false;
        }

        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fkt_translations';
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT translated_text FROM $table_name 
            WHERE original_text = %s AND source_lang = %s AND target_lang = %s
            AND created_at > DATE_SUB(NOW(), INTERVAL %d SECOND)",
            $text,
            $source_lang,
            $target_lang,
            get_option('fkt_cache_duration', 86400)
        ));

        return $result ? $result->translated_text : false;
    }

    /**
     * Cache translation
     */
    private function cache_translation($original, $translated, $source_lang, $target_lang) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'fkt_translations';
        
        $wpdb->insert(
            $table_name,
            array(
                'original_text' => $original,
                'translated_text' => $translated,
                'source_lang' => $source_lang,
                'target_lang' => $target_lang,
                'content_type' => 'manual',
            ),
            array('%s', '%s', '%s', '%s', '%s')
        );
    }

    /**
     * AJAX: Translate text
     */
    public function ajax_translate_text() {
        check_ajax_referer('fkt_translate_nonce', 'nonce');

        $text = isset($_POST['text']) ? sanitize_textarea_field($_POST['text']) : '';
        $source_lang = isset($_POST['source_lang']) ? sanitize_text_field($_POST['source_lang']) : 'auto';
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : 'en';

        if (empty($text)) {
            wp_send_json_error(array('message' => __('No text provided', 'forcekeys-translation')));
        }

        $result = $this->translate($text, $source_lang, $target_lang);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'translation' => $result,
            'source_lang' => $source_lang,
            'target_lang' => $target_lang,
        ));
    }

    /**
     * AJAX: Batch translate
     */
    public function ajax_batch_translate() {
        check_ajax_referer('fkt_translate_nonce', 'nonce');

        $texts = isset($_POST['texts']) ? array_map('sanitize_textarea_field', $_POST['texts']) : array();
        $source_lang = isset($_POST['source_lang']) ? sanitize_text_field($_POST['source_lang']) : 'auto';
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : 'en';

        if (empty($texts)) {
            wp_send_json_error(array('message' => __('No texts provided', 'forcekeys-translation')));
        }

        $translations = array();
        
        foreach ($texts as $text) {
            $result = $this->translate($text, $source_lang, $target_lang);
            
            if (is_wp_error($result)) {
                $translations[] = array(
                    'original' => $text,
                    'translation' => $result->get_error_message(),
                    'error' => true,
                );
            } else {
                $translations[] = array(
                    'original' => $text,
                    'translation' => $result,
                    'error' => false,
                );
            }
        }

        wp_send_json_success(array(
            'translations' => $translations,
            'source_lang' => $source_lang,
            'target_lang' => $target_lang,
        ));
    }
}
