<?php
/**
 * Forcekeys Admin Settings Class
 * 
 * Handles admin settings page and configuration
 */

if (!defined('ABSPATH')) {
    exit;
}

class Forcekeys_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Add meta box for translations
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_box'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Forcekeys Translation', 'forcekeys-translation'),
            __('Translation', 'forcekeys-translation'),
            'manage_options',
            'forcekeys-translation',
            array($this, 'render_settings_page'),
            'dashicons-translator',
            100
        );

        add_submenu_page(
            'forcekeys-translation',
            __('Settings', 'forcekeys-translation'),
            __('Settings', 'forcekeys-translation'),
            'manage_options',
            'forcekeys-translation',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'forcekeys-translation',
            __('Translations Cache', 'forcekeys-translation'),
            __('Cache', 'forcekeys-translation'),
            'manage_options',
            'forcekeys-translation-cache',
            array($this, 'render_cache_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('fkt_settings_group', 'fkt_api_key');
        register_setting('fkt_settings_group', 'fkt_api_url');
        register_setting('fkt_settings_group', 'fkt_default_source_lang');
        register_setting('fkt_settings_group', 'fkt_default_target_lang');
        register_setting('fkt_settings_group', 'fkt_cache_enabled');
        register_setting('fkt_settings_group', 'fkt_cache_duration');
        register_setting('fkt_settings_group', 'fkt_auto_translate_posts');
        register_setting('fkt_settings_group', 'fkt_show_shortcode_button');
        register_setting('fkt_settings_group', 'fkt_woocommerce_enabled');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Get languages
        $api = new Forcekeys_API();
        $languages = $api->get_languages();
        
        if (is_wp_error($languages)) {
            $languages = array(
                array('code' => 'en', 'name' => 'English'),
                array('code' => 'fr', 'name' => 'French'),
            );
        }
        
        ?>
        <div class="wrap fkt-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php settings_fields('fkt_settings_group'); ?>
                
                <!-- API Settings -->
                <div class="fkt-card">
                    <h2><?php _e('API Configuration', 'forcekeys-translation'); ?></h2>
                    <p class="description">
                        <?php _e('Get your API key from', 'forcekeys-translation'); ?> 
                        <a href="https://forcekeys.com/dashboard/api-keys" target="_blank">forcekeys.com</a>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fkt_api_key"><?php _e('API Key', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <input type="password" 
                                       id="fkt_api_key" 
                                       name="fkt_api_key" 
                                       value="<?php echo esc_attr(get_option('fkt_api_key')); ?>" 
                                       class="regular-text"
                                       placeholder="fk_live_xxxxx">
                                <p class="description"><?php _e('Your Forcekeys API key', 'forcekeys-translation'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fkt_api_url"><?php _e('API URL (Optional)', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="fkt_api_url" 
                                       name="fkt_api_url" 
                                       value="<?php echo esc_attr(get_option('fkt_api_url', FKT_API_BASE_URL)); ?>" 
                                       class="regular-text">
                                <p class="description"><?php _e('Leave empty for default API endpoint', 'forcekeys-translation'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Language Settings -->
                <div class="fkt-card">
                    <h2><?php _e('Language Settings', 'forcekeys-translation'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fkt_default_source_lang"><?php _e('Default Source Language', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <select id="fkt_default_source_lang" name="fkt_default_source_lang">
                                    <option value="auto" <?php selected(get_option('fkt_default_source_lang'), 'auto'); ?>>
                                        <?php _e('Auto Detect', 'forcekeys-translation'); ?>
                                    </option>
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo esc_attr($lang['code']); ?>" 
                                                <?php selected(get_option('fkt_default_source_lang'), $lang['code']); ?>>
                                            <?php echo esc_html($lang['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fkt_default_target_lang"><?php _e('Default Target Language', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <select id="fkt_default_target_lang" name="fkt_default_target_lang">
                                    <?php foreach ($languages as $lang): ?>
                                        <option value="<?php echo esc_attr($lang['code']); ?>" 
                                                <?php selected(get_option('fkt_default_target_lang'), $lang['code']); ?>>
                                            <?php echo esc_html($lang['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Cache Settings -->
                <div class="fkt-card">
                    <h2><?php _e('Cache Settings', 'forcekeys-translation'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fkt_cache_enabled"><?php _e('Enable Translation Cache', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" 
                                       id="fkt_cache_enabled" 
                                       name="fkt_cache_enabled" 
                                       value="1" 
                                       <?php checked(get_option('fkt_cache_enabled'), '1'); ?>>
                                <span class="description"><?php _e('Cache translations to reduce API calls and improve performance', 'forcekeys-translation'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fkt_cache_duration"><?php _e('Cache Duration (seconds)', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <input type="number" 
                                       id="fkt_cache_duration" 
                                       name="fkt_cache_duration" 
                                       value="<?php echo esc_attr(get_option('fkt_cache_duration', 86400)); ?>" 
                                       class="small-text"
                                       min="3600">
                                <p class="description"><?php _e('Default: 86400 seconds (24 hours)', 'forcekeys-translation'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Integration Settings -->
                <div class="fkt-card">
                    <h2><?php _e('Integrations', 'forcekeys-translation'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="fkt_woocommerce_enabled"><?php _e('WooCommerce Integration', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" 
                                       id="fkt_woocommerce_enabled" 
                                       name="fkt_woocommerce_enabled" 
                                       value="1" 
                                       <?php checked(get_option('fkt_woocommerce_enabled'), '1'); ?>>
                                <span class="description"><?php _e('Enable WooCommerce product translation', 'forcekeys-translation'); ?></span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="fkt_show_shortcode_button"><?php _e('Show Shortcode Button', 'forcekeys-translation'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" 
                                       id="fkt_show_shortcode_button" 
                                       name="fkt_show_shortcode_button" 
                                       value="1" 
                                       <?php checked(get_option('fkt_show_shortcode_button'), '1'); ?>>
                                <span class="description"><?php _e('Show translation shortcode button in post editor', 'forcekeys-translation'); ?></span>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save Settings', 'forcekeys-translation')); ?>
            </form>
        </div>
        
        <style>
        .fkt-admin-wrap .fkt-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .fkt-admin-wrap .fkt-card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 1.2em;
        }
        </style>
        <?php
    }

    /**
     * Render cache page
     */
    public function render_cache_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fkt_translations';
        
        // Handle cache clear action
        if (isset($_POST['clear_cache']) && check_admin_referer('fkt_clear_cache')) {
            $wpdb->query("TRUNCATE TABLE $table_name");
            echo '<div class="notice notice-success"><p>' . __('Cache cleared successfully', 'forcekeys-translation') . '</p></div>';
        }
        
        // Get cache stats
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $today = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Translation Cache', 'forcekeys-translation'); ?></h1>
            
            <div class="card" style="max-width: 400px; margin-top: 20px;">
                <h2><?php _e('Cache Statistics', 'forcekeys-translation'); ?></h2>
                <p><strong><?php _e('Total Translations:', 'forcekeys-translation'); ?></strong> <?php echo intval($total); ?></p>
                <p><strong><?php _e('Translations Today:', 'forcekeys-translation'); ?></strong> <?php echo intval($today); ?></p>
            </div>
            
            <form method="post" style="margin-top: 20px;">
                <?php wp_nonce_field('fkt_clear_cache'); ?>
                <button type="submit" name="clear_cache" class="button button-secondary">
                    <?php _e('Clear Cache', 'forcekeys-translation'); ?>
                </button>
            </form>
        </div>
        <?php
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        if (!get_option('fkt_api_key')) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php _e('Forcekeys Translation:', 'forcekeys-translation'); ?></strong>
                    <?php _e('Please configure your API key in', 'forcekeys-translation'); ?>
                    <a href="<?php echo admin_url('admin.php?page=forcekeys-translation'); ?>">
                        <?php _e('Translation Settings', 'forcekeys-translation'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'fkt_translation_meta',
            __('Forcekeys Translation', 'forcekeys-translation'),
            array($this, 'render_meta_box'),
            array('post', 'page', 'product'),
            'side',
            'high'
        );
    }

    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        $source_lang = get_post_meta($post->ID, '_fkt_source_lang', true);
        $target_lang = get_post_meta($post->ID, '_fkt_target_lang', true);
        
        $api = new Forcekeys_API();
        $languages = $api->get_languages();
        
        if (is_wp_error($languages)) {
            $languages = array(array('code' => 'en', 'name' => 'English'));
        }
        
        ?>
        <p>
            <label><strong><?php _e('Source Language:', 'forcekeys-translation'); ?></strong></label>
            <select name="fkt_source_lang" style="width: 100%;">
                <option value="auto" <?php selected($source_lang, 'auto'); ?>><?php _e('Auto Detect', 'forcekeys-translation'); ?></option>
                <?php foreach ($languages as $lang): ?>
                    <option value="<?php echo esc_attr($lang['code']); ?>" <?php selected($source_lang, $lang['code']); ?>>
                        <?php echo esc_html($lang['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label><strong><?php _e('Target Language:', 'forcekeys-translation'); ?></strong></label>
            <select name="fkt_target_lang" style="width: 100%;">
                <?php foreach ($languages as $lang): ?>
                    <option value="<?php echo esc_attr($lang['code']); ?>" <?php selected($target_lang, $lang['code']); ?>>
                        <?php echo esc_html($lang['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <button type="button" class="button button-primary" id="fkt-translate-post" style="width: 100%; margin-top: 10px;">
            <?php _e('Translate This Content', 'forcekeys-translation'); ?>
        </button>
        <div id="fkt-translate-status" style="margin-top: 10px;"></div>
        <?php
    }

    /**
     * Save meta box
     */
    public function save_meta_box($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['fkt_source_lang'])) {
            update_post_meta($post_id, '_fkt_source_lang', sanitize_text_field($_POST['fkt_source_lang']));
        }
        
        if (isset($_POST['fkt_target_lang'])) {
            update_post_meta($post_id, '_fkt_target_lang', sanitize_text_field($_POST['fkt_target_lang']));
        }
    }
}
