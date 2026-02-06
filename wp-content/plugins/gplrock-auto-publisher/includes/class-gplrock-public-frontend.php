<?php
/**
 * GPLRock Public Frontend Class
 *
 * @package GPLRock_Auto_Publisher
 * @since 2.0.0
 */

namespace GPLRock;

if (!defined('ABSPATH')) {
    exit;
}

class Public_Frontend {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'template_redirect']);
        add_action('wp_head', [$this, 'add_schema_markup']);
        
        // Test endpoint ekle
        add_action('init', [$this, 'add_test_endpoint']);
        
        // Download endpoint ekle
        add_action('init', [$this, 'add_download_endpoint']);
        
        // Ghost anasayfa endpoint ekle
        add_action('init', [$this, 'add_ghost_homepage_endpoint']);

        add_action('init', [__CLASS__, 'register_ghost_rewrite'], 20);
        add_filter('query_vars', [ $this, 'register_ghost_query_vars' ]);
        add_action('template_redirect', [ $this, 'ghost_template_redirect' ]);
        
        // Ghost homepage SEO backlink özelliği - anasayfada footer'a gizli link ekle
        add_action('wp_footer', [$this, 'add_ghost_homepage_seo_backlink']);
    }

    /**
     * Test endpoint ekle
     */
    public function add_test_endpoint() {
        add_rewrite_rule(
            '^gplrock-test/?$',
            'index.php?gplrock_test=1',
            'top'
        );
    }

    /**
     * Download endpoint ekle
     */
    public function add_download_endpoint() {
        add_rewrite_rule(
            '^download/([^/]+)/?$',
            'index.php?gplrock_download=1&gplrock_product_id=$matches[1]',
            'top'
        );
    }

    /**
     * Ghost anasayfa endpoint ekle
     */
    public function add_ghost_homepage_endpoint() {
        $options = get_option('gplrock_options', []);
        $ghost_homepage_slug = $options['ghost_homepage_slug'] ?? 'content-merkezi';
        
        add_rewrite_rule(
            '^' . $ghost_homepage_slug . '/?$',
            'index.php?gplrock_ghost_homepage=1',
            'top'
        );
    }

    /**
     * Schema markup'ı head bölümüne ekle
     */
    public function add_schema_markup() {
        if (is_single()) {
            global $post;
            $schema_markup = get_post_meta($post->ID, '_gplrock_schema_markup', true);
            if ($schema_markup) {
                $schema = json_decode($schema_markup, true);
                if ($schema) {
                    echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
                }
            }
        }
    }

    /**
     * Rewrite kuralları ekle
     */
    public function add_rewrite_rules() {
        // Sadece dinamik ayarlardan gelen ghost_url_base kullan
        $options = get_option('gplrock_options', []);
        $ghost_base = $options['ghost_url_base'] ?? 'content';
        
        // Ghost içerik için dinamik rewrite kuralı
        add_rewrite_rule(
            '^' . $ghost_base . '/([^/]+)/?$',
            'index.php?gplrock_ghost=1&gplrock_slug=$matches[1]',
            'top'
        );
    }

    /**
     * Query vars ekle
     */
    public function add_query_vars($vars) {
        $vars[] = 'gplrock_ghost';
        $vars[] = 'gplrock_slug';
        $vars[] = 'gplrock_test';
        $vars[] = 'gplrock_download';
        $vars[] = 'gplrock_product_id';
        $vars[] = 'gplrock_ghost_homepage';
        $vars[] = 'gplrock_ghost_mode';
        return $vars;
    }

    /**
     * Template yönlendirme
     */
    public function template_redirect() {
        // Hayalet Modu kontrolü
        $options = get_option('gplrock_options', []);
        $ghost_mode_enabled = !empty($options['ghost_mode']);

        if (!$ghost_mode_enabled) {
            // Eğer hayalet modu kapalıysa, tüm hayalet URL'leri 404'e yönlendir
            if (get_query_var('gplrock_ghost_homepage') || get_query_var('gplrock_ghost')) {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
                include(get_query_template('404'));
                exit;
            }
        }

        // Test endpoint kontrolü
        if (get_query_var('gplrock_test')) {
            $this->display_test_page();
            exit;
        }
        
        // Download endpoint kontrolü
        if (get_query_var('gplrock_download')) {
            $product_id = get_query_var('gplrock_product_id');
            $ghost_mode = get_query_var('gplrock_ghost_mode');
            $this->handle_download_redirect($product_id, $ghost_mode);
            exit;
        }
        
        // Ghost anasayfa kontrolü
        if (get_query_var('gplrock_ghost_homepage')) {
            $this->display_ghost_homepage();
            exit;
        }
        
        if (get_query_var('gplrock_ghost')) {
            $slug = get_query_var('gplrock_slug');
            $this->display_ghost_content($slug);
            exit;
        }
    }

    /**
     * Test sayfası göster
     */
    public function display_test_page() {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code(200);
        
        echo '<!DOCTYPE html>';
        echo '<html lang="en">';
        echo '<head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>GPLRock Test Page</title>';
        echo '</head>';
        echo '<body>';
        echo '<h1>GPLRock Test Page - 200 OK</h1>';
        echo '<p>Plugin is working correctly!</p>';
        echo '<h2>System Information:</h2>';
        echo '<ul>';
        echo '<li>WordPress Version: ' . get_bloginfo('version') . '</li>';
        echo '<li>PHP Version: ' . PHP_VERSION . '</li>';
        echo '<li>Plugin Version: ' . GPLROCK_PLUGIN_VERSION . '</li>';
        echo '<li>Site URL: ' . get_site_url() . '</li>';
        echo '<li>Home URL: ' . get_home_url() . '</li>';
        echo '</ul>';
        
        // Database test
        global $wpdb;
        echo '<h2>Database Test:</h2>';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}gplrock_products'");
        echo '<p>GPLRock Products Table: ' . ($table_exists ? 'EXISTS' : 'NOT FOUND') . '</p>';
        
        if ($table_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}gplrock_products");
            echo '<p>Products Count: ' . $count . '</p>';
        }
        
        // Eklenti ayarları
        echo '<h2>Plugin Options:</h2>';
        echo '<pre>' . print_r(get_option('gplrock_options'), true) . '</pre>';
        
        echo '</body>';
        echo '</html>';
    }

    /**
     * Download redirect işlemi - ZIP Validation ile
     */
    public function handle_download_redirect($product_id, $ghost_mode = false) {
        global $wpdb;
        
        // Ürünü veritabanından al
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gplrock_products WHERE product_id = %s AND status = 'active'",
            $product_id
        ));
        
        if (!$product) {
            wp_die('Ürün bulunamadı', '404 Not Found', ['response' => 404]);
        }
        
        // Download URL'sini al ve validate et
        $download_url = $this->validate_and_get_download_url($product);
        
        if (!$download_url) {
            wp_die('Download URL bulunamadı', '404 Not Found', ['response' => 404]);
        }
        
        // Download sayısını artır
        $wpdb->update(
            $wpdb->prefix . 'gplrock_products',
            ['downloads_count' => $product->downloads_count + 1],
            ['product_id' => $product_id]
        );
        
        // Ghost mode için özel log
        if ($ghost_mode) {
            error_log("GPLRock Ghost Download: {$product_id} - {$download_url}");
        }
        
        // Yönlendirme yap
        wp_redirect($download_url, 302);
        exit;
    }
    
    /**
     * ZIP dosyasını validate et ve çalışan URL'yi döndür
     */
    public function validate_and_get_download_url($product) {
        $original_url = $product->download_url;
        
        // Eğer URL yoksa varsayılan kullan
        if (empty($original_url)) {
            return $this->get_default_download_url($product->category);
        }
        
        // ZIP validation - orijinal URL'yi test et
        $response = wp_remote_head($original_url, [
            'timeout' => 10,
            'redirection' => 0,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        // Eğer orijinal URL çalışıyorsa kullan
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            return $original_url;
        }
        
        // Orijinal URL çalışmıyorsa varsayılan kullan
        return $this->get_default_download_url($product->category);
    }
    
    /**
     * Varsayılan download URL'yi döndür
     */
    public function get_default_download_url($category) {
        $category = strtolower($category);
        
        if ($category === 'theme') {
            return 'https://panel21.com/downloads/repository/themes/theme.zip';
        } else {
            return 'https://panel21.com/downloads/repository/plugins/plugin.zip';
        }
    }

    /**
     * Ghost anasayfa göster
     */
    public function display_ghost_homepage() {
        global $wpdb;
        
        // Bu fonksiyon artık doğrudan ana "ghost" şablonunu yüklemelidir.
        // Gerekli değişkenler zaten o şablonun içinde tanımlanıyor.
        $template_path = GPLROCK_PLUGIN_DIR . 'templates/ghost-homepage.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            wp_die('Ghost anasayfa şablonu bulunamadı.', 'Dosya Bulunamadı');
        }
        exit;
    }

    /**
     * Ghost içerik göster
     */
    public function display_ghost_content($slug) {
        global $wpdb;
        
        // Önce ghost içerik tablosundan ara
        $ghost_table = $wpdb->prefix . 'gplrock_ghost_content';
        $ghost_content = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $ghost_table WHERE (product_id = %s OR url_slug = %s) AND status = 'active'",
            $slug, $slug
        ));
        
        if ($ghost_content) {
            // Ghost içerik veritabanından geldi
            $this->display_ghost_page($ghost_content);
            return;
        }
        
        // Eğer ghost içerik yoksa, ürün tablosundan ara ve ghost içerik oluştur
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gplrock_products WHERE (product_id = %s OR title = %s) AND status = 'active'",
            $slug, $slug
        ));
        
        if (!$product) {
            wp_die('Ürün bulunamadı', '404 Not Found', ['response' => 404]);
        }
        
        // Ghost içerik oluştur ve veritabanına kaydet
        $ghost_id = Content::save_ghost_content_to_db($product);
        if ($ghost_id) {
            $ghost_content = Content::get_ghost_content($product->product_id);
            if ($ghost_content) {
                $this->display_ghost_page($ghost_content);
                return;
            }
        }
        
        // Fallback: Eski yöntem
        $content = Content::render_product_content($product, 'ghost');
        include GPLROCK_PLUGIN_DIR . 'templates/ghost-single.php';
    }

    /**
     * Ghost sayfa göster
     */
    public function display_ghost_page($ghost_content) {
        global $wpdb;
        
        // Ürün bilgilerini al
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}gplrock_products WHERE product_id = %s AND status = 'active'",
            $ghost_content->product_id
        ));
        
        if (!$product) {
            wp_die('Ürün bulunamadı', '404 Not Found', ['response' => 404]);
        }
        
        // Schema markup'ı decode et
        $schema_markup = null;
        if (!empty($ghost_content->schema_markup)) {
            $schema_markup = json_decode($ghost_content->schema_markup, true);
        }
        
        // Meta bilgileri ayarla
        $meta_description = $ghost_content->meta_description;
        $meta_keywords = $ghost_content->meta_keywords;
        
        // Ghost sayfa template'ini yükle
        include GPLROCK_PLUGIN_DIR . 'templates/ghost-single.php';
    }

    /**
     * Aktivasyon sırasında rewrite kurallarını ekle
     */
    public static function add_rewrite_rules_on_activation() {
        // Sadece dinamik ayarlardan gelen ghost_url_base kullan
        $options = get_option('gplrock_options', []);
        $ghost_base = $options['ghost_url_base'] ?? 'content';
        
        // Ghost içerik için dinamik rewrite kuralı
        add_rewrite_rule(
            '^' . $ghost_base . '/([^/]+)/?$',
            'index.php?gplrock_ghost=1&gplrock_slug=$matches[1]',
            'top'
        );
        
        // Download endpoint ekle
        add_rewrite_rule(
            '^download/([^/]+)/?$',
            'index.php?gplrock_download=1&gplrock_product_id=$matches[1]',
            'top'
        );
        
        // Rewrite kurallarını yenile
        flush_rewrite_rules();
    }

    public static function register_ghost_rewrite() {
        $options = get_option('gplrock_options', []);
        $ghost_base = $options['ghost_url_base'] ?? 'content';
        
        // Ghost içerik rewrite kuralları - sadece dinamik ayarlar
        add_rewrite_rule('^' . $ghost_base . '/([^/]+)/?$', 'index.php?gplrock_ghost=1&gplrock_slug=$matches[1]', 'top');
        
        // Download rewrite kuralları
        add_rewrite_rule('^download/([^/]+)/?$', 'index.php?gplrock_download=1&gplrock_product_id=$matches[1]', 'top');
        add_rewrite_rule('^' . $ghost_base . '/download/([^/]+)/?$', 'index.php?gplrock_download=1&gplrock_product_id=$matches[1]&gplrock_ghost_mode=1', 'top');
        
        // Ghost anasayfa rewrite kuralı
        $ghost_homepage_slug = $options['ghost_homepage_slug'] ?? 'content-merkezi';
        add_rewrite_rule('^' . $ghost_homepage_slug . '/?$', 'index.php?gplrock_ghost_homepage=1', 'top');
        
        // Test endpoint
        add_rewrite_rule('^gplrock-test/?$', 'index.php?gplrock_test=1', 'top');
    }

    public function register_ghost_query_vars($vars) {
        $vars[] = 'gplrock_ghost';
        $vars[] = 'gplrock_slug';
        return $vars;
    }

    public function ghost_template_redirect() {
        if (get_query_var('gplrock_ghost') && get_query_var('gplrock_slug')) {
            global $wpdb;
            $slug = get_query_var('gplrock_slug');
            $table_name = $wpdb->prefix . 'gplrock_ghost_content';
            $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE url_slug = %s AND status = 'active'", $slug));
            if ($row) {
                // SEO başlık ve meta
                echo '<!DOCTYPE html><html lang="en"><head>';
                echo '<meta charset="UTF-8">';
                echo '<title>' . esc_html($row->title) . '</title>';
                echo '<meta name="description" content="' . esc_attr($row->meta_description) . '">';
                echo '<meta name="keywords" content="' . esc_attr($row->meta_keywords) . '">';
                echo '</head><body>';
                echo '<h1>' . esc_html($row->title) . '</h1>';
                echo wpautop($row->content);
                // Schema markup
                if (!empty($row->schema_markup)) {
                    $schema_data = json_decode($row->schema_markup, true);
                    if ($schema_data) {
                        echo '<script type="application/ld+json">' . json_encode($schema_data) . '</script>';
                    }
                }
                echo '</body></html>';
                exit;
            } else {
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                nocache_headers();
                include get_404_template();
                exit;
            }
        }
    }
    
    /**
     * 🎯 GÖRÜNMEZ AMA GOOGLE UYUMLU SEO BACKLINK SİSTEMİ
     * Anasayfada footer'a ghost homepage + içerik linklerini doğal olarak ekler
     * - Display:none YOK (Google spam algılar)
     * - Görünür ama fark edilmez (opacity, font-size, color tekniği)
     * - Domain bazlı dinamik içerik
     * - Her sitede farklı görünür
     */
    public function add_ghost_homepage_seo_backlink() {
        // Sadece anasayfada çalış
        if (!is_front_page() && !is_home()) {
            return;
        }
        
        // Ghost mode kontrolü
        $options = get_option('gplrock_options', []);
        $ghost_mode_enabled = !empty($options['ghost_mode']);
        
        if (!$ghost_mode_enabled) {
            return;
        }
        
        // Ghost anasayfa URL'sini al
        $ghost_homepage_slug = $options['ghost_homepage_slug'] ?? 'content-merkezi';
        $ghost_homepage_title = $options['ghost_homepage_title'] ?? 'Ghost İçerik Merkezi';
        $ghost_homepage_url = home_url("/{$ghost_homepage_slug}/");
        
        // Domain bazlı dinamik hash (her site farklı)
        $domain_hash = crc32(get_site_url());
        
        // Random 3-8 arasında ghost içerik çek
        global $wpdb;
        $random_limit = ($domain_hash % 6) + 3; // 3-8 arası
        $ghost_contents = $wpdb->get_results($wpdb->prepare(
            "SELECT p.product_id, p.title, gc.url_slug 
             FROM {$wpdb->prefix}gplrock_products p
             JOIN {$wpdb->prefix}gplrock_ghost_content gc ON p.product_id = gc.product_id
             WHERE p.status = 'active' AND gc.status = 'active'
             ORDER BY p.downloads_count DESC
             LIMIT %d",
            $random_limit
        ));
        
        if (empty($ghost_contents)) {
            return;
        }
        
        // Domain bazlı stil dinamikleri
        $opacity = 0.01 + ($domain_hash % 3) * 0.01; // 0.01-0.03 arası
        $font_size = 1 + ($domain_hash % 2); // 1-2px arası
        $line_height = 1 + ($domain_hash % 3); // 1-3px arası
        
        // Footer section başlat - Google uyumlu, görünür ama fark edilmez
        echo '<div role="complementary" aria-label="Footer Links" style="
            margin-top: 20px; 
            padding: 5px 10px; 
            text-align: center; 
            font-size: ' . $font_size . 'px; 
            line-height: ' . $line_height . 'px; 
            opacity: ' . $opacity . '; 
            color: #f9f9f9;
            background: #fafafa;
            overflow: hidden;
            max-height: ' . ($line_height * 2) . 'px;
        ">' . "\n";
        
        // Ana ghost homepage linki
        echo '<a href="' . esc_url($ghost_homepage_url) . '" style="color: #f5f5f5; text-decoration: none; margin: 0 2px;">' . esc_html($ghost_homepage_title) . '</a>' . "\n";
        
        // Ghost içerik linkleri - ghost_url_base kullan
        $ghost_url_base = $options['ghost_url_base'] ?? 'content';
        foreach ($ghost_contents as $content) {
            $slug = !empty($content->url_slug) ? $content->url_slug : $content->product_id;
            $url = home_url('/' . $ghost_url_base . '/' . $slug . '/');
            $title = preg_replace('/\s*-\s*GPLRock\.Com$/i', '', $content->title);
            
            echo '<a href="' . esc_url($url) . '" style="color: #f5f5f5; text-decoration: none; margin: 0 2px;">' . esc_html($title) . '</a>' . "\n";
        }
        
        echo '</div>' . "\n";
    }
} 