<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/includes/init.php';
$admin = AdminAuth::requireAuth('categories.manage');
$adminTitle = 'Category Management';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Security::requireCsrf();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $platform = (string) ($_POST['platform'] ?? 'other');
        $sort = (int) ($_POST['sort_order'] ?? 0);
        $active = !empty($_POST['is_active']) ? 1 : 0;
        if ($name !== '') {
            if ($id > 0) {
                Database::execute('UPDATE categories SET name = ?, platform = ?, icon = ?, sort_order = ?, is_active = ? WHERE id = ?', [$name, $platform, $platform, $sort, $active, $id]);
                Session::flash('success', 'Category updated.');
            } else {
                Database::execute('INSERT INTO categories (name, slug, platform, icon, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)', [$name, CategoryManager::slugify($name), $platform, $platform, $sort, $active]);
                Session::flash('success', 'Category created.');
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $cnt = (int) Database::fetchValue('SELECT COUNT(*) FROM services WHERE category_id = ?', [$id], 0);
        if ($cnt > 0) {
            Session::flash('error', 'Cannot delete category with existing services.');
        } else {
            Database::execute('DELETE FROM categories WHERE id = ?', [$id]);
            Session::flash('success', 'Category deleted.');
        }
    }
    Response::redirect('/admin/categories/index.php');
}

$cats = Database::fetchAll('SELECT c.*, (SELECT COUNT(*) FROM services s WHERE s.category_id = c.id) AS service_count FROM categories c ORDER BY c.sort_order ASC, c.id ASC');
require dirname(__DIR__) . '/layouts/header.php';
?>
<div class="adm-card">
  <h2 style="margin:0 0 12px;font-size:16px">Add Category</h2>
  <form method="post" class="grid-3">
    <?= csrf_field() ?><input type="hidden" name="action" value="save">
    <input class="form-control" name="name" placeholder="Category Name (e.g. Instagram Followers)" required>
    <select class="form-select" name="platform"><?php foreach (SUPPORTED_PLATFORMS as $k => $lbl): ?><option value="<?= e($k) ?>"><?= e($lbl) ?></option><?php endforeach; ?></select>
    <div style="display:flex;gap:8px;align-items:center">
      <input class="form-control" type="number" name="sort_order" value="0" style="max-width:90px" placeholder="Sort">
      <label style="font-size:13px"><input type="checkbox" name="is_active" value="1" checked> Active</label>
      <button class="btn btn-primary" type="submit">+ Add</button>
    </div>
  </form>
</div>

<div class="adm-card">
  <div class="table-responsive">
    <table class="adm-table">
      <thead><tr><th>ID</th><th>Icon</th><th>Name</th><th>Platform</th><th>Services</th><th>Sort</th><th>Status</th><th>Save / Delete</th></tr></thead>
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td>#<?= (int) $c['id'] ?></td>
          <td><?= platform_icon_svg((string) $c['platform'], 24) ?></td>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <td><input class="form-control" name="name" value="<?= e($c['name']) ?>"></td>
            <td><select class="form-select" name="platform"><?php foreach (SUPPORTED_PLATFORMS as $k => $lbl): ?><option value="<?= e($k) ?>" <?= $c['platform']===$k?'selected':'' ?>><?= e($lbl) ?></option><?php endforeach; ?></select></td>
            <td><?= (int) $c['service_count'] ?></td>
            <td><input class="form-control" type="number" name="sort_order" value="<?= (int) $c['sort_order'] ?>" style="max-width:75px"></td>
            <td><label><input type="checkbox" name="is_active" value="1" <?= $c['is_active']?'checked':'' ?>> Active</label></td>
            <td style="white-space:nowrap"><button class="btn btn-primary btn-sm" type="submit">Save</button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Delete category?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $c['id'] ?>"><button class="btn btn-danger btn-sm" type="submit">Del</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
