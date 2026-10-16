<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<div class="admin-grid">
  <section class="card">
    <h2>Team</h2>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Name</th><th>Role</th><th>Last sign in</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= e($r['name']) ?><br><span class="small muted"><?= e($r['email']) ?></span></td>
        <td><?= e(ucfirst($r['role'])) ?></td>
        <td class="small nowrap"><?= $r['last_login_at'] ? e(date('j M Y, H:i', strtotime($r['last_login_at'] . ' UTC'))) : 'Never' ?></td>
        <td><span class="status status-<?= $r['is_active'] ? 'won' : 'lost' ?>"><?= $r['is_active'] ? 'Active' : 'Disabled' ?></span></td>
        <td><?php if ((int) $r['id'] !== (int) $user['id']): ?><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-sm btn-outline" type="submit"><?= $r['is_active'] ? 'Disable' : 'Enable' ?></button></form><?php endif; ?></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
  <section class="card">
    <h2>Add a user</h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <label class="field"><span>Name</span><input class="input" type="text" name="name" required maxlength="80"></label>
      <label class="field"><span>Email</span><input class="input" type="email" name="email" required maxlength="190" autocomplete="off"></label>
      <label class="field"><span>Temporary password</span><input class="input" type="password" name="password" required minlength="10" autocomplete="new-password"></label>
      <label class="field"><span>Role</span><select class="select" name="role"><option value="editor">Editor (content and leads)</option><option value="admin">Admin (everything)</option></select></label>
      <button class="btn" type="submit">Add user</button>
    </form>
  </section>
</div>
<section class="card">
  <h2>Recent activity</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>When (UTC)</th><th>Who</th><th>Action</th><th>Target</th></tr></thead>
    <tbody><?php foreach ($audit as $a): ?><tr><td class="small nowrap"><?= e($a['created_at']) ?></td><td><?= e($a['name'] ?? 'Unknown') ?></td><td><?= e(str_replace('_', ' ', $a['action'])) ?></td><td><?= e($a['target']) ?></td></tr><?php endforeach; ?></tbody>
  </table></div>
</section>
