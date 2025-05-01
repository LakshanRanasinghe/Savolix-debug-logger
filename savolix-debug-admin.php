<?php

trait SavolixDebugAdmin {
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (isset($_GET['action']) && $_GET['action'] === 'download_log' && check_admin_referer('download_log')) {
            $this->download_log_file();
            exit;
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Savolix Debug Log', 'slix-debug-logger'); ?></h1>

            <form method="post" action="options.php">
                <?php settings_fields('slix_debug_logger_settings'); ?>
                <?php do_settings_sections('slix_debug_logger_settings'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Enable Debug Logging', 'slix-debug-logger'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="slix_debug_logger_enabled" value="1" <?php checked(get_option('slix_debug_logger_enabled', 1)); ?> />
                                <?php _e('Enable logging', 'slix-debug-logger'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Max Log File Size', 'slix-debug-logger'); ?></th>
                        <td>
                            <input 
                                type="number" 
                                name="slix_debug_logger_max_size" 
                                value="<?php echo esc_attr(round(get_option('slix_debug_logger_max_size', 5242880) / 1024)); ?>" 
                                min="1" 
                                max="5242880" 
                                placeholder="5120 (5MB)" 
                            />
                            <span>KB</span>
                            <p class="description">
                                <?php _e('Maximum log file size in kilobytes (KB). Default: 5120 KB (5MB).', 'slix-debug-logger'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><?php _e('Log Level', 'slix-debug-logger'); ?></th>
                        <td>
                            <select name="slix_debug_logger_level">
                                <option value="all" <?php selected(get_option('slix_debug_logger_level', 'all'), 'all'); ?>><?php _e('All', 'slix-debug-logger'); ?></option>
                                <option value="debug" <?php selected(get_option('slix_debug_logger_level', 'all'), 'debug'); ?>><?php _e('Debug', 'slix-debug-logger'); ?></option>
                                <option value="info" <?php selected(get_option('slix_debug_logger_level', 'all'), 'info'); ?>><?php _e('Info', 'slix-debug-logger'); ?></option>
                                <option value="notice" <?php selected(get_option('slix_debug_logger_level', 'all'), 'notice'); ?>><?php _e('Notice', 'slix-debug-logger'); ?></option>
                                <option value="warning" <?php selected(get_option('slix_debug_logger_level', 'all'), 'warning'); ?>><?php _e('Warning', 'slix-debug-logger'); ?></option>
                                <option value="error" <?php selected(get_option('slix_debug_logger_level', 'all'), 'error'); ?>><?php _e('Error', 'slix-debug-logger'); ?></option>
                                <option value="critical" <?php selected(get_option('slix_debug_logger_level', 'all'), 'critical'); ?>><?php _e('Critical', 'slix-debug-logger'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h2><?php _e('Log Viewer', 'slix-debug-logger'); ?></h2>

            <div class="log-actions">
                <form method="post" style="display: inline-block;" class="slix-clear-log-form">
                    <input type="hidden" name="action" value="clear_log">
                    <?php wp_nonce_field('slix_debug_logger_clear_log'); ?>
                    <button type="submit" class="button button-secondary"><?php _e('Clear Log', 'slix-debug-logger'); ?></button>
                </form>

                <a href="<?php echo wp_nonce_url(admin_url('tools.php?page=slix-debug-log&action=download_log'), 'download_log'); ?>" class="button button-secondary" style="margin-left: 5px;"><?php _e('Download Log', 'slix-debug-logger'); ?></a>
            </div>

            <div class="slix-debug-log-viewer">
                <pre><?php echo esc_html($this->get_log_content() ?: 'No log entries yet.'); ?></pre>
            </div>

            <p>
                Use the following line to log messages from anywhere in your plugin or theme. You can specify the message, log level (e.g. <code>debug</code>, <code>info</code>, <code>error</code>), and the source context. This helps track events, errors, or debugging information:
            </p>

            <div style="display: flex; flex-wrap: wrap;">
                <code id="savolix-log-snippet">SavolixDebugLogger::slix_debug_log('User login failed', 'error', 'auth');</code>
                <button id="copy-savolix-log-snippet" >
                <svg fill="#000" height="16px" width="16px" version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" xmlns:xlink="http://www.w3.org/1999/xlink" enable-background="new 0 0 512 512" stroke="#ffffff"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <g> <g> <path d="M480.6,109.1h-87.5V31.4c0-11.3-9.1-20.4-20.4-20.4H31.4C20.1,11,11,20.1,11,31.4v351c0,11.3,9.1,20.4,20.4,20.4h87.5 v77.7c0,11.3,9.1,20.4,20.4,20.4h341.3c11.3,0,20.4-9.1,20.4-20.4v-351C501,118.3,491.9,109.1,480.6,109.1z M51.8,362V51.8h300.4 v57.3H139.3c-11.3,0-20.4,9.1-20.4,20.4V362H51.8z M460.2,460.2H159.7V150h300.4V460.2z"></path> <path d="m233.3,254.4h155.8c11.3,0 20.4-9.1 20.4-20.4 0-11.3-9.1-20.4-20.4-20.4h-155.8c-11.3,0-20.4,9.1-20.4,20.4 0,11.2 9.1,20.4 20.4,20.4z"></path> <path d="m233.3,396.6h155.8c11.3,0 20.4-9.1 20.4-20.4 0-11.3-9.1-20.4-20.4-20.4h-155.8c-11.3,0-20.4,9.1-20.4,20.4 0,11.3 9.1,20.4 20.4,20.4z"></path> </g> </g> </g></svg>
                </button>
            </div>
        </div>
        <?php
    }

     /**
     * Get log content
     */
    private function get_log_content() {
        $this->init(); 
        if (!file_exists($this->log_file_path)) {
            return __('Log file is empty or does not exist yet.', 'slix-debug-logger');
        }

        $content = file_get_contents($this->log_file_path);
        if (filesize($this->log_file_path) > $this->log_max_size) {
            return __('Log file is too large to display. Please download it.', 'slix-debug-logger');
        }

        return $content;
    }

}