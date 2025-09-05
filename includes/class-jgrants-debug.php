<?php
/**
 * デバッグとロギング機能を提供するクラス
 */

if (!defined('ABSPATH')) {
    exit;
}

class JGrants_Debug {
    
    /**
     * ログファイルのパス
     */
    private static $log_file;
    
    /**
     * デバッグモードかどうか
     */
    private static $debug_mode;
    
    /**
     * 初期化
     */
    public static function init() {
        self::$log_file = JGRANTS_PLUGIN_PATH . 'logs/jgrants-debug.log';
        self::$debug_mode = get_option('jgrants_debug_mode', false) || JGRANTS_DEBUG;
        
        // ログディレクトリの作成
        $log_dir = dirname(self::$log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
            file_put_contents($log_dir . '/.htaccess', 'Deny from all');
        }
    }
    
    /**
     * ログを記録
     */
    public static function log($message, $level = 'INFO') {
        if (!self::$debug_mode) {
            return;
        }
        
        if (empty(self::$log_file)) {
            self::init();
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]) ? $backtrace[1]['function'] : 'unknown';
        
        if (is_array($message) || is_object($message)) {
            $message = print_r($message, true);
        }
        
        $log_entry = sprintf(
            "[%s] [%s] [%s] %s\n",
            $timestamp,
            $level,
            $caller,
            $message
        );
        
        // ファイルサイズチェック（10MB以上なら古いログを削除）
        if (file_exists(self::$log_file) && filesize(self::$log_file) > 10485760) {
            self::rotate_log();
        }
        
        error_log($log_entry, 3, self::$log_file);
        
        // WP_DEBUGが有効な場合はエラーログにも出力
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log('[JGrants] ' . $log_entry);
        }
    }
    
    /**
     * エラーログを記録
     */
    public static function error($message) {
        self::log($message, 'ERROR');
        
        // 管理者にメール通知（オプション）
        if (get_option('jgrants_email_on_error', false)) {
            self::send_error_notification($message);
        }
    }
    
    /**
     * 警告ログを記録
     */
    public static function warning($message) {
        self::log($message, 'WARNING');
    }
    
    /**
     * 情報ログを記録
     */
    public static function info($message) {
        self::log($message, 'INFO');
    }
    
    /**
     * デバッグログを記録
     */
    public static function debug($message) {
        self::log($message, 'DEBUG');
    }
    
    /**
     * APIリクエスト/レスポンスのログ
     */
    public static function log_api($endpoint, $request = null, $response = null, $status_code = null) {
        if (!self::$debug_mode) {
            return;
        }
        
        $log_data = array(
            'endpoint' => $endpoint,
            'timestamp' => current_time('Y-m-d H:i:s')
        );
        
        if ($request !== null) {
            $log_data['request'] = $request;
        }
        
        if ($response !== null) {
            $log_data['response'] = $response;
        }
        
        if ($status_code !== null) {
            $log_data['status_code'] = $status_code;
        }
        
        self::log($log_data, 'API');
    }
    
    /**
     * パフォーマンス測定開始
     */
    public static function start_timer($label = 'default') {
        if (!self::$debug_mode) {
            return;
        }
        
        $GLOBALS['jgrants_timers'][$label] = microtime(true);
    }
    
    /**
     * パフォーマンス測定終了
     */
    public static function end_timer($label = 'default', $log = true) {
        if (!self::$debug_mode) {
            return null;
        }
        
        if (!isset($GLOBALS['jgrants_timers'][$label])) {
            return null;
        }
        
        $duration = microtime(true) - $GLOBALS['jgrants_timers'][$label];
        
        if ($log) {
            self::log(
                sprintf('%s: %.4f seconds', $label, $duration),
                'PERFORMANCE'
            );
        }
        
        unset($GLOBALS['jgrants_timers'][$label]);
        
        return $duration;
    }
    
    /**
     * メモリ使用量を記録
     */
    public static function log_memory($label = '') {
        if (!self::$debug_mode) {
            return;
        }
        
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        $message = sprintf(
            'Memory Usage%s: %s / Peak: %s',
            $label ? ' (' . $label . ')' : '',
            size_format($memory),
            size_format($peak)
        );
        
        self::log($message, 'MEMORY');
    }
    
    /**
     * ログのローテーション
     */
    private static function rotate_log() {
        $backup_file = self::$log_file . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename(self::$log_file, $backup_file);
        
        // 古いバックアップファイルの削除（30日以上前）
        $log_dir = dirname(self::$log_file);
        $old_files = glob($log_dir . '/*.bak');
        
        foreach ($old_files as $file) {
            if (filemtime($file) < strtotime('-30 days')) {
                unlink($file);
            }
        }
    }
    
    /**
     * エラー通知メールを送信
     */
    private static function send_error_notification($message) {
        $admin_email = get_option('admin_email');
        $subject = '[JGrants Integration] エラーが発生しました';
        $body = "以下のエラーが発生しました：\n\n" . $message . "\n\n" .
                "サイト: " . get_site_url() . "\n" .
                "時刻: " . current_time('Y-m-d H:i:s');
        
        wp_mail($admin_email, $subject, $body);
    }
    
    /**
     * デバッグ情報の取得
     */
    public static function get_debug_info() {
        global $wp_version, $wpdb;
        
        $info = array(
            'WordPress Version' => $wp_version,
            'PHP Version' => PHP_VERSION,
            'MySQL Version' => $wpdb->db_version(),
            'Plugin Version' => JGRANTS_PLUGIN_VERSION,
            'Active Theme' => wp_get_theme()->get('Name'),
            'Active Plugins' => array(),
            'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'Memory Limit' => ini_get('memory_limit'),
            'Max Execution Time' => ini_get('max_execution_time'),
            'Post Max Size' => ini_get('post_max_size'),
            'Upload Max Filesize' => ini_get('upload_max_filesize'),
            'Debug Mode' => JGRANTS_DEBUG ? 'Enabled' : 'Disabled',
            'Site URL' => get_site_url(),
            'Home URL' => get_home_url(),
        );
        
        // アクティブなプラグインのリスト
        $active_plugins = get_option('active_plugins', array());
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin, false, false);
            $info['Active Plugins'][] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
        }
        
        return $info;
    }
    
    /**
     * システムステータスチェック
     */
    public static function check_system_status() {
        $status = array();
        
        // PHPバージョンチェック
        $status['php_version'] = array(
            'label' => 'PHP Version',
            'value' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, '7.2', '>=') ? 'ok' : 'error',
            'message' => version_compare(PHP_VERSION, '7.2', '>=') 
                ? 'PHP version is compatible' 
                : 'PHP 7.2 or higher is required'
        );
        
        // WordPressバージョンチェック
        global $wp_version;
        $status['wp_version'] = array(
            'label' => 'WordPress Version',
            'value' => $wp_version,
            'status' => version_compare($wp_version, '5.0', '>=') ? 'ok' : 'error',
            'message' => version_compare($wp_version, '5.0', '>=') 
                ? 'WordPress version is compatible' 
                : 'WordPress 5.0 or higher is required'
        );
        
        // 必要な関数のチェック
        $required_functions = array('curl_init', 'json_decode', 'wp_remote_get');
        foreach ($required_functions as $func) {
            $status['function_' . $func] = array(
                'label' => 'Function: ' . $func,
                'value' => function_exists($func) ? 'Available' : 'Missing',
                'status' => function_exists($func) ? 'ok' : 'error',
                'message' => function_exists($func) 
                    ? $func . ' is available' 
                    : $func . ' is required but not available'
            );
        }
        
        // ディレクトリの書き込み権限チェック
        $dirs = array(
            'cache' => JGRANTS_PLUGIN_PATH . 'cache',
            'logs' => JGRANTS_PLUGIN_PATH . 'logs'
        );
        
        foreach ($dirs as $label => $dir) {
            $status['dir_' . $label] = array(
                'label' => ucfirst($label) . ' Directory',
                'value' => $dir,
                'status' => is_writable($dir) ? 'ok' : 'warning',
                'message' => is_writable($dir) 
                    ? 'Directory is writable' 
                    : 'Directory is not writable'
            );
        }
        
        return $status;
    }
}

// 初期化
add_action('init', array('JGrants_Debug', 'init'));