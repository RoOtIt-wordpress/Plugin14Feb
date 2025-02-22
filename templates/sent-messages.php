<?php
/**
 * قالب نمایش پیامهای ارسالی
 * 
 * @package Messaging System
 * @author akamsafirrootit
 * @version 1.0.0
 * @since 2025-02-13
 */

if (!defined('ABSPATH')) {
    exit;
}

// بررسی لاگین بودن کاربر
if (!is_user_logged_in()) {
    return '<div class="msg-error">لطفا برای مشاهده پیامهای ارسالی وارد شوید.</div>';
}

// دریافت پیامهای ارسالی
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$messages = MSG_System()->get_user_messages(get_current_user_id(), 'sent', array(
    'paged' => $paged,
    'posts_per_page' => 10
));

// آمار پیامها
$stats = msg_system_update_stats();
?>

<div class="msg-system-container sent-messages">
    <!-- نوار آمار -->
    <div class="msg-stats">
        <div class="stat-item">
            <span class="stat-label">کل پیامهای ارسالی:</span>
            <span class="stat-value"><?php echo esc_html($stats['total_sent']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">آخرین فعالیت:</span>
            <span class="stat-value"><?php echo esc_html(msg_system_format_date(strtotime($stats['last_activity']))); ?></span>
        </div>
    </div>

    <!-- نوار ابزار -->
    <div class="msg-toolbar">
        <div class="search-box">
            <input type="text" id="message-search" placeholder="جستجو در پیامها...">
            <button type="button" id="search-btn">
                <span class="dashicons dashicons-search"></span>
            </button>
        </div>
        <div class="filter-box">
            <select id="group-filter">
                <option value="">همه گروهها</option>
                <?php foreach (msg_system_get_groups() as $key => $value): ?>
                    <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($value); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="date-filter">
                <option value="">همه زمانها</option>
                <option value="today">امروز</option>
                <option value="week">هفته اخیر</option>
                <option value="month">ماه اخیر</option>
            </select>
        </div>
    </div>

    <?php if ($messages->have_posts()): ?>
        <!-- لیست پیامها -->
        <div class="messages-list">
            <?php while ($messages->have_posts()): $messages->the_post(); 
                $message_data = msg_system_get_message_data(get_the_ID());
                $recipient = $message_data['recipient'];
                $read_status = $message_data['read_status'];
                $attachments = $message_data['attachments'];
            ?>
                <div class="message-item" data-id="<?php echo get_the_ID(); ?>" 
                     data-group="<?php echo esc_attr($message_data['group']); ?>">
                    <div class="message-header">
                        <div class="recipient-info">
                            <img src="<?php echo esc_url($recipient['avatar']); ?>" 
                                 alt="<?php echo esc_attr($recipient['name']); ?>" class="avatar">
                            <span class="recipient-name"><?php echo esc_html($recipient['name']); ?></span>
                            <span class="recipient-role"><?php echo esc_html($recipient['role']); ?></span>
                        </div>
                        <div class="message-meta">
                            <span class="message-date"><?php echo esc_html($message_data['date']); ?></span>
                            <span class="message-status <?php echo $read_status ? 'read' : 'unread'; ?>">
                                <?php echo $read_status ? 'خوانده شده' : 'خوانده نشده'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="message-preview">
                        <h4 class="message-title"><?php echo esc_html(get_the_title()); ?></h4>
                        <div class="message-excerpt">
                            <?php echo wp_trim_words(wp_strip_all_tags(get_the_content()), 20); ?>
                        </div>
                    </div>

                    <?php if (!empty($attachments)): ?>
                        <div class="message-attachments">
                            <span class="attachments-label">پیوستها:</span>
                            <?php foreach ($attachments as $attachment): ?>
                                <a href="<?php echo esc_url($attachment['url']); ?>" 
                                   class="attachment-link" target="_blank" download>
                                    <?php echo esc_html($attachment['name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="message-actions">
                        <button type="button" class="view-message">مشاهده کامل</button>
                        <button type="button" class="delete-message">حذف</button>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- پاگینیشن -->
        <?php
        echo paginate_links(array(
            'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
            'format' => '?paged=%#%',
            'current' => max(1, $paged),
            'total' => $messages->max_num_pages,
            'prev_text' => '&raquo;',
            'next_text' => '&laquo;'
        ));
        ?>

    <?php else: ?>
        <div class="no-messages">
            <div class="empty-state">
                <span class="dashicons dashicons-email-alt"></span>
                <h3>پیامی یافت نشد</h3>
                <p>شما هنوز پیامی ارسال نکردهاید.</p>
                <a href="<?php echo esc_url(home_url('/send-message')); ?>" class="msg-button">
                    ارسال پیام جدید
                </a>
            </div>
        </div>
    <?php endif; 
    wp_reset_postdata();
    ?>
</div>

<!-- مودال نمایش پیام -->
<div id="message-modal" class="msg-modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-body"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // جستجو در پیامها
    var searchTimeout;
    $('#message-search').on('input', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        
        searchTimeout = setTimeout(function() {
            $('.message-item').each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm.toLowerCase()) > -1);
            });
        }, 300);
    });

    // فیلتر گروه
    $('#group-filter').change(function() {
        var selectedGroup = $(this).val();
        $('.message-item').each(function() {
            if (!selectedGroup || $(this).data('group') === selectedGroup) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    // فیلتر تاریخ
    $('#date-filter').change(function() {
        var period = $(this).val();
        if (!period) {
            $('.message-item').show();
            return;
        }

        var now = new Date();
        var cutoff = new Date();

        switch(period) {
            case 'today':
                cutoff.setHours(0, 0, 0, 0);
                break;
            case 'week':
                cutoff.setDate(cutoff.getDate() - 7);
                break;
            case 'month':
                cutoff.setMonth(cutoff.getMonth() - 1);
                break;
        }

        $('.message-item').each(function() {
            var messageDate = new Date($(this).find('.message-date').data('timestamp'));
            $(this).toggle(messageDate >= cutoff);
        });
    });

    // مشاهده پیام کامل
    $('.view-message').click(function() {
        var messageId = $(this).closest('.message-item').data('id');
        
        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_get_message',
                message_id: messageId,
                nonce: msgSystemVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#message-modal .modal-body').html(response.data);
                    $('#message-modal').fadeIn();
                }
            }
        });
    });

    // حذف پیام
    $('.delete-message').click(function() {
        if (!confirm('آیا از حذف این پیام اطمینان دارید؟')) {
            return;
        }

        var messageItem = $(this).closest('.message-item');
        var messageId = messageItem.data('id');

        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_delete_message',
                message_id: messageId,
                nonce: msgSystemVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    messageItem.fadeOut(function() {
                        $(this).remove();
                        if ($('.message-item').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    alert('خطا در حذف پیام');
                }
            }
        });
    });

    // بستن مودال
    $('.close-modal').click(function() {
        $('#message-modal').fadeOut();
    });

    $(document).click(function(e) {
        if ($(e.target).is('#message-modal')) {
            $('#message-modal').fadeOut();
        }
    });
});
</script>
