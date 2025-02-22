<?php
/**
 * قالب فرم ارسال پیام
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
    return '<div class="msg-error">لطفا برای دسترسی به سیستم پیامرسانی وارد شوید.</div>';
}

// بررسی محدودیتهای ارسال
$limit_check = msg_system_check_limits();
if (is_wp_error($limit_check)) {
    return '<div class="msg-error">' . $limit_check->get_error_message() . '</div>';
}

// دریافت تنظیمات و برچسبها
$labels = msg_system_get_labels();
$groups = msg_system_get_groups();
?>

<div class="msg-system-container">
    <div class="msg-form-wrapper">
        <h2><?php echo esc_html($labels['form_title']); ?></h2>
        
        <form id="msg-send-form" class="msg-form" method="post" enctype="multipart/form-data">
            <!-- بخش انتخاب گروه -->
            <div class="form-group">
                <label><?php echo esc_html($labels['recipient_group']); ?></label>
                <div class="group-selector">
                    <?php foreach ($groups as $key => $value): ?>
                        <label class="group-option">
                            <input type="radio" name="group" value="<?php echo esc_attr($key); ?>" required>
                            <span class="group-name"><?php echo esc_html($value); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- بخش انتخاب کاربر -->
            <div class="form-group user-selection" style="display: none;">
                <label><?php echo esc_html($labels['user_selection']); ?></label>
                <div class="user-search-container">
                    <input type="text" id="user-search" class="user-search-input" 
                           placeholder="جستجوی نام کاربر...">
                    <select name="recipient_id" id="recipient-select" required>
                        <option value="">لطفا یک کاربر را انتخاب کنید</option>
                    </select>
                </div>
            </div>

            <!-- بخش متن پیام -->
            <div class="form-group">
                <label><?php echo esc_html($labels['message']); ?></label>
                <?php
                wp_editor('', 'message_content', array(
                    'media_buttons' => false,
                    'textarea_rows' => 8,
                    'teeny' => true,
                    'quicktags' => array('buttons' => 'strong,em,link,ul,ol,li,close'),
                    'tinymce' => array(
                        'toolbar1' => 'formatselect,bold,italic,bullist,numlist,link,unlink,undo,redo',
                        'toolbar2' => '',
                        'content_style' => 'body { font-family: Tahoma, Arial, sans-serif; font-size: 14px; }'
                    )
                ));
                ?>
            </div>

            <!-- بخش پیوستها -->
            <div class="form-group">
                <label><?php echo esc_html($labels['attachments']); ?></label>
                <div class="attachment-container">
                    <div class="attachment-dropzone" id="attachment-dropzone">
                        <input type="file" name="attachments[]" id="file-input" multiple 
                               accept=".jpg,.jpeg,.png,.pdf" style="display: none;">
                        <div class="dropzone-content">
                            <span class="dashicons dashicons-upload"></span>
                            <p>فایلها را اینجا رها کنید یا کلیک کنید</p>
                            <small>
                                فرمتهای مجاز: JPG, PNG, PDF<br>
                                حداکثر حجم هر فایل: <?php echo get_option('msg_system_max_file_size', 20); ?> مگابایت<br>
                                حداکثر تعداد فایل: <?php echo get_option('msg_system_max_file_count', 3); ?> عدد
                            </small>
                        </div>
                    </div>
                    <div id="selected-files" class="selected-files"></div>
                </div>
            </div>

            <!-- دکمههای فرم -->
            <div class="form-actions">
                <button type="submit" class="msg-submit-btn">
                    <span class="btn-text"><?php echo esc_html($labels['submit']); ?></span>
                    <span class="btn-loading" style="display: none;">
                        <span class="spinner"></span>
                        در حال ارسال...
                    </span>
                </button>
                <button type="reset" class="msg-reset-btn">پاک کردن فرم</button>
            </div>

            <?php wp_nonce_field('msg_system_nonce', 'message_nonce'); ?>
        </form>
    </div>

    <!-- نمایش پیشنمایش پیام -->
    <div class="msg-preview" style="display: none;">
        <h3>پیشنمایش پیام</h3>
        <div class="preview-content"></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // متغیرهای عمومی
    var dropzone = $('#attachment-dropzone');
    var fileInput = $('#file-input');
    var selectedFiles = $('#selected-files');
    var maxFileSize = <?php echo get_option('msg_system_max_file_size', 20) * 1024 * 1024; ?>;
    var maxFiles = <?php echo get_option('msg_system_max_file_count', 3); ?>;
    var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    // مدیریت رویداد انتخاب گروه
    $('input[name="group"]').change(function() {
        var selectedGroup = $(this).val();
        if (selectedGroup) {
            $('.user-selection').slideDown();
            loadGroupUsers(selectedGroup);
        } else {
            $('.user-selection').slideUp();
        }
    });

    // بارگذاری کاربران گروه
    function loadGroupUsers(group) {
        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: {
                action: 'msg_system_get_group_users',
                group: group,
                nonce: msgSystemVars.nonce
            },
            beforeSend: function() {
                $('#recipient-select').html('<option>در حال بارگذاری...</option>');
            },
            success: function(response) {
                if (response.success) {
                    var options = '<option value="">لطفا یک کاربر را انتخاب کنید</option>';
                    $.each(response.data, function(id, name) {
                        options += '<option value="' + id + '">' + name + '</option>';
                    });
                    $('#recipient-select').html(options);
                }
            }
        });
    }

    // مدیریت جستجوی کاربر
    $('#user-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('#recipient-select option').each(function() {
            var text = $(this).text().toLowerCase();
            $(this).toggle(text.indexOf(searchTerm) > -1);
        });
    });

    // مدیریت رویدادهای Drag & Drop
    dropzone.on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    }).on('dragleave', function() {
        $(this).removeClass('dragover');
    }).on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        handleFiles(e.originalEvent.dataTransfer.files);
    }).on('click', function() {
        fileInput.click();
    });

    fileInput.change(function() {
        handleFiles(this.files);
    });

    // مدیریت فایلهای آپلود شده
    function handleFiles(files) {
        var currentFiles = selectedFiles.children().length;
        var newFiles = Array.from(files);

        // بررسی تعداد فایلها
        if (currentFiles + newFiles.length > maxFiles) {
            alert('حداکثر تعداد فایل مجاز: ' + maxFiles);
            return;
        }

        // بررسی هر فایل
        newFiles.forEach(function(file) {
            // بررسی نوع فایل
            if (!allowedTypes.includes(file.type)) {
                alert('فرمت فایل ' + file.name + ' مجاز نیست');
                return;
            }

            // بررسی حجم فایل
            if (file.size > maxFileSize) {
                alert('حجم فایل ' + file.name + ' بیشتر از حد مجاز است');
                return;
            }

            // نمایش فایل انتخاب شده
            var fileSize = (file.size / (1024 * 1024)).toFixed(2);
            var fileElement = $('<div class="selected-file">' +
                '<span class="file-name">' + file.name + ' (' + fileSize + ' MB)</span>' +
                '<button type="button" class="remove-file">&times;</button>' +
                '</div>');

            selectedFiles.append(fileElement);
        });
    }

    // حذف فایل
    $(document).on('click', '.remove-file', function() {
        $(this).parent().remove();
    });

    // ارسال فرم
    $('#msg-send-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.msg-submit-btn');
        var formData = new FormData(this);
        
        // افزودن محتوای ویرایشگر
        formData.append('message', tinymce.get('message_content').getContent());

        $.ajax({
            url: msgSystemVars.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                submitBtn.prop('disabled', true)
                    .find('.btn-text').hide()
                    .siblings('.btn-loading').show();
            },
            success: function(response) {
                if (response.success) {
                    alert('پیام با موفقیت ارسال شد');
                    form[0].reset();
                    tinymce.get('message_content').setContent('');
                    selectedFiles.empty();
                    $('.user-selection').slideUp();
                } else {
                    alert('خطا: ' + response.data);
                }
            },
            error: function() {
                alert('خطا در ارسال پیام');
            },
            complete: function() {
                submitBtn.prop('disabled', false)
                    .find('.btn-text').show()
                    .siblings('.btn-loading').hide();
            }
        });
    });

    // پیشنمایش پیام
    var previewTimeout;
    tinymce.get('message_content').on('keyup', function() {
        clearTimeout(previewTimeout);
        previewTimeout = setTimeout(function() {
            var content = tinymce.get('message_content').getContent();
            if (content) {
                $('.msg-preview').slideDown()
                    .find('.preview-content').html(content);
            } else {
                $('.msg-preview').slideUp();
            }
        }, 500);
    });
});
</script>
