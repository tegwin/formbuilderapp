/**
 * Frontend JavaScript for Dynamic Form Builder
 */

(function($) {
    'use strict';

    // Initialize when document is ready
    $(document).ready(function() {
        // Initialize all forms
        $('.dynamic-form-container').each(function() {
            initializeForm($(this));
        });
    });

    /**
     * Initialize a form
     */
    function initializeForm($formContainer) {
        const $form = $formContainer.find('form.dfb-form');
        
        // Form validation
        $form.on('submit', function(e) {
            const isValid = validateForm($form);
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
            
            // Show loading state
            $form.find('.dfb-submit-button').prop('disabled', true).addClass('dfb-loading');
            
            return true;
        });
        
        // Conditional logic for fields
        setupConditionalLogic($form);
        
        // File upload preview
        setupFileUploadPreview($form);
        
        // Character counter for text fields
        setupCharacterCounter($form);
    }
    
    /**
     * Validate form
     */
    function validateForm($form) {
        let isValid = true;
        
        // Remove existing error messages
        $form.find('.dfb-field-error').remove();
        $form.find('.dfb-field-container').removeClass('has-error');
        
        // Validate each required field
        $form.find('[required]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.dfb-field-container');
            const fieldType = $field.attr('type');
            
            // Skip fields that are conditionally hidden
            if ($container.is(':hidden')) {
                return;
            }
            
            let fieldValid = true;
            
            // Check if empty
            if (fieldType === 'checkbox' || fieldType === 'radio') {
                // For checkboxes and radios, check if any is checked
                const name = $field.attr('name');
                if ($form.find(`input[name="${name}"]:checked`).length === 0) {
                    fieldValid = false;
                }
            } else if (fieldType === 'file') {
                // For file inputs, check if a file is selected
                if ($field.get(0).files.length === 0) {
                    fieldValid = false;
                }
            } else {
                // For other inputs, check if value is empty
                if (!$field.val().trim()) {
                    fieldValid = false;
                }
            }
            
            // If field is required and empty, show error
            if (!fieldValid) {
                isValid = false;
                $container.addClass('has-error');
                
                const fieldLabel = $container.find('.dfb-field-label').text().trim();
                const errorMsg = `${fieldLabel} is required.`;
                
                $container.append(`<div class="dfb-field-error">${errorMsg}</div>`);
            }
        });
        
        // Validate email fields
        $form.find('input[type="email"]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.dfb-field-container');
            
            // Skip if empty (handled by required validation)
            if (!$field.val().trim()) {
                return;
            }
            
            // Skip fields that are conditionally hidden
            if ($container.is(':hidden')) {
                return;
            }
            
            // Email validation regex
            const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
            
            if (!emailRegex.test($field.val())) {
                isValid = false;
                $container.addClass('has-error');
                
                const fieldLabel = $container.find('.dfb-field-label').text().trim();
                const errorMsg = `Please enter a valid email address for ${fieldLabel}.`;
                
                $container.append(`<div class="dfb-field-error">${errorMsg}</div>`);
            }
        });
        
        // Validate URL fields
        $form.find('input[type="url"]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.dfb-field-container');
            
            // Skip if empty (handled by required validation)
            if (!$field.val().trim()) {
                return;
            }
            
            // Skip fields that are conditionally hidden
            if ($container.is(':hidden')) {
                return;
            }
            
            try {
                new URL($field.val());
            } catch (e) {
                isValid = false;
                $container.addClass('has-error');
                
                const fieldLabel = $container.find('.dfb-field-label').text().trim();
                const errorMsg = `Please enter a valid URL for ${fieldLabel}.`;
                
                $container.append(`<div class="dfb-field-error">${errorMsg}</div>`);
            }
        });
        
        // Validate number fields
        $form.find('input[type="number"]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.dfb-field-container');
            
            // Skip if empty (handled by required validation)
            if (!$field.val().trim()) {
                return;
            }
            
            // Skip fields that are conditionally hidden
            if ($container.is(':hidden')) {
                return;
            }
            
            // Get min/max attributes if set
            const min = $field.attr('min');
            const max = $field.attr('max');
            const value = parseFloat($field.val());
            
            let errorMsg = '';
            
            if (isNaN(value)) {
                errorMsg = `Please enter a valid number.`;
            } else if (min !== undefined && value < parseFloat(min)) {
                errorMsg = `Please enter a value greater than or equal to ${min}.`;
            } else if (max !== undefined && value > parseFloat(max)) {
                errorMsg = `Please enter a value less than or equal to ${max}.`;
            }
            
            if (errorMsg) {
                isValid = false;
                $container.addClass('has-error');
                
                const fieldLabel = $container.find('.dfb-field-label').text().trim();
                errorMsg = `${fieldLabel}: ${errorMsg}`;
                
                $container.append(`<div class="dfb-field-error">${errorMsg}</div>`);
            }
        });
        
        // Validate file uploads
        $form.find('input[type="file"]').each(function() {
            const $field = $(this);
            const $container = $field.closest('.dfb-field-container');
            
            // Skip if no file selected (handled by required validation)
            if ($field.get(0).files.length === 0) {
                return;
            }
            
            // Skip fields that are conditionally hidden
            if ($container.is(':hidden')) {
                return;
            }
            
            const file = $field.get(0).files[0];
            
            // Check file size (max 5MB by default)
            const maxSize = parseInt($field.data('max-size')) || 5 * 1024 * 1024;
            
            if (file.size > maxSize) {
                isValid = false;
                $container.addClass('has-error');
                
                const fieldLabel = $container.find('.dfb-field-label').text().trim();
                const maxSizeMB = Math.round(maxSize / (1024 * 1024) * 10) / 10;
                const errorMsg = `File size exceeds the maximum allowed size of ${maxSizeMB}MB for ${fieldLabel}.`;
                
                $container.append(`<div class="dfb-field-error">${errorMsg}</div>`);
            }
            
            // Check allowed file types if specified
            const allowedTypes = $field.data('allowed-types');
            
            if (allowedTypes) {
                const fileExt = file.name.split('.').pop().toLowerCase();
                const allowedExts = allowedTypes.split(',').map(type => type.trim().toLowerCase());
                
                if (!allowedExts.includes(fileExt)) {
                    isValid = false;
                    $container.addClass('has-error');
                    
                    const fieldLabel = $container.find('.dfb-field-label').text().trim();
                    const errorMsg = `File type not allowed for ${fieldLabel}. Allowed types: ${allowedTypes}.`;
                    
                    $container.append(`<div class="dfb-field-error">${errorMsg}</div>`);
                }
            }
        });
        
        // Scroll to first error
        if (!isValid) {
            const $firstError = $form.find('.has-error').first();
            
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }
        
        return isValid;
    }
    
    /**
     * Setup conditional logic for form fields
     */
    function setupConditionalLogic($form) {
        // Get all fields with conditional logic
        const $conditionalFields = $form.find('[data-conditional-field]');
        
        if ($conditionalFields.length === 0) {
            return;
        }
        
        // Function to evaluate conditions
        const evaluateConditions = function() {
            $conditionalFields.each(function() {
                const $field = $(this);
                const $container = $field.closest('.dfb-field-container');
                
                const targetFieldName = $field.data('conditional-field');
                const operator = $field.data('conditional-operator') || '==';
                const value = $field.data('conditional-value');
                
                const $targetField = $form.find(`[name="${targetFieldName}"], [name="${targetFieldName}[]"]`);
                
                if ($targetField.length === 0) {
                    return;
                }
                
                let fieldValue;
                
                // Get the value of the target field
                if ($targetField.is(':checkbox') || $targetField.is(':radio')) {
                    const $checkedFields = $form.find(`[name="${targetFieldName}"]:checked, [name="${targetFieldName}[]"]:checked`);
                    
                    if ($checkedFields.length === 0) {
                        fieldValue = '';
                    } else if ($checkedFields.length === 1) {
                        fieldValue = $checkedFields.val();
                    } else {
                        fieldValue = $checkedFields.map(function() {
                            return $(this).val();
                        }).get();
                    }
                } else {
                    fieldValue = $targetField.val();
                }
                
                // Evaluate condition
                let shouldShow = false;
                
                if (Array.isArray(fieldValue)) {
                    // For arrays (multiple checkboxes)
                    if (operator === '==' || operator === '=') {
                        shouldShow = fieldValue.includes(value);
                    } else if (operator === '!=' || operator === '<>') {
                        shouldShow = !fieldValue.includes(value);
                    }
                } else {
                    // For single values
                    if (operator === '==' || operator === '=') {
                        shouldShow = fieldValue == value;
                    } else if (operator === '!=' || operator === '<>') {
                        shouldShow = fieldValue != value;
                    } else if (operator === '>') {
                        shouldShow = parseFloat(fieldValue) > parseFloat(value);
                    } else if (operator === '>=') {
                        shouldShow = parseFloat(fieldValue) >= parseFloat(value);
                    } else if (operator === '<') {
                        shouldShow = parseFloat(fieldValue) < parseFloat(value);
                    } else if (operator === '<=') {
                        shouldShow = parseFloat(fieldValue) <= parseFloat(value);
                    } else if (operator === 'contains') {
                        shouldShow = fieldValue.indexOf(value) !== -1;
                    } else if (operator === 'empty') {
                        shouldShow = !fieldValue || fieldValue === '';
                    } else if (operator === 'not_empty') {
                        shouldShow = fieldValue && fieldValue !== '';
                    }
                }
                
                // Show or hide the field
                if (shouldShow) {
                    $container.show();
                    // Enable fields if they are required
                    $container.find('[required]').prop('disabled', false);
                } else {
                    $container.hide();
                    // Disable fields if they are required to avoid validation errors
                    $container.find('[required]').prop('disabled', true);
                }
            });
        };
        
        // Evaluate conditions on page load
        evaluateConditions();
        
        // Attach change event handlers to all form fields
        $form.find('input, select, textarea').on('change', function() {
            evaluateConditions();
        });
    }
    
    /**
     * Setup file upload preview
     */
    function setupFileUploadPreview($form) {
        const $fileInputs = $form.find('input[type="file"]');
        
        $fileInputs.each(function() {
            const $input = $(this);
            const $container = $input.closest('.dfb-field-container');
            
            // Create preview container if it doesn't exist
            if ($container.find('.dfb-file-preview').length === 0) {
                $container.append('<div class="dfb-file-preview"></div>');
            }
            
            const $preview = $container.find('.dfb-file-preview');
            
            // Handle file selection
            $input.on('change', function() {
                $preview.empty();
                
                if (this.files && this.files.length > 0) {
                    const file = this.files[0];
                    
                    // Display file name and size
                    const fileSize = Math.round(file.size / 1024);
                    const fileName = file.name;
                    
                    $preview.append(`<div class="dfb-file-info">
                        <span class="dfb-file-name">${fileName}</span>
                        <span class="dfb-file-size">(${fileSize} KB)</span>
                        <a href="#" class="dfb-file-remove">Remove</a>
                    </div>`);
                    
                    // Preview image if it's an image file
                    if (file.type.match('image.*')) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            $preview.append(`<img src="${e.target.result}" class="dfb-file-image-preview" />`);
                        };
                        
                        reader.readAsDataURL(file);
                    }
                }
            });
            
            // Handle file removal
            $container.on('click', '.dfb-file-remove', function(e) {
                e.preventDefault();
                
                $input.val('');
                $preview.empty();
            });
        });
    }
    
    /**
     * Setup character counter for text fields
     */
    function setupCharacterCounter($form) {
        const $textInputs = $form.find('input[type="text"], input[type="email"], input[type="url"], input[type="tel"], textarea');
        
        $textInputs.each(function() {
            const $input = $(this);
            const maxLength = $input.attr('maxlength');
            
            if (!maxLength) {
                return;
            }
            
            const $container = $input.closest('.dfb-field-container');
            
            // Create counter element
            if ($container.find('.dfb-char-counter').length === 0) {
                $container.append(`<div class="dfb-char-counter">0/${maxLength}</div>`);
            }
            
            const $counter = $container.find('.dfb-char-counter');
            
            // Update counter on input
            $input.on('input', function() {
                const charCount = $input.val().length;
                $counter.text(`${charCount}/${maxLength}`);
                
                if (charCount >= maxLength) {
                    $counter.addClass('dfb-char-limit-reached');
                } else {
                    $counter.removeClass('dfb-char-limit-reached');
                }
            });
            
            // Initialize counter
            $input.trigger('input');
        });
    }
    
})(jQuery);