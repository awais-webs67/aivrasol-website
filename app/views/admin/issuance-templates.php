<?php /** @var array $templates */ ?>
<p><a class="link-arrow small" href="<?= e(url('admin/issuance')) ?>">← Back to Issuance</a></p>
<div class="list-head"><p class="muted">Ready-made templates are marked "Built in". Edit any of them, or add your own.</p><a class="btn btn-sm" href="<?= e(url('admin/issuance/templates/new')) ?>"><?= icon('plus') ?>New template</a></div>
<section class="card">
  <div class="table-wrap"><table class="table">
    <thead><tr><th>Template</th><th>Type</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
    <tbody>
    <?php foreach ($templates as $t): ?>
    <tr>
      <td><a href="<?= e(url('admin/issuance/templates/' . $t['id'])) ?>"><?= e($t['name']) ?></a><br><span class="small muted"><?= e($t['code']) ?> · "<?= e($t['title']) ?>"<?= $t['is_builtin'] ? ' · Built in' : '' ?></span></td>
      <td class="small"><?= $t['kind'] === 'certificate' ? 'Certificate' : 'Letter' ?></td>
      <td><span class="status status-<?= $t['is_active'] ? 'won' : 'lost' ?>"><?= $t['is_active'] ? 'In use' : 'Hidden' ?></span></td>
      <td class="nowrap">
        <a class="btn btn-sm btn-outline" href="<?= e(url('admin/issuance/templates/' . $t['id'])) ?>">Edit</a>
        <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-outline" name="action" value="toggle" type="submit"><?= $t['is_active'] ? 'Hide' : 'Use again' ?></button></form>
        <?php if ($t['is_builtin']): ?>
        <form method="post" class="inline-form" data-confirm="Put back the original text of this template?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-outline" name="action" value="restore" type="submit">Restore original</button></form>
        <?php else: ?>
        <form method="post" class="inline-form" data-confirm="Delete this template? Documents already issued stay valid."><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><button class="btn btn-sm btn-danger" name="action" value="delete" type="submit">Delete</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
</section>
