<?php
/**
 * Plugin Name: Savolix Debug Logger
 * Description: A simple debug logging system for WordPress.
 * Version: 1.0.0
 * Author: Lakshan Ranasinghe
 * Author URI: 
 * License: GPL-2.0+
 * License URI: 
 * Text Domain: slix-debug-logger
 */

if (
    !defined('ABSPATH') || 
    (defined('DOING_CRON') && DOING_CRON) || 
    (defined('DOING_AJAX') && DOING_AJAX) || 
    (defined('REST_REQUEST') && REST_REQUEST) || 
    (defined('WP_CLI') && WP_CLI)
) {
    return;
}

require_once plugin_dir_path( __FILE__ ) . 'savolix-debug-admin.php';


if (!class_exists('SavolixDebugLogger')) {

    class SavolixDebugLogger {

        /**
         * @var string
         */
        private $log_dir;

        /**
         * @var string
         */
        private $log_file = 'slix-debug.log';

        /**
         * @var string
         */
        private $log_file_path;

        /**
         * @var int
         */
        private $log_max_size = 5242880; // 5MB in bytes

        /**
         * @var array
         */
        private static $early_logs = array();

        /**
         * use trait
         */
        use SavolixDebugAdmin;

        /**
         * Constructor
         */
        public function __construct() {
            $this->log_max_size = get_option('slix_debug_logger_max_size', 5242880);
        }

        /**
         * Initialize variables
         */
        private function init() {
            $upload_dir = wp_upload_dir();
            $this->log_dir = trailingslashit($upload_dir['basedir']) . 'savolix-debug-logs/';
            $this->log_file_path = $this->log_dir . $this->log_file;
        }

        /**
         * Register hooks
         */
        public function hooks() {

            // Trigger after all plugin loaded
            add_action('plugins_loaded', array($this, 'create_log_directory'));
            //add_action('plugins_loaded', array($this, 'initialize_plugin') , 1);

            // Add admin menu
            add_action('admin_menu', array($this, 'add_admin_menu'));

            // Register settings
            add_action('admin_init', array($this, 'register_settings'));

            // Add settings link to plugins page
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));

            // Enqueue admin assets
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));

            // Handle log clearing
            add_action('admin_init', array($this, 'handle_clear_log'));

            // Trigger when plugin activation
            register_activation_hook( __FILE__, array($this, 'slix_plugin_install_mu_loader') );

            // Trigger when plugin deactivation
            register_deactivation_hook( __FILE__, array($this, 'slix_plugin_remove_mu_loader') );

            // Fiter logger max size entry
            add_filter('pre_update_option_slix_debug_logger_max_size', array($this, 'convert_kb_to_bytes'), 10, 2);

            // Confirmation
            add_action('admin_footer-plugins.php', array($this, 'slix_confirm_plugin_deactivation'));
        }

        /**
         * Create log directory if it doesn't exist
         */
        public function create_log_directory() {
            $this->init(); 
            if (!file_exists($this->log_dir)) {
                wp_mkdir_p($this->log_dir);
            }
        }

        /**
         * Add settings link to plugins page
         */
        public function add_settings_link($links) {
            $settings_link = '<a href="' . admin_url('tools.php?page=slix-debug-log') . '">' . __('Settings', 'slix-debug-logger') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * Enqueue admin assets
         */
        public function enqueue_admin_assets($hook) {
            if ($hook === 'tools_page_slix-debug-log') {
                wp_enqueue_style(
                    'slix-debug-logger-admin-css',
                    plugins_url('assets/css/slix-admin.css', __FILE__),
                    array(),
                    filemtime(plugin_dir_path(__FILE__) . 'assets/css/slix-admin.css')
                );

                wp_enqueue_script(
                    'slix-debug-logger-admin-js',
                    plugins_url('assets/js/slix-admin.js', __FILE__),
                    array('jquery'),
                    filemtime(plugin_dir_path(__FILE__) . 'assets/js/slix-admin.js')
                );
            }
        }

        /**
         * Register settings
         */
        public function register_settings() {
            register_setting('slix_debug_logger_settings', 'slix_debug_logger_enabled');
            register_setting('slix_debug_logger_settings', 'slix_debug_logger_level');
            register_setting(
                'slix_debug_logger_settings',
                'slix_debug_logger_max_size',  
                [
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'default'           => 5242880,  // Default: 5MB in bytes
                ]
            );
        }

        /**
         * Add admin menu
         */
        public function add_admin_menu() {
            add_submenu_page(
                'tools.php',
                __('Savolix Debug Log', 'slix-debug-logger'),
                __('Savolix Debug Log', 'slix-debug-logger'),
                'manage_options',
                'slix-debug-log',
                array($this, 'render_admin_page')
            );
        }

        /**
         * Initialize the plugin
         */
        public function initialize_plugin() {
            $instance = SavolixDebugLogger::get_instance();
        }

        /**
         * Get instance
         */
        public static function get_instance() {
            static $instance = null;

            if (is_null($instance)) {
                $instance = new self();
            }

            return $instance;
        }

        /**
         * Log a message
         *
         * @param string $message
         * @param string $level
         * @param string $source
         */
        public static function log($message, $level = 'info', $source = '') {
            $instance = self::get_instance();

            if (!get_option('slix_debug_logger_enabled', 1)) {
                return;
            }

            if (empty($instance->log_file_path)) {
                $instance->init();
            }

            $log_level = get_option('slix_debug_logger_level', 'all');
            $levels = array(
                'critical' => 6,
                'error' => 5,
                'warning' => 4,
                'notice' => 3,
                'info' => 2,
                'debug' => 1,
            );

            if ($log_level !== 'all' && (!isset($levels[$level]) || $levels[$level] < $levels[$log_level])) {
                return;
            }

            if (is_array($message) || is_object($message)) {
                $message = print_r($message, true);
            }

            $timestamp = current_time('mysql');
            $source = $source ?: 'general';
            $log_entry = sprintf("[%s] %s.%s: %s\n", $timestamp, strtoupper($level), $source, $message);

            if (file_exists($instance->log_file_path) && filesize($instance->log_file_path) > $instance->log_max_size) {
                $new_file = $instance->log_dir . 'slix-debug-' . time() . '.log';
                rename($instance->log_file_path, $new_file);
            }

            file_put_contents($instance->log_file_path, $log_entry, FILE_APPEND | LOCK_EX);

        }

        /**
         * Summary of slix_debug_log
         * @param mixed $message
         * @param mixed $level
         * @param mixed $source
         * @return bool
         */
        public static function slix_debug_log($message = '', $level = 'info', $source = '') {
            static $early_logs = [];
            
            if (class_exists('SavolixDebugLogger') && method_exists('SavolixDebugLogger', 'log')) {
                foreach ($early_logs as $log) {
                    SavolixDebugLogger::log($log[0], $log[1], $log[2]);
                }
                $early_logs = [];
                return SavolixDebugLogger::log($message, $level, $source);
            }
            
            $early_logs[] = [$message, $level, $source];
            
            return true;
        }

        /**
         * Plugin activation -> Create MU plugin
         * @return void
         */
        function slix_plugin_install_mu_loader() {

            $mu_plugins_dir = WP_CONTENT_DIR . '/mu-plugins';

            if ( ! file_exists( $mu_plugins_dir ) ) {
                wp_mkdir_p( $mu_plugins_dir );
            }
            
            $loader_file = $mu_plugins_dir . '/savolix-plugin-loader.php';
            
            $content = "<?php\n";
            $content .= "/**\n * Auto-generated MU loader for Savolix Debug Logger Plugin.\n * Do not edit manually.\n */\n";
            $content .= "if ( ! defined( 'WP_PLUGIN_DIR' ) ) {\n\tdefine( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );\n}\n";
            $content .= "require_once WP_PLUGIN_DIR . '/" . dirname( plugin_basename( __FILE__ ) ) . "/savolix-debug-logger.php';\n";
            
            if ( false === file_put_contents( $loader_file, $content ) ) {
                error_log( 'My Plugin: Could not create MU loader file: ' . $loader_file );
            }
        }

        /**
         * Plugin deactivation -> Delete MU plugin
         * @return void
         */
        function slix_plugin_remove_mu_loader() {
            $loader_file = WP_CONTENT_DIR . '/mu-plugins/savolix-plugin-loader.php';
            if ( file_exists( $loader_file ) ) {
                unlink( $loader_file );
            }
        }

        /**
         * Handle log clearing
         */
        public function handle_clear_log() {
            if (isset($_POST['action']) && $_POST['action'] === 'clear_log' && check_admin_referer('slix_debug_logger_clear_log')) {
                $this->init(); 
                if (file_exists($this->log_file_path)) {
                    file_put_contents($this->log_file_path, '');
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>' . __('Log file cleared successfully.', 'slix-debug-logger') . '</p></div>';
                    });
                }
            }
        }

         /**
         * Download log file
         */
        private function download_log_file() {
            $this->init();
            if (!file_exists($this->log_file_path)) {
                wp_die(__('Log file not found!', 'slix-debug-logger'));
            }
        
            if (ob_get_level()) {
                ob_end_clean();
            }
        
            header('Content-Description: File Transfer');
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="debug-log-' . date('Y-m-d-H-i-s') . '.log"');
            header('Content-Length: ' . filesize($this->log_file_path));
            header('Pragma: public');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
        
            readfile($this->log_file_path);
            exit; 
        }

        /**
         * Convert user input (KB) to bytes for storage.
         */
        public function convert_kb_to_bytes($new_value, $old_value) {
            $new_value_kb = isset($_POST['slix_debug_logger_max_size']) ? (int)$_POST['slix_debug_logger_max_size'] : 0;
            $new_value_bytes = $new_value_kb * 1024; 
            return max(1024, min($new_value_bytes, 5242880)); 
        }

        /**
         * Confirmation on plugin deactivation
         * @return void
         */
        function slix_confirm_plugin_deactivation() {
            ?>
            <script>
            jQuery(document).ready(function($) {
                const pluginSlug = 'savolix-debug-logger/savolix-debug-logger.php'; 
                const deactivateLink = $('tr[data-plugin="' + pluginSlug + '"] .deactivate a');
        
                deactivateLink.on('click', function(e) {
                    const confirmMsg = "Are you sure you want to deactivate this plugin?\n\nAll logs created by the plugin may remain on the server. It's recommended to remove them to avoid site issues.";
                    if (!confirm(confirmMsg)) {
                        e.preventDefault();
                    }
                });
            });
            </script>
            <?php
        }

    }

    $savolixDebugLogger = new SavolixDebugLogger();
    $savolixDebugLogger->hooks();
 
} 


