<?php /** @var array $rows @var array $f @var array $types @var array $templates */
$labels = array_column($templates, 'name', 'code');
$typeLabel = fn (string $t) => $t === 'experience' ? 'Experience certificate' : ($labels[strtoupper($t)] ?? ucfirst($t));
$issued = (int) ($_GET['issued'] ?? 0);
?>
<section class="card">
  <div class="card-head"><h2>Issue something new</h2><a class="link-arrow small" href="<?= e(url('admin/issuance/templates')) ?>">Manage templates <?= icon('arrow-right') ?></a></div>
  <div class="tpl-grid">
    <?php foreach ($templates as $t): ?>
    <a class="tpl-card" href="<?= e(url('admin/issuance/new?template=' . $t['id'])) ?>">
      <span class="tpl-ico tpl-<?= e($t['kind']) ?>"><?= icon($t['kind'] === 'certificate' ? 'award' : 'file') ?></span>
      <span><strong><?= e($t['name']) ?></strong><br><span class="small muted"><?= $t['kind'] === 'certificate' ? 'Certificate' : 'Letter' ?> · <?= e($t['code']) ?></span></span>
    </a>
    <?php endforeach; ?>
  </div>
</section>

<div class="list-head">
  <form class="filters-bar" method="get" action="<?= e(url('admin/issuance')) ?>">
    <label class="sr-only" for="is-q">Search</label>
    <input class="input" id="is-q" type="search" name="q" placeholder="Search reference, title or name" value="<?= e($f['q']) ?>">
    <label class="sr-only" for="is-type">Type</label>
    <select class="select" id="is-type" name="type"><option value="">All types</option><?php foreach ($types as $t): ?><option value="<?= e($t['doc_type']) ?>"<?= $f['type'] === $t['doc_type'] ? ' selected' : '' ?>><?= e($typeLabel($t['doc_type'])) ?> (<?= (int) $t['n'] ?>)</option><?php endforeach; ?></select>
    <label class="sr-only" for="is-state">Status</label>
    <select class="select" id="is-state" name="state"><option value="">Valid and withdrawn</option><option value="valid"<?= $f['state'] === 'valid' ? ' selected' : '' ?>>Valid</option><option value="withdrawn"<?= $f['state'] === 'withdrawn' ? ' selected' : '' ?>>Withdrawn</option></select>
    <button class="btn btn-sm" type="submit">Filter</button>
  </form>
</div>
<section class="card">
  <h2>Issued so far</h2>
  <?php if (!$rows): ?>
  <p class="muted">Nothing issued yet.</p>
  <?php else: ?>
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Reference</th><th>Person</th><th>Issued</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $d): ?>
    <tr<?= $issued === (int) $d['id'] ? ' class="is-new"' : '' ?>>
      <td><a href="<?= e(url('admin/letters/' . $d['id'] . '?view=1')) ?>" target="_blank" rel="noopener"><?= e($d['ref_no']) ?></a><br><span class="small muted"><?= e($d['title']) ?></span></td>
      <td><a href="<?= e(url('admin/employees/' . $d['employee_id'])) ?>"><?= e($d['full_name']) ?></a><br><span class="small muted"><?= e($d['employee_code']) ?></span></td>
      <td class="small"><?= e(date('j M Y', strtotime($d['issued_at']))) ?><?= $d['issuer'] ? '<br>by ' . e($d['issuer']) : '' ?></td>
      <td><span class="status status-<?= $d['revoked_at'] ? 'lost' : 'won' ?>"><?= $d['revoked_at'] ? 'Withdrawn' : 'Valid' ?></span></td>
      <td class="nowrap">
        <a class="btn btn-sm btn-outline" href="<?= e(url('admin/letters/' . $d['id'])) ?>"><?= icon('download') ?>PDF</a>
        <form method="post" action="<?= e(url('admin/letters/' . $d['id'] . '/revoke')) ?>" class="inline-form" data-confirm="<?= $d['revoked_at'] ? 'Make this valid again?' : 'Withdraw this document? Anyone checking it will see it is no longer valid.' ?>"><?= csrf_field() ?><input type="hidden" name="back" value="issuance"><button class="btn btn-sm btn-outline" type="submit"><?= $d['revoked_at'] ? 'Restore' : 'Withdraw' ?></button></form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
  <?= admin_pager($total, $per, $page) ?>
</section>
