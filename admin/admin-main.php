<?php
/**
 * صفحه اصلی مدیریت پیام‌رسانی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @since 2025-02-13 11:09:01
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی دسترسی کاربر
if (!current_user_can('manage_options')) {
    wp_die(__('شما اجازه دسترسی به این صفحه را ندارید.', 'msg-system'));
}

// دریافت آمار
$total_messages = wp_count_posts('msg_system_message');
$unread_count = msg_system_count_unread_messages();
$total_users = count_users();
$active_users = msg_system_get_active_users_count(); // تعداد کاربران فعال در پیام‌رسانی

// دریافت آخرین پیام‌ها
$recent_messages = get_posts(array(
    'post_type' => 'msg_system_message',
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<div class="wrap msg-system-admin">
    <h1>
        <span class="dashicons dashicons-email-alt"></span>
        <?php _e('سیستم پیام‌رسانی داخلی', 'msg-system'); ?>
    </h1>

    <div class="msg-system-dashboard">
        <!-- نوار وضعیت -->
        <div class="msg-system-stats">
            <div class="stat-box">
                <h3><?php _e('کل پیام‌ها', 'msg-system'); ?></h3>
                <span class="stat-number"><?php echo $total_messages->publish; ?></span>
            </div>
            <div class="stat-box">
                <h3><?php _e('پیام‌های نخوانده', 'msg-system'); ?></h3>
                <span class="stat-number"><?php echo $unread_count; ?></span>
            </div>
            <div class="stat-box">
                <h3><?php _e('کاربران فعال', 'msg-system'); ?></h3>
                <span class="stat-number"><?php echo $active_users; ?></span>
            </div>
            <div class="stat-box">
                <h3><?php _e('کل کاربران', 'msg-system'); ?></h3>
                <span class="stat-number"><?php echo $total_users['total_users']; ?></span>
            </div>
        </div>

        <!-- دسترسی سریع -->
        <div class="msg-system-quick-actions">
            <h2><?php _e('دسترسی سریع', 'msg-system'); ?></h2>
            <div class="quick-actions-grid">
                <a href="<?php echo admin_url('admin.php?page=message-system-options'); ?>" class="quick-action-box">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <span class="action-title"><?php _e('تنظیمات', 'msg-system'); ?></span>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=msg_system_message'); ?>" class="quick-action-box">
                    <span class="dashicons dashicons-email"></span>
                    <span class="action-title"><?php _e('مدیریت پیام‌ها', 'msg-system'); ?></span>
                </a>
                <a href="<?php echo admin_url('edit-tags.php?taxonomy=msg_system_group&post_type=msg_system_message'); ?>" class="quick-action-box">
                    <span class="dashicons dashicons-groups"></span>
                    <span class="action-title"><?php _e('گروه‌های کاربری', 'msg-system'); ?></span>
                </a>
                <a href="<?php echo get_permalink(get_option('msg_system_messages_page_id')); ?>" class="quick-action-box">
                    <span class="dashicons dashicons-visibility"></span>
                    <span class="action-title"><?php _e('مشاهده صفحه پیام‌ها', 'msg-system'); ?></span>
                </a>
            </div>
        </div>

        <!-- آخرین پیام‌ها -->
        <div class="msg-system-recent">
            <h2><?php _e('آخرین پیام‌ها', 'msg-system'); ?></h2>
            <?php if ($recent_messages) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('فرستنده', 'msg-system'); ?></th>
                            <th><?php _e('گیرنده', 'msg-system'); ?></th>
                            <th><?php _e('موضوع', 'msg-system'); ?></th>
                            <th><?php _e('تاریخ', 'msg-system'); ?></th>
                            <th><?php _e('وضعیت', 'msg-system'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_messages as $message) : 
                            $sender_id = get_post_meta($message->ID, '_msg_sender_id', true);
                            $recipient_id = get_post_meta($message->ID, '_msg_recipient_id', true);
                            $status = get_post_meta($message->ID, '_msg_read_status', true);
                            
                            $sender = get_userdata($sender_id);
                            $recipient = get_userdata($recipient_id);
                        ?>
                            <tr>
                                <td><?php echo $sender ? esc_html($sender->display_name) : '-'; ?></td>
                                <td><?php echo $recipient ? esc_html($recipient->display_name) : '-'; ?></td>
                                <td>
                                    <a href="<?php echo get_edit_post_link($message->ID); ?>">
                                        <?php echo esc_html($message->post_title); ?>
                                    </a>
                                </td>
                                <td><?php echo get_the_date('Y/m/d H:i', $message); ?></td>
                                <td>
                                    <?php if ($status) : ?>
                                        <span class="msg-status read">
                                            <?php _e('خوانده شده', 'msg-system'); ?>
                                        </span>
                                    <?php else : ?>
                                        <span class="msg-status unread">
                                            <?php _e('خوانده نشده', 'msg-system'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p class="msg-no-items">
                    <?php _e('هنوز هیچ پیامی ارسال نشده است.', 'msg-system'); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- راهنمای سریع -->
        <div class="msg-system-help">
            <h2><?php _e('راهنمای سریع', 'msg-system'); ?></h2>
            <div class="help-content">
                <p><?php _e('برای شروع کار با سیستم پیام‌رسانی:', 'msg-system'); ?></p>
                <ol>
                    <li><?php _e('از بخش "تنظیمات" پیکربندی اولیه را انجام دهید', 'msg-system'); ?></li>
                    <li><?php _e('گروه‌های کاربری مورد نیاز را ایجاد کنید', 'msg-system'); ?></li>
                    <li><?php _e('دسترسی‌های لازم را برای کاربران تنظیم کنید', 'msg-system'); ?></li>
                    <li><?php _e('صفحات پیام‌رسانی را در منوی سایت قرار دهید', 'msg-system'); ?></li>
                </ol>
                <p>
                    <a href="https://github.com/akamsafirrootit/messaging-system/wiki" target="_blank">
                        <?php _e('مشاهده مستندات کامل', 'msg-system'); ?> →
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<style>
/* استایل‌های اختصاصی صفحه داشبورد */
.msg-system-admin {
    max-width: 1200px;
    margin: 20px auto;
}

.msg-system-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #2271b1;
}

.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.quick-action-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-decoration: none;
    color: #444;
    transition: transform 0.2s;
}

.quick-action-box:hover {
    transform: translateY(-2px);
}

.msg-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
}

.msg-status.read {
    background: #e5f6e5;
    color: #1f8c1f;
}

.msg-status.unread {
    background: #fff4e5;
    color: #856404;
}

.help-content {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>