<?php include 'header.php'; ?>

<div class="dynamic-form-container" id="dfb-form-<?php echo $form->id; ?>">
    <?php echo $success_message ?? ''; ?>
    <?php echo $error_messages ?? ''; ?>
    
    <h3 class="dfb-form-title"><?php echo htmlspecialchars($form->title); ?></h3>
    
    <?php if (!empty($form->description)): ?>
        <div class="dfb-form-description"><?php echo nl2br(htmlspecialchars($form->description)); ?></div>
    <?php endif; ?>
    
    <form method="post" class="dfb-form" enctype="multipart/form-data">
        <input type="hidden" name="dfb_form_id" value="<?php echo $form->id; ?>">
        <input type="hidden" name="dfb_submit" value="1">
        
        <?php foreach ($fields as $field): ?>
            <div class="dfb-field-container">
                <label><?php echo htmlspecialchars($field->label); ?></label>
                <input type="text" name="dfb_field_<?php echo $field->id; ?>" 
                       <?php echo $field->required ? 'required' : ''; ?>>
            </div>
        <?php endforeach; ?>
        
        <div class="dfb-submit-container">
            <button type="submit" class="dfb-submit-button">Submit</button>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>