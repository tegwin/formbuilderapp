<?php include 'header.php'; ?>

<div class="container">
    <h1>Available Forms</h1>
    
    <?php if (empty($forms)): ?>
        <p>No forms available.</p>
    <?php else: ?>
        <ul class="forms-list">
            <?php foreach ($forms as $form): ?>
                <li>
                    <a href="index.php?form_id=<?php echo $form->id; ?>">
                        <?php echo htmlspecialchars($form->title); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <p>
        <a href="index.php?dfb_admin=1" class="button">Admin Panel</a>
    </p>
</div>

<?php include 'footer.php'; ?>