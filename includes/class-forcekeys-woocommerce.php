<?php
/**
 * Forcekeys WooCommerce Integration Class
 * 
 * Handles WooCommerce product translation
 */

if (!defined('ABSPATH')) {
    exit;
}

class Forcekeys_WooCommerce {

    /**
     * Constructor
     */
    public function __construct() {
        // Only run if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Add translation tab to product data
        add_filter('woocommerce_product_data_tabs', array($this, 'add_translation_tab'));
        add_action('woocommerce_product_data_panels', array($this, 'render_translation_panel'));
        
        // Add bulk action
        add_action('admin_footer', array($this, 'add_bulk_actions'));
        add_action('woocommerce_product_bulk_edit_start', array($this, 'bulk_edit_fields'));
        add_action('woocommerce_product_bulk_edit_save', array($this, 'bulk_edit_save'));
        
        // AJAX handlers
        add_action('wp_ajax_fkt_translate_product', array($this, 'ajax_translate_product'));
        
        // Sync hooks
        add_action('woocommerce_product_import_inserted_product_object', array($this, 'auto_translate_import'), 10, 2);
    }

    /**
     * Add translation tab to product data
     */
    public function add_translation_tabs($tabs) {
        $tabs['translation'] = array(
            'label' => __('Translation', 'forcekeys-translation'),
            'target' => 'fkt_translation_data',
            'class' => array('hide_if_grouped'),
            'priority' => 70,
        );
        return $tabs;
    }

    /**
     * Add translation tab (using correct filter name)
     */
    public function add_translation_tab($tabs) {
        $tabs['fkt_translation'] = array(
            'label' => __('Translation', 'forcekeys-translation'),
            'target' => 'fkt_translation_panel',
            'class' => array(),
            'priority' => 70,
        );
        return $tabs;
    }

    /**
     * Render translation panel
     */
    public function render_translation_panel() {
        global $post;
        
        $product = wc_get_product($post->ID);
        
        $api = new Forcekeys_API();
        $languages = $api->get_languages();
        
        if (is_wp_error($languages)) {
            $languages = array(array('code' => 'en', 'name' => 'English'));
        }
        
        // Get stored translations
        $translations = get_post_meta($post->ID, '_fkt_translations', true);
        if (!is_array($translations)) {
            $translations = array();
        }
        
        ?>
        <div id="fkt_translation_panel" class="panel woocommerce_options_panel">
            <div class="options_group">
                <h3><?php _e('Forcekeys Translation', 'forcekeys-translation'); ?></h3>
                <p class="description">
                    <?php _e('Translate your product content to multiple languages.', 'forcekeys-translation'); ?>
                </p>
                
                <!-- Language Selection -->
                <p>
                    <label><strong><?php _e('Target Language:', 'forcekeys-translation'); ?></strong></label>
                    <select id="fkt_translate_lang" class="select" style="width: 50%;">
                        <?php foreach ($languages as $lang): ?>
                            <option value="<?php echo esc_attr($lang['code']); ?>">
                                <?php echo esc_html($lang['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
                
                <!-- Translate Button -->
                <p>
                    <button type="button" class="button button-primary" id="fkt-translate-product">
                        <?php _e('Translate Product', 'forcekeys-translation'); ?>
                    </button>
                    <span class="spinner" id="fkt-translate-spinner" style="float: none; visibility: hidden;"></span>
                </p>
                
                <!-- Status -->
                <div id="fkt-translate-status" style="display: none;"></div>
                
                <!-- Existing Translations -->
                <?php if (!empty($translations)): ?>
                    <h4><?php _e('Saved Translations', 'forcekeys-translation'); ?></h4>
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Language', 'forcekeys-translation'); ?></th>
                                <th><?php _e('Title', 'forcekeys-translation'); ?></th>
                                <th><?php _e('Actions', 'forcekeys-translation'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($translations as $lang => $data): ?>
                                <tr>
                                    <td><?php echo esc_html(strtoupper($lang)); ?></td>
                                    <td><?php echo esc_html($data['title'] ?? '-'); ?></td>
                                    <td>
                                        <button type="button" class="button button-small" onclick="fkt_apply_translation('<?php echo esc_attr($lang); ?>')">
                                            <?php _e('Apply', 'forcekeys-translation'); ?>
                                        </button>
                                        <button type="button" class="button button-small" onclick="fkt_delete_translation('<?php echo esc_attr($lang); ?>')">
                                            <?php _e('Delete', 'forcekeys-translation'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <script>
            jQuery(document).ready(function($) {
                $('#fkt-translate-product').on('click', function() {
                    var btn = $(this);
                    var spinner = $('#fkt-translate-spinner');
                    var status = $('#fkt-translate-status');
                    var target_lang = $('#fkt_translate_lang').val();
                    var product_id = <?php echo $post->ID; ?>;
                    
                    btn.prop('disabled', true);
                    spinner.css('visibility', 'visible');
                    status.hide();
                    
                    $.ajax({
                        url: fktAdminConfig.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'fkt_translate_product',
                            nonce: fktAdminConfig.nonce,
                            product_id: product_id,
                            target_lang: target_lang
                        },
                        success: function(response) {
                            if (response.success) {
                                status.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>').show();
                                location.reload();
                            } else {
                                status.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>').show();
                            }
                        },
                        error: function() {
                            status.html('<div class="notice notice-error"><p>Translation failed. Please try again.</p></div>').show();
                        },
                        complete: function() {
                            btn.prop('disabled', false);
                            spinner.css('visibility', 'hidden');
                        }
                    });
                });
                
                window.fkt_apply_translation = function(lang) {
                    var translations = <?php echo json_encode($translations); ?>;
                    if (translations[lang]) {
                        $('#_regular_price').val(translations[lang].regular_price || '');
                        $('#_sale_price').val(translations[lang].sale_price || '');
                        $('#post_title').val(translations[lang].title || '');
                        $('#content').val(translations[lang].description || '');
                    }
                };
                
                window.fkt_delete_translation = function(lang) {
                    if (confirm('Are you sure you want to delete this translation?')) {
                        var translations = <?php echo json_encode($translations); ?>;
                        delete translations[lang];
                        $.post(ajaxurl, {
                            action: 'fkt_save_translations',
                            nonce: fktAdminConfig.nonce,
                            product_id: <?php echo $post->ID; ?>,
                            translations: translations
                        }, function() {
                            location.reload();
                        });
                    }
                };
            });
            </script>
        </div>
        <?php
    }

    /**
     * Add bulk actions
     */
    public function add_bulk_actions() {
        global $post_type;
        
        if ('product' !== $post_type) {
            return;
        }
        
        ?>
        <script>
        jQuery(document).ready(function() {
            jQuery('<option>').val('fkt_translate')
                .text('<?php _e('Forcekeys: Translate', 'forcekeys-translation'); ?>')
                .appendTo('select[name="action"], select[name="action2"]');
        });
        </script>
        <?php
    }

    /**
     * AJAX: Translate product
     */
    public function ajax_translate_product() {
        check_ajax_referer('fkt_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $target_lang = isset($_POST['target_lang']) ? sanitize_text_field($_POST['target_lang']) : 'en';

        if (!$product_id) {
            wp_send_json_error(array('message' => 'Invalid product ID'));
        }

        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error(array('message' => 'Product not found'));
        }

        $api = new Forcekeys_API();
        
        // Get product data
        $data_to_translate = array(
            'title' => $product->get_name(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
        );

        $translations = array();
        
        foreach ($data_to_translate as $key => $value) {
            if (!empty($value)) {
                $result = $api->translate($value, 'auto', $target_lang);
                
                if (!is_wp_error($result)) {
                    $translations[$key] = $result;
                }
            }
        }

        // Save translations
        $existing = get_post_meta($product_id, '_fkt_translations', true);
        if (!is_array($existing)) {
            $existing = array();
        }
        
        $existing[$target_lang] = $translations;
        update_post_meta($product_id, '_fkt_translations', $existing);

        wp_send_json_success(array(
            'message' => sprintf(__('Product translated to %s successfully!', 'forcekeys-translation'), strtoupper($target_lang)),
            'translations' => $translations,
        ));
    }
}
