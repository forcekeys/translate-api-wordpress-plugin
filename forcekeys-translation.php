<?php
/**
 * Plugin Name: Forcekeys Translation API
 * Plugin URI:  https://forcekeys.com
 * Description: Translate your WordPress content using Forcekeys Translation API. Supports posts, pages, WooCommerce products and more.
 * Version:     1.0.0
 * Author:      Forcekeys
 * Author URI:  https://forcekeys.com
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: forcekeys-translation
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('FKT_VERSION', '1.0.0');
define('FKT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('FKT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('FKT_API_BASE_URL', 'https://api.translate.forcekeys.com/api/v1');

// Include required classes
require_once FKT_PLUGIN_DIR . 'includes/class-forcekeys-api.php';
require_once FKT_PLUGIN_DIR . 'includes/class-forcekeys-admin.php';
require_once FKT_PLUGIN_DIR . 'includes/class-forcekeys-shortcodes.php';
require_once FKT_PLUGIN_DIR . 'includes/class-forcekeys-woocommerce.php';

/**
 * Main Plugin Class
 */
class Forcekeys_Translation_Plugin {

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * API instance
     */
    public $api;

    /**
     * Admin instance
     */
    public $admin;

    /**
     * Shortcodes instance
     */
    public $shortcodes;

    /**
     * WooCommerce instance
     */
    public $woocommerce;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // Initialize API
        $this->api = new Forcekeys_API();
        
        // Initialize components
        $this->init_components();
        
        // Hook into WordPress
        $this->init_hooks();
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Admin (always loaded for settings)
        $this->admin = new Forcekeys_Admin();
        
        // Shortcodes
        $this->shortcodes = new Forcekeys_Shortcodes();
        
        // WooCommerce (only if WooCommerce is active)
        if (class_exists('WooCommerce')) {
            $this->woocommerce = new Forcekeys_WooCommerce();
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Plugin activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Load text domain
        add_action('plugins_loaded', array($this, 'load_text_domain'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

        // AJAX handlers
        add_action('wp_ajax_fkt_translate_text', array($this->api, 'ajax_translate_text'));
        add_action('wp_ajax_nopriv_fkt_translate_text', array($this->api, 'ajax_translate_text'));
        
        // Batch translation AJAX
        add_action('wp_ajax_fkt_batch_translate', array($this->api, 'ajax_batch_translate'));
        add_action('wp_ajax_nopriv_fkt_batch_translate', array($this->api, 'ajax_batch_translate'));
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'fkt_translations';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            original_text longtext NOT NULL,
            translated_text longtext NOT NULL,
            source_lang varchar(10) DEFAULT 'auto',
            target_lang varchar(10) NOT NULL,
            content_type varchar(50) DEFAULT 'manual',
            content_id bigint(20) DEFAULT NULL,
            api_key_id bigint(20) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY content_idx (content_type, content_id),
            KEY lang_idx (source_lang, target_lang)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'fkt_api_key' => '',
            'fkt_default_source_lang' => 'auto',
            'fkt_default_target_lang' => 'en',
            'fkt_cache_enabled' => '1',
            'fkt_cache_duration' => '86400', // 24 hours
            'fkt_auto_translate_posts' => '0',
            'fkt_show_shortcode_button' => '1',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_text_domain() {
        load_plugin_textdomain(
            'forcekeys-translation',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Main CSS
        wp_enqueue_style(
            'fkt-frontend',
            FKT_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            FKT_VERSION
        );

        // Main JS
        wp_enqueue_script(
            'fkt-frontend',
            FKT_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            FKT_VERSION,
            true
        );

        // Pass variables to JavaScript
        wp_localize_script('fkt-frontend', 'fktConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fkt_translate_nonce'),
            'i18n' => array(
                'translating' => __('Translating...', 'forcekeys-translation'),
                'translate' => __('Translate', 'forcekeys-translation'),
                'error' => __('Translation failed', 'forcekeys-translation'),
                'success' => __('Translation complete', 'forcekeys-translation'),
            ),
        ));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'forcekeys') === false) {
            return;
        }

        // Admin CSS
        wp_enqueue_style(
            'fkt-admin',
            FKT_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            FKT_VERSION
        );

        // Admin JS
        wp_enqueue_script(
            'fkt-admin',
            FKT_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            FKT_VERSION,
            true
        );

        wp_localize_script('fkt-admin', 'fktAdminConfig', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fkt_admin_nonce'),
        ));
    }
}

// Initialize the plugin
function fkt() {
    return Forcekeys_Translation_Plugin::get_instance();
}

// Start the plugin
fkt();
