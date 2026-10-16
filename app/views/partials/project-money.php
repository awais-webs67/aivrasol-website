<?php /** @var array $p @var array $totals @var array $entries @var bool $staff */ $cur = $p['currency']; ?>
<div class="money-cards">
  <div class="money"><span>Agreed budget</span><strong><?= e(Portal::money($totals['budget'], $cur)) ?></strong></div>
  <div class="money"><span>Paid so far</span><strong class="is-good"><?= e(Portal::money($totals['paid'], $cur)) ?></strong></div>
  <div class="money"><span>Remaining budget</span><strong><?= e(Portal::money($totals['remaining'], $cur)) ?></strong></div>
  <div class="money"><span>Due now</span><strong class="<?= $totals['due'] > 0 ? 'is-due' : '' ?>"><?= e(Portal::money($totals['due'], $cur)) ?></strong></div>
</div>
<?php if ($totals['budget'] > 0): ?>
<div class="pay-bar" aria-hidden="true"><span class="w-<?= (int) (round($totals['percent'] / 5) * 5) ?>"></span></div>
<p class="small muted"><?= $totals['percent'] ?>% of the budget paid. Invoiced so far: <?= e(Portal::money($totals['invoiced'], $cur)) ?>.</p>
<?php endif; ?>
<?php if ($entries): ?>
<div class="table-wrap"><table class="table p-table">
  <thead><tr><th>Date</th><th>Item</th><th>Reference</th><th class="num">Amount</th><?= !empty($staff) ? '<th><span class="sr-only">Actions</span></th>' : '' ?></tr></thead>
  <tbody>
  <?php foreach ($entries as $t): ?>
  <tr>
    <td class="nowrap"><?= e(date('j M Y', strtotime($t['entry_date']))) ?></td>
    <td><span class="entry entry-<?= e($t['type']) ?>"><?= e(Portal::ENTRY_TYPES[$t['type']] ?? $t['type']) ?></span><?php if ($t['description'] !== ''): ?><br><span class="small"><?= e($t['description']) ?></span><?php endif; ?><?php if ($t['type'] === 'invoice' && $t['due_date']): ?><br><span class="small muted">Due <?= e(date('j M Y', strtotime($t['due_date']))) ?></span><?php endif; ?></td>
    <td class="small"><?= e($t['reference']) ?><?= $t['method'] !== '' ? '<br><span class="muted">' . e($t['method']) . '</span>' : '' ?></td>
    <td class="num nowrap"><?= e(Portal::money($t['type'] === 'refund' ? -$t['amount'] : $t['amount'], $cur)) ?></td>
    <?php if (!empty($staff)): ?><td><form method="post" action="<?= e(url('admin/projects/' . $p['id'] . '/entries/' . $t['id'] . '/delete')) ?>" data-confirm="Delete this entry?"><?= csrf_field() ?><button class="btn btn-sm btn-outline" type="submit">Delete</button></form></td><?php endif; ?>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php else: ?>
<p class="muted">No invoices or payments yet.</p>
<?php endif; ?>
