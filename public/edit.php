<?php
require_once __DIR__ . '/../src/bootstrap.php';

use CT275\Labs\Contact;

$contact = new Contact($PDO);

$id = isset($_REQUEST['id']) ? filter_var($_REQUEST['id'], FILTER_VALIDATE_INT) : false;

if (!$id || !($contact->find($id))) {
  redirect('/');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $avatarPath = $contact->avatar; // Mặc định giữ lại đường dẫn ảnh cũ
  $newUploadedFile = null;

  // 1. Kiểm tra nếu có chọn file ảnh mới
  if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/uploads/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    $fileTmpPath = $_FILES['avatar']['tmp_name'];
    $fileName = time() . '_' . basename($_FILES['avatar']['name']);
    $targetFilePath = $uploadDir . $fileName;

    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (in_array($fileType, $allowedTypes)) {
      if (move_uploaded_file($fileTmpPath, $targetFilePath)) {
        $newUploadedFile = $targetFilePath;
        $avatarPath = '/uploads/' . $fileName;
      }
    }
  }

  $contactData = [
    'name' => $_POST['name'] ?? '',
    'phone' => $_POST['phone'] ?? '',
    'notes' => $_POST['notes'] ?? '',
    'avatar' => $avatarPath
  ];

  // 2. Validate dữ liệu
  $errors = $contact->validate($contactData);

  if (empty($errors)) {
    // Nếu Validate thành công: Tiến hành xóa ảnh cũ thực tế trên đĩa (nếu vừa đổi ảnh mới)
    if ($newUploadedFile && !empty($contact->avatar)) {
      $oldAvatarFile = __DIR__ . '/..' . $contact->avatar;
      if (file_exists($oldAvatarFile)) {
        unlink($oldAvatarFile);
      }
    }

    $contact->fill($contactData);
    if ($contact->save()) {
      redirect('/');
    }
  } else {
    // Nếu dính lỗi Validate: Xóa file ảnh vừa lỡ upload tạm lên để tránh rác thư mục uploads
    if ($newUploadedFile && file_exists($newUploadedFile)) {
      unlink($newUploadedFile);
    }
  }
}

include_once __DIR__ . '/../src/partials/header.php';
?>

<body>
  <?php include_once __DIR__ . '/../src/partials/navbar.php' ?>

  <!-- Main Page Content -->
  <div class="container">

    <?php
    $subtitle = 'Update your contacts here.';
    include_once __DIR__ . '/../src/partials/heading.php';
    ?>

    <div class="row">
      <div class="col-12">

        <form method="post" enctype="multipart/form-data" class="col-md-6 offset-md-3">

          <input type="hidden" name="id" value="<?= $contact->id ?>">

          <!-- Name -->
          <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" name="name" class="form-control<?= isset($errors['name']) ? ' is-invalid' : '' ?>" maxlen="255" id="name" placeholder="Enter Name" value="<?= html_escape($_POST['name'] ?? $contact->name) ?>" />

            <?php if (isset($errors['name'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['name'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Phone -->
          <div class="mb-3">
            <label for="phone" class="form-label">Phone Number</label>
            <input type="text" name="phone" class="form-control<?= isset($errors['phone']) ? ' is-invalid' : '' ?>" maxlen="255" id="phone" placeholder="Enter Phone" value="<?= html_escape($_POST['phone'] ?? $contact->phone) ?>" />

            <?php if (isset($errors['phone'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['phone'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Notes -->
          <div class="mb-3">
            <label for="notes" class="form-label">Notes </label>
            <textarea name="notes" id="notes" class="form-control<?= isset($errors['notes']) ? ' is-invalid' : '' ?>" placeholder="Enter notes (maximum character limit: 255)"><?= html_escape($_POST['notes'] ?? $contact->notes) ?></textarea>

            <?php if (isset($errors['notes'])) : ?>
              <span class="invalid-feedback">
                <strong><?= $errors['notes'] ?></strong>
              </span>
            <?php endif ?>
          </div>

          <!-- Avatar -->
          <div class="mb-3">
            <label for="avatar" class="form-label">Avatar</label>
            <div class="mb-2">
              <?php
              $avatarSrc = !empty($contact->avatar) ? trim($contact->avatar) : '';
              if (!str_starts_with($avatarSrc, '/')) {
                $avatarSrc = '/' . $avatarSrc;
              }
              ?>
              <img id="avatar-preview-img"
                src="<?= !empty($contact->avatar) ? html_escape($avatarSrc) : '' ?>"
                alt="Preview"
                class="avatar-preview <?= empty($contact->avatar) ? 'd-none' : '' ?>"
                style="max-width: 150px; max-height: 150px; object-fit: cover; border-radius: 8px;">
            </div>
            <input type="file" name="avatar" class="form-control" id="avatar" accept="image/*">
          </div>

          <!-- Submit -->
          <button type="submit" name="submit" class="btn btn-primary">Update Contact</button>
        </form>

      </div>
    </div>

  </div>

  <?php include_once __DIR__ . '/../src/partials/footer.php' ?>

  <script>
    document.getElementById('avatar').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const preview = document.getElementById('avatar-preview-img');
        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
      }
    });
  </script>
</body>

</html>