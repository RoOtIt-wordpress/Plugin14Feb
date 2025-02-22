<?php
/**
 * توابع عمومی پلاگین
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @since 2025-02-13 10:50:09
 */

if (!defined('ABSPATH')) {
    exit;
}

// تابع نمایش خطا
function msg_system_show_error($message) {
    return '<div class="msg-system-error">' . esc_html($message) . '</div>';
}

// تابع نمایش پیام موفقیت
function msg_system_show_success($message) {
    return '<div class="msg-system-success">' . esc_html($message) . '</div>';
}

// تابع دریافت برچسب‌ها
function msg_system_get_label($key) {
    $labels = get_option('msg_system_labels', array());
    return isset($labels[$key]) ? $labels[$key] : '';
}
// اضافه کردن این تابع به functions.php
function msg_system_get_active_users_count() {
    global $wpdb;
    
    // کاربرانی که در 30 روز گذشته پیام فرستاده یا دریافت کرده‌اند
    $thirty_days_ago = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    $sql = $wpdb->prepare("
        SELECT COUNT(DISTINCT CASE 
            WHEN meta_key = '_msg_sender_id' THEN meta_value
            WHEN meta_key = '_msg_recipient_id' THEN meta_value
        END) as active_users
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.post_type = 'msg_system_message'
        AND p.post_date >= %s
        AND meta_key IN ('_msg_sender_id', '_msg_recipient_id')
    ", $thirty_days_ago);
    
    return (int) $wpdb->get_var($sql);
}