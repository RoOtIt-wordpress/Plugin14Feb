<?php
/**
 * توابع مدیریت تنظیمات سیستم پیام‌رسانی
 * 
 * @package Messaging System
 * @author RoOtIt-dev
 * @version 1.0.0
 * @since 2025-02-13
 */

// جلوگیری از دسترسی مستقیم
if (!defined('ABSPATH')) {
    exit;
}

// فقط توابع را تعریف می‌کنیم، بدون ثابت‌ها و کلاس‌ها

// تعریف توابع فقط در صورتی که قبلاً تعریف نشده‌اند
if (!function_exists('msg_system_register_settings')):
function msg_system_register_settings() {
    register_setting('msg_system_settings', 'msg_system_user_groups');
    register_setting('msg_system_settings', 'msg_system_labels');
    register_setting('msg_system_settings', 'msg_system_max_file_size');
    register_setting('msg_system_settings', 'msg_system_max_file_count');
    register_setting('msg_system_settings', 'msg_system_allowed_extensions');
    register_setting('msg_system_settings', 'msg_system_email_notifications');
}
endif;

if (!function_exists('msg_system_settings_page')):
function msg_system_settings_page() {
    // کد تابع settings_page بدون تغییر
}
endif;

if (!function_exists('msg_system_groups_page')):
function msg_system_groups_page() {
    // کد تابع groups_page بدون تغییر
}
endif;

if (!function_exists('msg_system_labels_page')):
function msg_system_labels_page() {
    // کد تابع labels_page بدون تغییر
}
endif;