<?php
/**
 * قالب نمایش پیامهای دریافتی
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
    return '<div class="msg-error">لطفا برای مشاهده پیامهای دریافتی وارد شوید.</div>';
}

// دریافت پیامهای دریافتی
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$messages = MSG_System()->get_user_messages(get_current_user_id(), 'received', array(
    'paged' => $paged,
    'posts_per_page' => 10
));

// آمار پیامها
$stats = msg_system_update_stats();
?>

<div class="msg-system-container received-messages">
    <!-- نوار آمار -->
    <div class="msg-stats">
        <div class="stat-item">
            <span class="stat-label">کل پیامهای دریافتی:</span>
            <span class="stat-value"><?php echo esc_html($stats['total_received']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">پیامهای نخوانده:</span>
            <span class="stat-value unread-count"><?php echo esc_html($stats['unread']); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-label">آخرین بازدید:</span>
            <span class="stat-value"><?php echo esc_html(msg_system_format_date(strtotime($stats['last_activity']))); ?></span>
        </div>
    </div>

    <!-- نوار ابزار -->
    <div class="msg-toolbar">
        <div class="bulk-actions">
            <select id="bulk-action">
                <option value="">انتخاب عملیات گروهی</option>
                <option value="mark-read">علامتگذاری به عنوان خوانده شده</option>
                <option value="delete">حذف</option>
            </select>
            <button type="button" id="apply-bulk-action" disabled>اعمال</button>
        </div>
        <div class="search-box">
            <input type="text" id="message-search" placeholder="جستجو در پیامها...">
            <button type="button" id="search-btn">
                <span class="dashicons dashicons-search"></span>
            </button>
        </div>
        <div class="filter-box">
            <select id="status-filter">
                <option value="">همه پیامها</option>
                <option value="unread">نخوانده</option>
                <option value="read">خوانده شده</option>
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
                $sender = $message_data['sender'];
                $read_status = $message_data['read_status'];
                $attachments = $message_data['attachments'];
                $priority = $message_data['priority'];
            ?>
                <div class="message-item <?php echo $read_status ? 'read' : 'unread'; ?>" 
                     data-id="<?php echo get_the_ID(); ?>">
                    <div class="message-checkbox">
                        <input type="checkbox" class="message-select" 
                               value="<?php echo get_the_ID(); ?>">
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <div class="sender-info">
                                <img src="<?php echo esc_url($sender['avatar']); ?>" 
                                     alt="<?php echo esc_attr($sender['name']); ?>" class="avatar">
                                <div class="sender-details">
                                    <span class="sender-name"><?php echo esc_html($sender['name']); ?></span>
                                    <span class="sender-role"><?php echo esc_html($sender['role']); ?></span>
                                </div>
                            </div>
                            <div class="message-meta">
                                <?php if ($priority): ?>
                                    <span class="priority-badge priority-<?php echo esc_attr($priority); ?>">
                                        <?php echo esc_html(ucfirst($priority)); ?>
                                    </span>
                                <?php endif; ?>
                                <span class="message-date" 
                                      data-timestamp="<?php echo get_the_date('c'); ?>">
                                    <?php echo esc_html($message_data['date']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="message-body">
                            <h4 class="message-title">
                                <?php echo esc_html(get_the_title()); ?>
                                <?php if (!empty($attachments)): ?>
                                    <span class="attachment-indicator" 
                                          title="<?php echo count($attachments); ?> پیوست">
                                        <span class="dashicons dashicons-paperclip"></span>
                                    </span>
                                <?php endif; ?>
                            </h4>
                            <div class="message-excerpt">
                                <?php echo wp_trim_words(wp_strip_all_tags(get_the_content()), 30); ?>
                            </div>
                        </div>

                        <div class="message-actions">
                            <button type="button" class="view-message">
                                <span class="dashicons dashicons-visibility"></span>
                                مشاهده
                            </button>
                            <?php if (!$read_status): ?>
                                <button type="button" class="mark-read">
                                    <span class="dashicons dashicons-yes"></span>
                                    علامت خوانده شده
                                </button>
                            <?php endif; ?>
                            <button type="button" class="reply-message">
                                <span class="dashicons dashicons-reply"></span>
                                پاسخ
                            </button>
                            <button type="button" class="delete-message">
                                <span class="dashicons dashicons-trash"></span>
                                حذف
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- پاگینیشن -->
        <div class="msg-pagination">
            <?php
            echo paginate_links(array(
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format' => '?paged=%#%',
                'current' => max(1, $paged),
                'total' => $messages->max_num_pages,
                'prev_text' => '&raquo;',
                'next_text' => '&laquo;',
                'type' => 'list'
            ));
            ?>
        </div>

    <?php else: ?>
        <div class="no-messages">
            <div class="empty-state">
                <span class="dashicons dashicons-email"></span>
                <h3>صندوق دریافت خالی است</h3>
                <p>شما هیچ پیام دریافتی ندارید.</p>
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
        <div class="modal-footer">
            <button type="button" class="modal-reply">پاسخ</button>
            <button type="button" class="modal-close">بستن</button>
        </div>
    </div>
</div>

<!-- مودال پاسخ -->
<div id="reply-modal" class="msg-modal" style="display: none;">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-header">
            <h3>ارسال پاسخ</h3>
        </div>
        <div class="modal-body">
            <form id="reply-form">
                <div class="form-group">
                    <label for="reply-content">متن پاسخ:</label>
                    <?php
                    wp_editor('', 'reply-content', array(
                        'media_buttons' => false,
                        'textarea_rows' => 6,
                        'teeny' => true,
                        'quicktags' => false
                    ));
                    ?>
                </div>
                <div class="form-group">
                    <label>پیوست:</label>
                    <input type="file" name="attachments[]" multiple 
                           accept=".jpg,.jpeg,.png,.pdf">
                </div>
                <input type="hidden" name="original_message_id" value="">
                <?php wp_nonce_field('msg_system_reply_nonce', 'reply_nonce'); ?>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" id="send-reply">ارسال پاسخ</button>
            <button type="button" class="modal-close">انصراف</button>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // انتخاب همه پیامها
    $('.select-all').change(function() {
        $('.message-select').prop('checked', $(this).prop('checked'));
        updateBulkActionButton();
    });

    // بهروزرسانی دکمه عملیات گروهی
    $('.message-select').change(function() {
        updateBulkActionButton();
    });

    function updateBulkActionButton() {
        $('#apply-bulk-action').prop('disabled', !$('.message-select:checked').length);
    }

    // اعمال عملیات گروهی
    $('#apply-bulk-action').click(function() {
        var action = $('#bulk-action').val();
        if (!action) return;

        var selectedIds = $('.message-select:checked').map(function() {
            return $(this).val();
        }).get();

        if (action === 'delete' && !confirm('آیا از حذف پیامهای انتخاب شده اطمینان دارید؟')) {
            return;
        }

        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_bulk_action',
                message_ids: selectedIds,
                bulk_action: action,
                nonce: msgSystemVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('خطا در انجام عملیات');
                }
            }
        });
    });

    // جستجو در پیامها
    var searchTimeout;
    $('#message-search').on('input', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val().toLowerCase();
        
        searchTimeout = setTimeout(function() {
            $('.message-item').each(function() {
                var text = $(this).text().toLowerCase();
                $(this).toggle(text.indexOf(searchTerm) > -1);
            });
        }, 300);
    });

    // فیلتر وضعیت
    $('#status-filter').change(function() {
        var status = $(this).val();
        $('.message-item').each(function() {
            if (!status || 
                (status === 'read' && $(this).hasClass('read')) || 
                (status === 'unread' && $(this).hasClass('unread'))) {
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

    // مشاهده پیام
    $('.view-message').click(function() {
        var messageItem = $(this).closest('.message-item');
        var messageId = messageItem.data('id');

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
                    
                    if (messageItem.hasClass('unread')) {
                        markAsRead(messageId, messageItem);
                    }
                }
            }
        });
    });

    // علامتگذاری به عنوان خوانده شده
    $('.mark-read').click(function() {
        var messageItem = $(this).closest('.message-item');
        var messageId = messageItem.data('id');
        markAsRead(messageId, messageItem);
    });
function markAsRead(messageId, messageItem) {
        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_mark_as_read',
                message_id: messageId,
                nonce: msgSystemVars.nonce
            },
            success: function(response) {
                if (response.success) {
                    messageItem.removeClass('unread').addClass('read');
                    messageItem.find('.mark-read').remove();
                    
                    // بهروزرسانی شمارنده پیامهای نخوانده
                    var unreadCount = parseInt($('.unread-count').text()) - 1;
                    $('.unread-count').text(Math.max(0, unreadCount));
                }
            }
        });
    }

    // پاسخ به پیام
    $('.reply-message, .modal-reply').click(function() {
        var messageId = $(this).closest('.message-item, .modal-content').find('[data-id]').data('id');
        $('#reply-modal').find('input[name="original_message_id"]').val(messageId);
        $('#message-modal').fadeOut();
        $('#reply-modal').fadeIn();
    });

    // ارسال پاسخ
    $('#send-reply').click(function() {
        var form = $('#reply-form');
        var formData = new FormData(form[0]);
        formData.append('action', 'msg_system_send_reply');
        formData.append('message', tinymce.get('reply-content').getContent());

        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#send-reply').prop('disabled', true).text('در حال ارسال...');
            },
            success: function(response) {
                if (response.success) {
                    alert('پاسخ با موفقیت ارسال شد');
                    $('#reply-modal').fadeOut();
                    form[0].reset();
                    tinymce.get('reply-content').setContent('');
                } else {
                    alert('خطا در ارسال پاسخ: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارسال پاسخ');
            },
            complete: function() {
                $('#send-reply').prop('disabled', false).text('ارسال پاسخ');
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

    // مدیریت مودالها
    $('.close-modal, .modal-close').click(function() {
        $(this).closest('.msg-modal').fadeOut();
    });

    $(document).click(function(e) {
        if ($(e.target).hasClass('msg-modal')) {
            $('.msg-modal').fadeOut();
        }
    });

    // نمایش پیشنمایش پیام در هنگام تایپ پاسخ
    var previewTimeout;
    if (typeof tinymce !== 'undefined' && tinymce.get('reply-content')) {
        tinymce.get('reply-content').on('keyup', function() {
            clearTimeout(previewTimeout);
            previewTimeout = setTimeout(function() {
                var content = tinymce.get('reply-content').getContent();
                if (content) {
                    $('.reply-preview').html(content).show();
                } else {
                    $('.reply-preview').hide();
                }
            }, 500);
        });
    }
});
</script>
