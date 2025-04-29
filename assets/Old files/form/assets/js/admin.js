/**
 * Admin JavaScript for Dynamic Form Builder
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Form Builder
        initFormBuilder();
        
        // Field Management
        initFieldManagement();
        
        // Field Sorting
        initFieldSorting();
        
        // Form Settings
        initFormSettings();
        
        // Form Preview
        initFormPreview();
        
        // Form Delete Confirmation
        initDeleteConfirmation();
    });

    /**
     * Initialize Form Builder
     */
    function initFormBuilder() {
        // Add new field
        $('#dfb-add-field-button').on('click', function(e) {
            e.preventDefault();
            
            // Show field type selector
            $('#dfb-field-type-selector').slideDown();
        });
        
        // Select field type
        $('.dfb-field-type-option').on('click', function(e) {
            e.preventDefault();
            
            const fieldType = $(this).data('field-type');
            
            // Hide field type selector
            $('#dfb-field-type-selector').slideUp();
            
            // Show field editor with empty field
            showFieldEditor({
                id: '',
                field_type: fieldType,
                label: '',
                placeholder: '',
                options: '',
                required: 0,
                field_order: $('.dfb-field-list .dfb-field-item').length,
                field_class: '',
                field_id: '',
                validation_rules: ''
            });
        });
    }
    
    /**
     * Initialize Field Management
     */
    function initFieldManagement() {
        // Edit field
        $(document).on('click', '.dfb-edit-field', function(e) {
            e.preventDefault();
            
            const $fieldItem = $(this).closest('.dfb-field-item');
            const fieldId = $fieldItem.data('field-id');
            
            // Get field data
            const fieldData = {
                id: fieldId,
                field_type: $fieldItem.data('field-type'),
                label: $fieldItem.find('.dfb-field-label').text(),
                placeholder: $fieldItem.data('field-placeholder'),
                options: $fieldItem.data('field-options'),
                required: $fieldItem.data('field-required'),
                field_order: $fieldItem.data('field-order'),
                field_class: $fieldItem.data('field-class'),
                field_id: $fieldItem.data('field-id-attr'),
                validation_rules: $fieldItem.data('field-validation')
            };
            
            // Show field editor
            showFieldEditor(fieldData);
        });
        
        // Delete field
        $(document).on('click', '.dfb-delete-field', function(e) {
            e.preventDefault();
            
            if (!confirm(dfb_vars.confirm_delete)) {
                return;
            }
            
            const $fieldItem = $(this).closest('.dfb-field-item');
            const fieldId = $fieldItem.data('field-id');
            
            // If field hasn't been saved yet, just remove it
            if (!fieldId) {
                $fieldItem.remove();
                return;
            }
            
            // Ajax delete request
            $.ajax({
                url: dfb_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'dfb_delete_field',
                    field_id: fieldId,
                    nonce: dfb_vars.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $fieldItem.remove();
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification('Error deleting field', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error', 'error');
                }
            });
        });
        
        // Save field
        $(document).on('click', '#dfb-save-field-button', function(e) {
            e.preventDefault();
            
            const $form = $('#dfb-field-editor-form');
            const formData = $form.serialize();
            
            // Ajax save request
            $.ajax({
                url: dfb_vars.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        const fieldData = getFieldDataFromForm();
                        updateFieldItem(fieldData, response.data.field_id);
                        
                        // Hide field editor
                        $('#dfb-field-editor').slideUp();
                        
                        showNotification(response.data.message, 'success');
                    } else {
                        showNotification('Error saving field', 'error');
                    }
                },
                error: function() {
                    showNotification('Server error', 'error');
                }
            });
        });
        
        // Cancel field editing
        $(document).on('click', '#dfb-cancel-field-button', function(e) {
            e.preventDefault();
            
            // Hide field editor
            $('#dfb-field-editor').slideUp();
        });
        
        // Toggle field options based on field type
        $(document).on('change', '#field_type', function() {
            const fieldType = $(this).val();
            
            // Hide all option sections
            $('.dfb-field-option-section').hide();
            
            // Show common options
            $('.dfb-field-option-common').show();
            
            // Show specific options for field type
            $(`.dfb-field-option-${fieldType}`).show();
            
            // Special handling for field types
            if (['select', 'radio', 'checkbox'].includes(fieldType)) {
                $('.dfb-field-option-choices').show();
            }
            
            if (['text', 'email', 'url', 'tel', 'number', 'date', 'time', 'password'].includes(fieldType)) {
                $('.dfb-field-option-placeholder').show();
                $('.dfb-field-option-validation').show();
            }
            
            if (fieldType === 'textarea') {
                $('.dfb-field-option-placeholder').show();
            }
            
            if (fieldType === 'html') {
                $('.dfb-field-option-label').hide();
                $('.dfb-field-option-placeholder').show();
                $('.dfb-field-option-required').hide();
            }
            
            if (fieldType === 'hidden') {
                $('.dfb-field-option-label').hide();
                $('.dfb-field-option-placeholder').show();
                $('.dfb-field-option-required').hide();
            }
        });
    }
    
    /**
     * Initialize Field Sorting
     */
    function initFieldSorting() {
        $('.dfb-field-list').sortable({
            handle: '.dfb-field-drag-handle',
            update: function(event, ui) {
                // Update field order
                updateFieldOrder();
                
                // Save the new order via Ajax
                const fieldIds = $('.dfb-field-list .dfb-field-item').map(function() {
                    return $(this).data('field-id');
                }).get();
                
                $.ajax({
                    url: dfb_vars.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'dfb_sort_fields',
                        fields: fieldIds,
                        nonce: dfb_vars.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            showNotification(response.data.message, 'success');
                        }
                    }
                });
            }
        });
    }
    
    /**
     * Initialize Form Settings
     */
    function initFormSettings() {
        // Toggle form settings panel
        $('#dfb-toggle-form-settings').on('click', function(e) {
            e.preventDefault();
            
            $('#dfb-form-settings-panel').slideToggle();
        });
        
        // Save form settings
        $('#dfb-save-form-button').on('click', function(e) {
            e.preventDefault();
            
            const $form = $('#dfb-form-settings-form');
            const formData = $form.serialize();
            
            // Show loading state
            const $button = $(this);
            $button.prop('disabled', true).text('Saving...');
            
            // Ajax save request
            $.ajax({
                url: dfb_vars.ajax_url,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        showNotification(response.data.message, 'success');
                        
                        // If this is a new form, redirect to edit page
                        if (!$('#form_id').val() && response.data.form_id) {
                            window.location.href = `?page=dfb-edit-form&form_id=${response.data.form_id}`;
                        } else {
                            // Update form ID field
                            $('#form_id').val(response.data.form_id);
                        }
                    } else {
                        showNotification('Error saving form', 'error');
                    }
                    
                    // Reset button
                    $button.prop('disabled', false).text('Save Form');
                },
                error: function() {
                    showNotification('Server error', 'error');
                    
                    // Reset button
                    $button.prop('disabled', false).text('Save Form');
                }
            });
        });
    }
    
    /**
     * Initialize Form Preview
     */
    function initFormPreview() {
        $('#dfb-preview-form-button').on('click', function(e) {
            e.preventDefault();
            
            // Save form first
            $('#dfb-save-form-button').trigger('click');
            
            // Open preview in new window/tab
            const formId = $('#form_id').val();
            
            if (formId) {
                window.open(`?page=dfb-preview-form&form_id=${formId}`, '_blank');
            } else {
                showNotification('Please save the form first', 'error');
            }
        });
    }
    
    /**
     * Initialize Delete Confirmation
     */
    function initDeleteConfirmation() {
        $('.dfb-delete-form').on('click', function(e) {
            if (!confirm(dfb_vars.confirm_delete)) {
                e.preventDefault();
                return false;
            }
        });
    }
    
    /**
     * Show field editor with field data
     */
    function showFieldEditor(fieldData) {
        const $editor = $('#dfb-field-editor');
        
        // Set form values
        $('#field_id').val(fieldData.id);
        $('#field_type').val(fieldData.field_type);
        $('#label').val(fieldData.label);
        $('#placeholder').val(fieldData.placeholder);
        $('#options').val(fieldData.options);
        $('#required').prop('checked', fieldData.required == 1);
        $('#field_order').val(fieldData.field_order);
        $('#field_class').val(fieldData.field_class);
        $('#field_id_attr').val(fieldData.field_id);
        $('#validation_rules').val(fieldData.validation_rules);
        
        // Trigger change event to show/hide relevant options
        $('#field_type').trigger('change');
        
        // Show editor
        $editor.slideDown();
        
        // Scroll to editor
        $('html, body').animate({
            scrollTop: $editor.offset().top - 50
        }, 500);
    }
    
    /**
     * Get field data from form
     */
    function getFieldDataFromForm() {
        return {
            id: $('#field_id').val(),
            field_type: $('#field_type').val(),
            label: $('#label').val(),
            placeholder: $('#placeholder').val(),
            options: $('#options').val(),
            required: $('#required').is(':checked') ? 1 : 0,
            field_order: $('#field_order').val(),
            field_class: $('#field_class').val(),
            field_id: $('#field_id_attr').val(),
            validation_rules: $('#validation_rules').val()
        };
    }
    
    /**
     * Update or add field item in the list
     */
    function updateFieldItem(fieldData, fieldId) {
        const $existingItem = $(`.dfb-field-item[data-field-id="${fieldData.id}"]`);
        
        // Field type label
        const fieldTypeLabels = {
            text: 'Text',
            email: 'Email',
            url: 'URL',
            password: 'Password',
            tel: 'Telephone',
            number: 'Number',
            textarea: 'Textarea',
            select: 'Dropdown',
            radio: 'Radio Buttons',
            checkbox: 'Checkboxes',
            date: 'Date',
            time: 'Time',
            file: 'File Upload',
            hidden: 'Hidden Field',
            html: 'HTML Content'
        };
        
        // Create field item HTML
        const fieldHtml = `
            <div class="dfb-field-item" data-field-id="${fieldId || fieldData.id}" data-field-type="${fieldData.field_type}" 
                data-field-placeholder="${fieldData.placeholder}" data-field-options="${fieldData.options}" 
                data-field-required="${fieldData.required}" data-field-order="${fieldData.field_order}" 
                data-field-class="${fieldData.field_class}" data-field-id-attr="${fieldData.field_id}" 
                data-field-validation="${fieldData.validation_rules}">
                
                <div class="dfb-field-drag-handle">
                    <i class="fas fa-grip-vertical"></i>
                </div>
                
                <div class="dfb-field-preview">
                    <span class="dfb-field-type-badge">${fieldTypeLabels[fieldData.field_type] || fieldData.field_type}</span>
                    <span class="dfb-field-label">${fieldData.label}</span>
                    ${fieldData.required == 1 ? '<span class="dfb-required-badge">Required</span>' : ''}
                </div>
                
                <div class="dfb-field-actions">
                    <a href="#" class="dfb-edit-field"><i class="fas fa-edit"></i></a>
                    <a href="#" class="dfb-delete-field"><i class="fas fa-trash"></i></a>
                </div>
            </div>
        `;
        
        // Update or add field item
        if ($existingItem.length) {
            $existingItem.replaceWith(fieldHtml);
        } else {
            $('.dfb-field-list').append(fieldHtml);
        }
        
        // Update field order
        updateFieldOrder();
    }
    
    /**
     * Update field order
     */
    function updateFieldOrder() {
        $('.dfb-field-list .dfb-field-item').each(function(index) {
            $(this).data('field-order', index);
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'success') {
        const $notification = $('#dfb-notification');
        
        // If notification doesn't exist, create it
        if ($notification.length === 0) {
            $('body').append('<div id="dfb-notification"></div>');
        }
        
        // Set message and type
        $('#dfb-notification')
            .html(`<div class="dfb-notification-${type}">${message}</div>`)
            .fadeIn();
        
        // Auto-hide after 3 seconds
        setTimeout(function() {
            $('#dfb-notification').fadeOut();
        }, 3000);
    }
    
})(jQuery);