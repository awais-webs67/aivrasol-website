<?php $back = '?' . http_build_query(array_filter(['status' => $status, 'tag' => $tag, 'q' => $q, 'page' => $page > 1 ? $page : null])); ?>
<div class="stat-cards">
  <?php foreach (Campaigns::CONTACT_STATUSES as $k => $v): ?><a class="stat-card" href="<?= e(url('admin/contacts?status=' . $k)) ?>"><span><?= e($v) ?></span><strong><?= (int) ($counts[$k] ?? 0) ?></strong></a><?php endforeach; ?>
</div>
<form class="filters-bar" method="get" action="<?= e(url('admin/contacts')) ?>">
  <label class="sr-only" for="ct-q">Search</label>
  <input class="input" id="ct-q" type="search" name="q" placeholder="Search email, name or company" value="<?= e($q) ?>">
  <label class="sr-only" for="ct-status">Status</label>
  <select class="select" id="ct-status" name="status"><option value="">All statuses</option><?php foreach (Campaigns::CONTACT_STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select>
  <label class="sr-only" for="ct-tag">Tag</label>
  <select class="select" id="ct-tag" name="tag"><option value="">All tags</option><?php foreach ($tags as $t => $n): ?><option value="<?= e($t) ?>"<?= $tag === $t ? ' selected' : '' ?>><?= e($t) ?> (<?= (int) $n ?>)</option><?php endforeach; ?></select>
  <button class="btn btn-sm" type="submit">Filter</button>
</form>
<p class="small muted"><?= $total ?> contact<?= $total === 1 ? '' : 's' ?> found</p>
<div class="card">
<?php if (!$rows): ?>
<p class="muted">No contacts yet. Import a CSV, add website leads, or add people one by one below.</p>
<?php else: ?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Contact</th><th>Tags</th><th>Status</th><th>Last emailed</th><th><span class="sr-only">Actions</span></th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): ?>
  <tr>
    <td><?= e(trim($r['first_name'] . ' ' . $r['last_name']) ?: '-') ?><?= $r['company'] !== '' ? ', ' . e($r['company']) : '' ?><br><span class="small muted"><?= e($r['email']) ?></span></td>
    <td class="small"><?= e(str_replace(',', ', ', $r['tags'])) ?></td>
    <td><span class="status status-<?= $r['status'] === 'subscribed' ? 'won' : 'lost' ?>"><?= e(Campaigns::CONTACT_STATUSES[$r['status']] ?? $r['status']) ?></span></td>
    <td class="small nowrap"><?= $r['last_sent_at'] ? e(date('j M Y', strtotime($r['last_sent_at'] . ' UTC'))) : 'Never' ?></td>
    <td class="nowrap">
      <form method="post" class="inline-form"><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="<?= $r['status'] === 'subscribed' ? 'unsubscribe' : 'resubscribe' ?>"><button class="btn btn-sm btn-outline" type="submit"<?= in_array($r['status'], ['bounced', 'complained'], true) ? ' disabled' : '' ?>><?= $r['status'] === 'subscribed' ? 'Unsubscribe' : 'Subscribe' ?></button></form>
      <form method="post" class="inline-form" data-confirm="Delete this contact?"><?= csrf_field() ?><input type="hidden" name="back" value="<?= e($back) ?>"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-sm btn-outline" type="submit" aria-label="Delete <?= e($r['email']) ?>"><?= icon('trash') ?></button></form>
    </td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
<?php if ($total > $per): ?>
<nav class="pager" aria-label="Pages"><?php for ($i = 1; $i <= (int) ceil($total / $per); $i++): ?><a href="<?= e(url('admin/contacts') . '?' . http_build_query(array_filter(['status' => $status, 'tag' => $tag, 'q' => $q, 'page' => $i]))) ?>"<?= $i === $page ? ' aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?></nav>
<?php endif; ?>
<?php endif; ?>
</div>
<div class="admin-grid">
  <section class="card">
    <h2>Import from a CSV file</h2>
    <form method="post" enctype="multipart/form-data" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="import">
      <label class="field"><span>CSV file (first row: column names)</span><input class="file-input" type="file" name="csv" accept=".csv,text/csv" required></label>
      <p class="small muted">Columns we read: email (required), first_name, last_name, company, website, country, tags. A single "name" column also works. People who unsubscribed or bounced are never re-subscribed by an import.</p>
      <label class="field"><span>Add these tags to everyone imported</span><input class="input" name="import_tags" maxlength="120" placeholder="e.g. uk-retail, oct-2026"></label>
      <label class="check"><input type="checkbox" name="consent" value="1" required> <span>I confirm we have a lawful reason to email these people (for example a business contact with a clear interest), and every email will include an unsubscribe link.</span></label>
      <button class="btn btn-sm" type="submit">Import contacts</button>
    </form>
  </section>
  <section class="card">
    <h2>Add a contact</h2>
    <form method="post" class="form-grid cols-2">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <label class="field span-2"><span>Email *</span><input class="input" type="email" name="email" required maxlength="190"></label>
      <label class="field"><span>First name</span><input class="input" name="first_name" maxlength="80"></label>
      <label class="field"><span>Last name</span><input class="input" name="last_name" maxlength="80"></label>
      <label class="field"><span>Company</span><input class="input" name="company" maxlength="120"></label>
      <label class="field"><span>Website</span><input class="input" name="website" maxlength="190"></label>
      <label class="field"><span>Country</span><input class="input" name="country" maxlength="60"></label>
      <label class="field"><span>Tags</span><input class="input" name="tags" maxlength="120" placeholder="comma separated"></label>
      <div class="span-2"><button class="btn btn-sm" type="submit">Add contact</button></div>
    </form>
    <form method="post" class="mt"><?= csrf_field() ?><input type="hidden" name="action" value="leads"><button class="btn btn-sm btn-outline" type="submit">Add all website leads as contacts</button></form>
  </section>
</div>
