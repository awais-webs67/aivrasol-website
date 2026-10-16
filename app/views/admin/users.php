<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<section class="card">
  <div class="card-head"><h2>Team</h2><span class="small muted"><?= count($rows) ?> accounts</span></div>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Name</th><th>Role</th><th>Last sign in</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): $self = (int) $r['id'] === (int) $user['id']; ?>
    <tr>
      <td><?= e($r['name']) ?><?= $self ? ' <span class="small muted">(you)</span>' : '' ?><br><span class="small muted"><?= e($r['email']) ?></span></td>
      <td>
        <?php if ($self): ?><?= e(Auth::roleLabel($r['role'])) ?>
        <?php else: ?>
        <form method="post" class="inline-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
          <label class="sr-only" for="role-<?= (int) $r['id'] ?>">Role for <?= e($r['name']) ?></label>
          <select class="select select-sm" id="role-<?= (int) $r['id'] ?>" name="role"><?php foreach (Auth::ROLES as $k => $ro): ?><option value="<?= e($k) ?>"<?= $r['role'] === $k ? ' selected' : '' ?>><?= e($ro['label']) ?></option><?php endforeach; ?></select>
          <button class="btn btn-sm btn-outline" type="submit">Save</button>
        </form>
        <?php endif; ?>
      </td>
      <td class="small nowrap"><?= $r['last_login_at'] ? e(date('j M Y, H:i', strtotime($r['last_login_at'] . ' UTC'))) : 'Never' ?></td>
      <td><span class="status status-<?= $r['is_active'] ? 'won' : 'lost' ?>"><?= $r['is_active'] ? 'Active' : 'Disabled' ?></span><?php if ($r['must_change_password'] && $r['is_active']): ?><br><span class="small muted">Temporary password</span><?php endif; ?></td>
      <td>
        <?php if (!$self): ?>
        <details class="row-menu">
          <summary class="btn btn-sm btn-outline">Manage</summary>
          <div class="row-menu-body">
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="link"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-sm btn-block btn-outline" type="submit"><?= icon('mail') ?>Email a reset link</button></form>
            <form method="post" class="form-grid">
              <?= csrf_field() ?><input type="hidden" name="action" value="password"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <label class="field"><span>Set a temporary password</span><input class="input" type="password" name="password" required minlength="10" maxlength="200" autocomplete="new-password"></label>
              <button class="btn btn-sm btn-block btn-outline" type="submit">Set password</button>
            </form>
            <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><button class="btn btn-sm btn-block <?= $r['is_active'] ? 'btn-danger' : 'btn-outline' ?>" type="submit"><?= $r['is_active'] ? 'Disable account' : 'Enable account' ?></button></form>
          </div>
        </details>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
<div class="admin-grid">
  <section class="card">
    <h2>Add a team member</h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="create">
      <label class="field"><span>Name</span><input class="input" type="text" name="name" required maxlength="80" value="<?= e((string) ($_POST['name'] ?? '')) ?>"></label>
      <label class="field"><span>Email</span><input class="input" type="email" name="email" required maxlength="190" autocomplete="off" value="<?= e((string) ($_POST['email'] ?? '')) ?>"></label>
      <label class="field"><span>Role</span><select class="select" name="role"><?php foreach (Auth::ROLES as $k => $ro): ?><option value="<?= e($k) ?>"<?= ($_POST['role'] ?? 'editor') === $k ? ' selected' : '' ?>><?= e($ro['label']) ?></option><?php endforeach; ?></select></label>
      <label class="check"><input type="checkbox" name="invite" value="1" checked><span>Email them a link to set their own password (works for 48 hours)</span></label>
      <label class="field"><span>Or a temporary password <span class="small muted">(optional, they must change it at first sign in)</span></span><input class="input" type="password" name="password" minlength="10" maxlength="200" autocomplete="new-password"></label>
      <button class="btn" type="submit">Add team member</button>
    </form>
  </section>
  <section class="card">
    <h2>What each role can open</h2>
    <dl class="dl">
      <?php foreach (Auth::ROLES as $ro): ?><dt><?= e($ro['label']) ?></dt><dd><?= e($ro['about']) ?></dd><?php endforeach; ?>
    </dl>
  </section>
</div>
<section class="card">
  <h2>Recent activity</h2>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>When (UTC)</th><th>Who</th><th>Action</th><th>Target</th></tr></thead>
    <tbody><?php foreach ($audit as $a): ?><tr><td class="small nowrap"><?= e($a['created_at']) ?></td><td><?= e($a['name'] ?? 'Unknown') ?></td><td><?= e(str_replace('_', ' ', $a['action'])) ?></td><td><?= e($a['target']) ?></td></tr><?php endforeach; ?></tbody>
  </table></div>
</section>
