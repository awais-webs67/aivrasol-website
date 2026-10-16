<div class="toolbar"><a class="btn btn-sm" href="<?= e(url('admin/content/' . $key . '/new')) ?>"><?= icon('plus') ?>Add <?= e($res['singular']) ?></a></div>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">Nothing here yet.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><?php foreach ($res['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <?php $first = true; foreach ($res['columns'] as $col => $label): $v = $r[$col]; ?>
    <td><?php if ($first): ?><a href="<?= e(url("admin/content/$key/{$r['id']}")) ?>"><?= e(excerpt((string) $v, 80)) ?></a><?php elseif (in_array($col, ['is_active', 'is_sample', 'is_highlighted', 'is_featured'], true)): ?><span class="status status-<?= $v ? 'won' : 'lost' ?>"><?= $v ? 'Yes' : 'No' ?></span><?php else: ?><?= e((string) $v) ?><?php endif; ?></td>
    <?php $first = false; endforeach; ?>
    <td class="nowrap"><a class="btn btn-sm btn-outline" href="<?= e(url("admin/content/$key/{$r['id']}")) ?>">Edit</a><?php if (isset($res['public']) && $r['is_active']): ?> <a class="btn btn-sm btn-outline" href="<?= e(url($res['public']($r))) ?>" target="_blank" rel="noopener" aria-label="View on website"><?= icon('eye') ?></a><?php endif; ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php endif; ?>
</div>
