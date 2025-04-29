<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($form->title); ?></title>
    <link rel="stylesheet" href="assets/css/frontend.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="dynamic-form-container" id="dfb-form-<?php echo $form->id; ?>">
        <?php echo $success_message; ?>
        <?php echo $error_messages; ?>
        
        <h3 class="dfb-form-title"><?php echo htmlspecialchars($form->title); ?></h3>
        
        <?php if (!empty($form->description)): ?>
            <div class="dfb-form-description"><?php echo nl2br(htmlspecialchars($form->description)); ?></div>
        <?php endif; ?>
        
        <form method="post" class="dfb-form" enctype="multipart/form-data">
            <input type="hidden" name="dfb_form_id" value="<?php echo $form->id; ?>">
            <input type="hidden" name="dfb_submit" value="1">
            
            <?php foreach ($fields as $field): ?>
                <div class="dfb-field-container dfb-field-type-<?php echo htmlspecialchars($field->field_type); ?> <?php echo htmlspecialchars($field->field_class); ?>"
                     <?php if (!empty($field->validation_rules)): ?>data-validation="<?php echo htmlspecialchars($field->validation_rules); ?>"<?php endif; ?>>
                    
                    <?php if ($field->field_type !== 'hidden' && $field->field_type !== 'html'): ?>
                        <label for="dfb-field-<?php echo $field->id; ?>" class="dfb-field-label">
                            <?php echo htmlspecialchars($field->label); ?>
                            <?php if ($field->required): ?>
                                <span class="dfb-required">*</span>
                            <?php endif; ?>
                        </label>
                    <?php endif; ?>
                    
                    <?php
                    // Render field based on type
                    $field_name = 'dfb_field_' . $field->id;
                    $field_id = !empty($field->field_id) ? $field->field_id : 'dfb-field-' . $field->id;
                    $field_value = isset($_SESSION['dfb_form_data'][$field_name]) ? $_SESSION['dfb_form_data'][$field_name] : '';
                    $required = $field->required ? 'required' : '';
                    $placeholder = !empty($field->placeholder) ? 'placeholder="' . htmlspecialchars($field->placeholder) . '"' : '';
                    
                    switch ($field->field_type):
                        case 'text':
                        case 'email':
                        case 'url':
                        case 'password':
                        case 'tel':
                        case 'number':
                        case 'date':
                        case 'time':
                            ?>
                            <input type="<?php echo $field->field_type; ?>" 
                                  id="<?php echo htmlspecialchars($field_id); ?>" 
                                  name="<?php echo htmlspecialchars($field_name); ?>" 
                                  value="<?php echo htmlspecialchars($field_value); ?>" 
                                  <?php echo $placeholder; ?> 
                                  <?php echo $required; ?>>
                            <?php
                            break;
                            
                        case 'textarea':
                            ?>
                            <textarea id="<?php echo htmlspecialchars($field_id); ?>" 
                                     name="<?php echo htmlspecialchars($field_name); ?>" 
                                     <?php echo $placeholder; ?> 
                                     <?php echo $required; ?>><?php echo htmlspecialchars($field_value); ?></textarea>
                            <?php
                            break;
                            
                        case 'select':
                            $options = !empty($field->options) ? explode("\n", $field->options) : array();
                            ?>
                            <select id="<?php echo htmlspecialchars($field_id); ?>" 
                                   name="<?php echo htmlspecialchars($field_name); ?>" 
                                   <?php echo $required; ?>>
                                <?php if (!empty($field->placeholder)): ?>
                                    <option value="" disabled<?php echo empty($field_value) ? ' selected' : ''; ?>><?php echo htmlspecialchars($field->placeholder); ?></option>
                                <?php endif; ?>
                                
                                <?php foreach ($options as $option): 
                                    $option = trim($option);
                                    if (empty($option)) continue;
                                    
                                    // Check if option has a value and label (value|label)
                                    if (strpos($option, '|') !== false) {
                                        list($option_value, $option_label) = explode('|', $option, 2);
                                        $option_value = trim($option_value);
                                        $option_label = trim($option_label);
                                    } else {
                                        $option_value = $option_label = $option;
                                    }
                                    
                                    $selected = ($field_value == $option_value) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo htmlspecialchars($option_value); ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($option_label); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php
                            break;
                            
                        case 'radio':
                            $options = !empty($field->options) ? explode("\n", $field->options) : array();
                            ?>
                            <div class="dfb-radio-options">
                                <?php foreach ($options as $i => $option): 
                                    $option = trim($option);
                                    if (empty($option)) continue;
                                    
                                    // Check if option has a value and label (value|label)
                                    if (strpos($option, '|') !== false) {
                                        list($option_value, $option_label) = explode('|', $option, 2);
                                        $option_value = trim($option_value);
                                        $option_label = trim($option_label);
                                    } else {
                                        $option_value = $option_label = $option;
                                    }
                                    
                                    $checked = ($field_value == $option_value) ? 'checked' : '';
                                    $option_id = $field_id . '-' . $i;
                                    ?>
                                    <div class="dfb-radio-option">
                                        <input type="radio" 
                                              id="<?php echo htmlspecialchars($option_id); ?>" 
                                              name="<?php echo htmlspecialchars($field_name); ?>" 
                                              value="<?php echo htmlspecialchars($option_value); ?>" 
                                              <?php echo $checked; ?> 
                                              <?php echo $required; ?>>
                                        <label for="<?php echo htmlspecialchars($option_id); ?>"><?php echo htmlspecialchars($option_label); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php
                            break;
                            
                        case 'checkbox':
                            $options = !empty($field->options) ? explode("\n", $field->options) : array();
                            
                            // If no options, treat as single checkbox
                            if (empty($options)):
                                $checked = !empty($field_value) ? 'checked' : '';
                                ?>
                                <div class="dfb-checkbox-option">
                                    <input type="checkbox" 
                                          id="<?php echo htmlspecialchars($field_id); ?>" 
                                          name="<?php echo htmlspecialchars($field_name); ?>" 
                                          value="1" 
                                          <?php echo $checked; ?> 
                                          <?php echo $required; ?>>
                                    <label for="<?php echo htmlspecialchars($field_id); ?>"><?php echo htmlspecialchars($field->placeholder); ?></label>
                                </div>
                                <?php
                            else:
                                ?>
                                <div class="dfb-checkbox-options">
                                    <?php foreach ($options as $i => $option): 
                                        $option = trim($option);
                                        if (empty($option)) continue;
                                        
                                        // Check if option has a value and label (value|label)
                                        if (strpos($option, '|') !== false) {
                                            list($option_value, $option_label) = explode('|', $option, 2);
                                            $option_value = trim($option_value);
                                            $option_label = trim($option_label);
                                        } else {
                                            $option_value = $option_label = $option;
                                        }
                                        
                                        $option_id = $field_id . '-' . $i;
                                        $option_name = $field_name . '[]';
                                        
                                        // Check if value is in array of selected values
                                        $checked = '';
                                        if (is_array($field_value) && in_array($option_value, $field_value)) {
                                            $checked = 'checked';
                                        }
                                        ?>
                                        <div class="dfb-checkbox-option">
                                            <input type="checkbox" 
                                                  id="<?php echo htmlspecialchars($option_id); ?>" 
                                                  name="<?php echo htmlspecialchars($option_name); ?>" 
                                                  value="<?php echo htmlspecialchars($option_value); ?>" 
                                                  <?php echo $checked; ?>>
                                            <label for="<?php echo htmlspecialchars($option_id); ?>"><?php echo htmlspecialchars($option_label); ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif;
                            break;
                            
                        case 'file':
                            ?>
                            <input type="file" 
                                  id="<?php echo htmlspecialchars($field_id); ?>" 
                                  name="<?php echo htmlspecialchars($field_name); ?>" 
                                  <?php echo $required; ?>>
                            <div class="dfb-file-preview"></div>
                            <?php
                            break;
                            
                        case 'hidden':
                            ?>
                            <input type="hidden" 
                                  id="<?php echo htmlspecialchars($field_id); ?>" 
                                  name="<?php echo htmlspecialchars($field_name); ?>" 
                                  value="<?php echo htmlspecialchars($field_value); ?>">
                            <?php
                            break;
                            
                        case 'html':
                            ?>
                            <div class="dfb-html-content"><?php echo $field->placeholder; ?></div>
                            <?php
                            break;
                    endswitch;
                    ?>
                </div>
            <?php endforeach; ?>
            
            <div class="dfb-submit-container">
                <button type="submit" class="dfb-submit-button">Submit</button>
            </div>
        </form>
    </div>
    
    <script src="assets/js/frontend.js"></script>
</body>
</html>