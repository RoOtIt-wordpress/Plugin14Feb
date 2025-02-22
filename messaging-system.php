<?php
/**
 * Plugin Name: سیستم پیام‌رسانی داخلی
 * Plugin URI: https://github.com/RoOtIt-dev/wp-messaging-system
 * Description: افزونه پیام‌رسانی داخلی برای وردپرس با قابلیت ارسال فایل و گروه‌بندی کاربران
 * Version: 1.0.0
 * Author: RoOtIt-dev
 * Author URI: https://github.com/RoOtIt-dev
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: msg-system
 * Domain Path: /languages
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// تعریف ثابت‌ها
defined('MSG_SYSTEM_VERSION') || define('MSG_SYSTEM_VERSION', '1.0.0');
defined('MSG_SYSTEM_FILE') || define('MSG_SYSTEM_FILE', __FILE__);
defined('MSG_SYSTEM_PATH') || define('MSG_SYSTEM_PATH', plugin_dir_path(__FILE__));
defined('MSG_SYSTEM_URL') || define('MSG_SYSTEM_URL', plugin_dir_url(__FILE__));
defined('MSG_SYSTEM_BASENAME') || define('MSG_SYSTEM_BASENAME', plugin_basename(__FILE__));

// لود کردن فایل‌های مورد نیاز
require_once MSG_SYSTEM_PATH . 'admin/admin-settings.php';

// کلاس اصلی پلاگین
if (!class_exists('MSG_System')):
class MSG_System {
    // نمونه منحصر به فرد
    private static $instance = null;

    // گرفتن نمونه منحصر به فرد
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // سازنده کلاس
    private function __construct() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_filter('plugin_action_links_' . MSG_SYSTEM_BASENAME, array($this, 'add_settings_link'));

        // هوک‌های فعال‌سازی و غیرفعال‌سازی
        register_activation_hook(MSG_SYSTEM_FILE, array($this, 'activate'));
        register_deactivation_hook(MSG_SYSTEM_FILE, array($this, 'deactivate'));

        // ثبت تنظیمات
        if (is_admin()) {
            add_action('admin_init', 'msg_system_register_settings');
        }
    }

    // لود کردن ترجمه‌ها
    public function load_textdomain() {
        load_plugin_textdomain('msg-system', false, dirname(MSG_SYSTEM_BASENAME) . '/languages');
    }

    // افزودن منوی مدیریت
    public function add_admin_menu() {
        add_menu_page(
            __('سیستم پیام‌رسانی', 'msg-system'),
            __('پیام‌رسانی', 'msg-system'),
            'manage_options',
            'message-system-settings',
            'msg_system_settings_page',
            'dashicons-email',
            30
        );

        add_submenu_page(
            'message-system-settings',
            __('گروه‌های کاربری', 'msg-system'),
            __('گروه‌ها', 'msg-system'),
            'manage_options',
            'message-system-groups',
            'msg_system_groups_page'
        );

        add_submenu_page(
            'message-system-settings',
            __('برچسب‌ها و متون', 'msg-system'),
            __('برچسب‌ها', 'msg-system'),
            'manage_options',
            'message-system-labels',
            'msg_system_labels_page'
        );
    }

    // فعال‌سازی پلاگین
    public function activate() {
        global $wpdb;
        
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // جدول پیام‌ها
        $table_messages = $wpdb->prefix . 'msg_system_messages';
        $sql_messages = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            sender_id bigint(20) NOT NULL,
            recipient_id bigint(20) NOT NULL,
            subject varchar(255) NOT NULL,
            message longtext NOT NULL,
            attachment varchar(255),
            status varchar(20) NOT NULL DEFAULT 'unread',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        dbDelta($sql_messages);

        // افزودن تنظیمات پیش‌فرض
        $this->add_default_options();
    }

    // غیرفعال‌سازی پلاگین
    public function deactivate() {
        // در صورت نیاز به پاکسازی
    }

    // افزودن استایل‌ها و اسکریپت‌ها
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'message-system') !== false) {
            wp_enqueue_style(
                'msg-system-admin',
                MSG_SYSTEM_URL . 'assets/css/admin.css',
                array(),
                MSG_SYSTEM_VERSION
            );

            wp_enqueue_script(
                'msg-system-admin',
                MSG_SYSTEM_URL . 'assets/js/admin.js',
                array('jquery'),
                MSG_SYSTEM_VERSION,
                true
            );

            wp_localize_script('msg-system-admin', 'msgSystemAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('msg_system_nonce')
            ));
        }
    }

    // افزودن لینک تنظیمات
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=message-system-settings'),
            __('تنظیمات', 'msg-system')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

    // افزودن تنظیمات پیش‌فرض
    private function add_default_options() {
        $default_settings = array(
            'msg_system_max_file_size' => 20,
            'msg_system_max_file_count' => 3,
            'msg_system_allowed_extensions' => 'jpg,jpeg,png,pdf,doc,docx',
            'msg_system_email_notifications' => 1
        );

        foreach ($default_settings as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
endif;

// راه‌اندازی پلاگین
if (!function_exists('msg_system')):
    function msg_system() {
        return MSG_System::get_instance();
    }
endif;

msg_system();