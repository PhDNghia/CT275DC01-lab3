<?php
require_once __DIR__ . '/../src/bootstrap.php';

use CT275\Labs\Contact;
use CT275\Labs\Paginator;

$contact = new Contact($PDO);

$limit = (isset($_GET['limit']) && is_numeric($_GET['limit'])) ? (int)$_GET['limit'] : 5;
$page = (isset($_GET['page']) && is_numeric($_GET['page'])) ? (int)$_GET['page'] : 1;

$paginator = new Paginator(
  recordsPerPage: $limit,
  totalRecords: $contact->count(),
  currentPage: $page
);

$contacts = $contact->paginate($paginator->recordOffset, $paginator->recordsPerPage);
$pages = $paginator->getPages(length: 3);

include_once __DIR__ . '/../src/partials/header.php';
?>

<body>
  <?php include_once __DIR__ . '/../src/partials/navbar.php' ?>

  <div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Danh sách liên hệ</h2>
      <a href="/add.php" class="btn btn-primary">Thêm mới</a>
    </div>

    <table class="table table-bordered table-striped align-middle">
      <thead>
        <tr>
          <th>Avatar</th>
          <th>Họ tên</th>
          <th>Số điện thoại</th>
          <th>Ngày tạo</th>
          <th>Ghi chú</th>
          <th class="text-center">Thao tác</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($contacts as $c): ?>
          <tr>
            <td class="text-center">
              <?php if (!empty($c->avatar)): ?>
                <img src="<?= html_escape($c->avatar) ?>" alt="Avatar" class="avatar-img" style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%;">
              <?php else: ?>
                <span class="badge bg-secondary">No img</span>
              <?php endif; ?>
            </td>
            <td><?= html_escape($c->name) ?></td>
            <td><?= html_escape($c->phone) ?></td>
            <td><?= html_escape(date("d-m-Y", strtotime($c->created_at))) ?></td>
            <td><?= html_escape($c->notes) ?></td>
            <td class="d-flex justify-content-center align-items-center">
              <a href="/edit.php?id=<?= $c->id ?>" class="btn btn-xs btn-warning me-1">
                <i class="fa fa-pencil"></i> Edit
              </a>
              <form action="/delete.php" method="POST" class="d-inline">
                <input type="hidden" name="id" value="<?= $c->id ?>">
                <button type="submit" class="btn btn-xs btn-danger" name="delete-contact">
                  <i class="fa fa-trash"></i> Delete
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>

    <nav class="d-flex justify-content-center">
      <ul class="pagination">
        <li class="page-item <?= $paginator->getPrevPage() ? '' : 'disabled' ?>">
          <a class="page-link" href="/?page=<?= $paginator->getPrevPage() ?>&limit=5">&laquo;</a>
        </li>
        <?php foreach ($pages as $p): ?>
          <li class="page-item <?= $paginator->currentPage == $p ? 'active' : '' ?>">
            <a class="page-link" href="/?page=<?= $p ?>&limit=5"><?= $p ?></a>
          </li>
        <?php endforeach ?>
        <li class="page-item <?= $paginator->getNextPage() ? '' : 'disabled' ?>">
          <a class="page-link" href="/?page=<?= $paginator->getNextPage() ?>&limit=5">&raquo;</a>
        </li>
      </ul>
    </nav>
  </div>

  <div class="modal fade" id="delete-confirm" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Xác nhận xóa</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          Bạn có chắc chắn muốn xóa liên hệ này?
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
          <button type="button" class="btn btn-danger" id="delete">Xóa</button>
        </div>
      </div>
    </div>
  </div>

  <?php include_once __DIR__ . '/../src/partials/footer.php'; ?>

  <script>
    const deleteButtons = document.querySelectorAll('button[name="delete-contact"]');
    deleteButtons.forEach(button => {
      button.addEventListener('click', function(e) {
        e.preventDefault();
        const form = button.closest('form');
        const nameTd = button.closest('tr').querySelector('td:nth-child(2)');
        if (nameTd) {
          document.querySelector('#delete-confirm .modal-body').textContent =
            `Bạn có muốn xóa "${nameTd.textContent.trim()}"?`;
        }

        const submitForm = function() {
          form.submit();
        };

        document.getElementById('delete').addEventListener('click', submitForm, {
          once: true
        });

        const modalEl = document.getElementById('delete-confirm');
        modalEl.addEventListener('hidden.bs.modal', function() {
          document.getElementById('delete').removeEventListener('click', submitForm);
        });

        const confirmModal = new bootstrap.Modal(modalEl, {
          backdrop: 'static',
          keyboard: false
        });
        confirmModal.show();
      });
    });
  </script>
</body>

</html>