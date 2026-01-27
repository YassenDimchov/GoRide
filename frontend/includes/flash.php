<?php

if (!empty($_SESSION['flash_success'])): ?>
  <div class="form-note form-note-success">
    <?= htmlspecialchars($_SESSION['flash_success']) ?>
  </div>
  <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
  <div class="form-note form-note-error">
    <?= htmlspecialchars($_SESSION['flash_error']) ?>
  </div>
  <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>
