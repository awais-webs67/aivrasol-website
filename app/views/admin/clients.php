<div class="list-head">
  <form class="filters-bar" method="get" action="<?= e(url('admin/clients')) ?>">
    <label class="sr-only" for="cf-q">Search</label>
    <input class="input" id="cf-q" type="search" name="q" placeholder="Search company, name or email" value="<?= e($q) ?>">
    <button class="btn btn-sm" type="submit">Search</button>
  </form>
  <a class="btn btn-sm" href="<?= e(url('admin/clients/new')) ?>"><?= icon('plus') ?>Add client</a>
</div>
<div class="card">
<?php if (!$rows): ?>
<p class="muted"><?= $q !== '' ? 'No clients match your search.' : 'No clients yet. Add one, or open a won lead and choose "Create client".' ?></p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Client</th><th>Projects</th><th>Portal</th><th>Last sign in</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><a href="<?= e(url('admin/clients/' . $r['id'])) ?>"><?= e($r['company_name'] !== '' ? $r['company_name'] : $r['contact_name']) ?></a><br><span class="small muted"><?= e($r['contact_name']) ?> · <?= e($r['email']) ?></span></td>
    <td><?= (int) $r['projects'] ?></td>
    <td><span class="status status-<?= $r['is_active'] ? 'won' : 'lost' ?>"><?= $r['is_active'] ? 'On' : 'Off' ?></span></td>
    <td class="small nowrap"><?= $r['last_login_at'] ? e(date('j M Y', strtotime($r['last_login_at'] . ' UTC'))) : 'Never' ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
