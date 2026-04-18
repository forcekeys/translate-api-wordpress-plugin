# Forcekeys Translation API WordPress Plugin

![WordPress Plugin Version](https://img.shields.io/badge/version-1.0.0-blue)
![WordPress](https://img.shields.io/badge/WordPress-%E2%9C%93-green)
![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777BB4)
![License](https://img.shields.io/badge/license-GPL%202.0-lightgrey)

Translate your WordPress content using the powerful Forcekeys Translation API. This plugin allows you to translate posts, pages, WooCommerce products, and more with support for 70+ languages.

## ✨ Features

### 🎯 Core Translation Features
- **Real-time text translation** with automatic language detection
- **Batch translation** for multiple content items at once
- **70+ languages** supported including major world languages
- **WooCommerce integration** for product and category translation
- **Translation caching** for improved performance
- **Automatic post translation** (optional)

### 🛠️ Admin Features
- **Easy API key configuration** in WordPress admin
- **Translation history** with detailed logs
- **Usage statistics** and quota monitoring
- **Customizable default languages**
- **Cache management** options

### 🔌 Integration Features
- **Shortcodes** for easy translation buttons
- **TinyMCE editor integration** for inline translation
- **AJAX-powered** frontend translation
- **REST API endpoints** for custom integrations
- **WooCommerce compatibility** for multilingual stores

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Forcekeys Translation API account (free tier available)
- cURL extension enabled

## 🚀 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin ZIP file from [forcekeys.com](https://forcekeys.com)
2. Go to **Plugins → Add New → Upload Plugin**
3. Upload the ZIP file and click **Install Now**
4. Activate the plugin

### Method 2: Manual Installation
1. Download the plugin ZIP file
2. Extract the contents to `/wp-content/plugins/forcekeys-translation/`
3. Go to **Plugins** in WordPress admin
4. Find "Forcekeys Translation API" and click **Activate**

### Method 3: Git Clone
```bash
cd /wp-content/plugins/
git clone https://github.com/forcekeys/wordpress-translation-plugin.git forcekeys-translation
```

## ⚙️ Configuration

### 1. Get Your API Key
1. Sign up at [forcekeys.com](https://forcekeys.com)
2. Go to your Dashboard
3. Generate an API key from the API Keys section

### 2. Configure the Plugin
1. Go to **Settings → Forcekeys Translation**
2. Enter your API key
3. Configure default languages:
   - **Source Language**: Auto-detect or specific language
   - **Target Language**: Default translation language
4. Adjust cache settings (recommended: enabled)
5. Save changes

### 3. Configure WooCommerce (Optional)
If WooCommerce is installed:
1. Go to **WooCommerce → Settings → Forcekeys Translation**
2. Enable product translation
3. Configure which fields to translate (title, description, etc.)
4. Set up automatic translation for new products

## 📖 Usage

### Shortcodes
Add translation functionality anywhere on your site:

```php
// Basic translation button
[forcekeys_translate_button text="Hello World" target="es"]

// Translation with custom styling
[forcekeys_translate_button 
    text="Click to translate" 
    target="fr" 
    class="btn btn-primary"
    show_original="true"]

// Inline translation
[forcekeys_translate_inline]Text to translate[/forcekeys_translate_inline]
```

### PHP Functions
Use in your theme or custom plugins:

```php
// Translate text
$translated = fkt()->api->translate_text('Hello World', 'es');

// Batch translate array of texts
$texts = ['Hello', 'Goodbye', 'Thank you'];
$translations = fkt()->api->batch_translate($texts, 'fr');

// Get translation history
$history = fkt()->api->get_translation_history();
```

### JavaScript API
Frontend translation with AJAX:

```javascript
// Translate text on the fly
fktTranslate('Hello World', 'es')
    .then(translation => {
        console.log('Translated:', translation);
    })
    .catch(error => {
        console.error('Translation failed:', error);
    });

// Batch translation
fktBatchTranslate(['Text 1', 'Text 2'], 'de')
    .then(translations => {
        console.log('Translations:', translations);
    });
```

### WooCommerce Integration
The plugin automatically adds:
- Translation buttons on product edit pages
- Bulk translation tools for product catalogs
- Category and tag translation support
- Multilingual product variations

## 🎨 Customization

### CSS Classes
```css
/* Translation button */
.fkt-translate-btn {
    /* Your custom styles */
}

/* Loading state */
.fkt-translate-btn.loading {
    /* Loading animation styles */
}

/* Success state */
.fkt-translate-btn.success {
    /* Success feedback styles */
}

/* Error state */
.fkt-translate-btn.error {
    /* Error feedback styles */
}
```

### Hooks and Filters
```php
// Filter translation before sending to API
add_filter('fkt_pre_translate_text', function($text, $target_lang) {
    // Modify text before translation
    return $text;
}, 10, 2);

// Filter translation after receiving from API
add_filter('fkt_post_translate_text', function($translated, $original, $target_lang) {
    // Modify translated text
    return $translated;
}, 10, 3);

// Add custom translation providers
add_filter('fkt_translation_providers', function($providers) {
    $providers['custom'] = 'Custom_Translation_Provider';
    return $providers;
});

// Modify API request
add_filter('fkt_api_request_args', function($args) {
    $args['timeout'] = 30; // Increase timeout
    return $args;
});
```

### Actions
```php
// Translation completed
add_action('fkt_translation_complete', function($original, $translated, $target_lang) {
    // Log translation, send notification, etc.
}, 10, 3);

// Translation failed
add_action('fkt_translation_failed', function($error, $text, $target_lang) {
    // Handle translation errors
}, 10, 3);

// Plugin initialized
add_action('fkt_init', function() {
    // Custom initialization code
});
```

## 🔧 Advanced Configuration

### Database Schema
The plugin creates the following table:
```sql
CREATE TABLE wp_fkt_translations (
    id BIGINT(20) AUTO_INCREMENT,
    original_text LONGTEXT NOT NULL,
    translated_text LONGTEXT NOT NULL,
    source_lang VARCHAR(10) DEFAULT 'auto',
    target_lang VARCHAR(10) NOT NULL,
    content_type VARCHAR(50) DEFAULT 'manual',
    content_id BIGINT(20) DEFAULT NULL,
    api_key_id BIGINT(20) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY content_idx (content_type, content_id),
    KEY lang_idx (source_lang, target_lang)
);
```

### wp-config.php Settings
```php
// Disable translation caching
define('FKT_CACHE_ENABLED', false);

// Set custom cache duration (seconds)
define('FKT_CACHE_DURATION', 3600);

// Custom API endpoint (for self-hosted instances)
define('FKT_API_BASE_URL', 'https://your-api-endpoint.com/api/v1');

// Enable debug mode
define('FKT_DEBUG', true);
```

## 📊 Usage Statistics

The plugin tracks:
- **Total translations**: Number of translations performed
- **Characters translated**: Total character count
- **Most used languages**: Top target languages
- **Cache hit rate**: Percentage of cached translations
- **API usage**: Quota consumption and limits

View statistics in **Settings → Forcekeys Translation → Statistics**

## 🔒 Security

### API Key Security
- API keys are encrypted in the database
- Keys are never exposed in frontend JavaScript
- Each key has usage limits and can be revoked

### Data Privacy
- No user data is sent to the API without consent
- Translations are cached locally to reduce API calls
- All API communications use HTTPS

### Permissions
- **Administrators**: Full access to all features
- **Editors**: Can translate content they can edit
- **Authors**: Can translate their own content
- **Subscribers**: No translation access by default

## 🐛 Troubleshooting

### Common Issues

**1. "API Key Invalid" Error**
- Verify your API key is correct
- Check if the key has expired
- Ensure your account has available quota

**2. Translation Not Working**
- Check PHP cURL extension is enabled
- Verify server can connect to `api.translate.forcekeys.com`
- Check WordPress debug log for errors

**3. Slow Translation Performance**
- Enable caching in plugin settings
- Reduce batch translation size
- Check server response times

**4. WooCommerce Integration Not Showing**
- Ensure WooCommerce is active
- Check user permissions
- Verify plugin is properly activated

### Debug Mode
Enable debug mode in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('FKT_DEBUG', true);
```

Check the debug log at `/wp-content/debug.log` for plugin errors.

## 📈 Performance Optimization

### Caching Strategies
1. **Enable translation caching** (recommended)
2. **Set appropriate cache duration** (24 hours default)
3. **Use CDN for static assets**
4. **Implement object caching** (Redis/Memcached)

### Database Optimization
1. **Regularly clean old translations** (optional)
2. **Add indexes for frequent queries**
3. **Archive translation history** (monthly)

### API Usage Optimization
1. **Batch translations** when possible
2. **Cache frequently translated content**
3. **Use webhooks for async translation**
4. **Monitor quota usage** to avoid overages

## 🤝 Contributing

We welcome contributions! Here's how to help:

### Development Setup
```bash
# Clone the repository
git clone https://github.com/forcekeys/wordpress-translation-plugin.git

# Install dependencies
cd wordpress-translation-plugin
composer install

# Set up development environment
cp .env.example .env
```

### Coding Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use PHPDoc for all functions and classes
- Write unit tests for new features
- Update documentation with changes

### Pull Request Process
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add/update tests
5. Update documentation
6. Submit pull request

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 📞 Support

### Documentation
- [Official Documentation](https://forcekeys.com/docs/wordpress)
- [API Reference](https://forcekeys.com/api-docs)
- [FAQ](https://forcekeys.com/faq)

### Community Support
- [GitHub Issues](https://github.com/forcekeys/wordpress-translation-plugin/issues)
- [WordPress Support Forum](https://wordpress.org/support/plugin/forcekeys-translation)
- [Community Discord](https://discord.gg/forcekeys)

### Professional Support
- **Email**: support@forcekeys.com
- **Priority Support**: Available for Enterprise plans
- **Custom Development**: Contact sales@forcekeys.com

### Bug Reports
Please report bugs on [GitHub Issues](https://github.com/forcekeys/wordpress-translation-plugin/issues) with:
1. WordPress version
2. PHP version
3. Plugin version
4. Error message
5. Steps to reproduce

## 🔗 Links

- **Website**: [forcekeys.com](https://forcekeys.com)
- **Documentation**: [docs.forcekeys.com](https://docs.forcekeys.com)
- **GitHub**: [github.com/forcekeys](https://github.com/forcekeys)
- **Twitter**: [@forcekeys](https://twitter.com/forcekeys)
- **LinkedIn**: [Forcekeys](https://linkedin.com/company/forcekeys)

## 🙏 Acknowledgments

- Thanks to all our contributors
- Built with ❤️ by the Forcekeys team
- Powered by advanced AI translation technology
- Special thanks to early adopters and beta testers

---

**Need help?** Contact us at support@forcekeys.com or join our [community Discord](https://discord.gg/forcekeys).

**Found a bug?** Please report it on [GitHub Issues](https://github.com/forcekeys/wordpress-translation-plugin/issues).

**Want to contribute?** Check out our [contributing guidelines](CONTRIBUTING.md).

**Looking for enterprise features?** Contact sales@forcekeys.com for white-label solutions, on-premise deployment, and custom integrations.