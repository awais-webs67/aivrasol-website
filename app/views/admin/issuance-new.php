<?php /** @var array $people @var array $templates @var ?array $emp @var ?array $tpl @var string $end @var string $details @var int $step @var string $title @var string $body @var array $errors @var bool $notify @var bool $ready */ ?>
<p><a class="link-arrow small" href="<?= e(url('admin/issuance')) ?>">← Back to Issuance</a></p>
<?php if (!$ready): ?><p class="form-status is-error">Add the signatory name and signature in <a href="<?= e(url('admin/documents')) ?>">Document design</a> first, so documents are signed.</p><?php endif; ?>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<form method="post" class="card form-grid cols-2">
  <?= csrf_field() ?>
  <h2 class="span-2">1. Who and what</h2>
  <div class="field">
    <label for="is-emp"><span>Person *</span></label>
    <select class="select" id="is-emp" name="employee" required>
      <option value="">Choose a person...</option>
      <?php foreach ($people as $p): ?><option value="<?= (int) $p['id'] ?>"<?= $emp && (int) $emp['id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e($p['full_name'] . ' · ' . $p['designation'] . ' · ' . $p['employee_code']) ?><?= $p['status'] !== 'active' ? ' (' . e(Documents::STATUSES[$p['status']] ?? $p['status']) . ')' : '' ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field">
    <label for="is-tpl"><span>Certificate or letter *</span></label>
    <select class="select" id="is-tpl" name="template" required>
      <option value="">Choose...</option>
      <?php foreach ($templates as $t): ?><option value="<?= (int) $t['id'] ?>"<?= $tpl && (int) $tpl['id'] === (int) $t['id'] ? ' selected' : '' ?>><?= e($t['name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <?php if (!$tpl || $tpl['needs_end_date']): ?>
  <div class="field"><label for="is-end"><span>End date (last working day or internship end)<?= $tpl ? ' *' : '' ?></span></label><input class="input" id="is-end" type="date" name="end_date" value="<?= e($end) ?>"><?php if (!$tpl): ?><span class="small muted">Only used by templates that need it.</span><?php endif; ?></div>
  <?php endif; ?>
  <?php if (!$tpl || $tpl['details_label'] !== ''): ?>
  <div class="field"><label for="is-det"><span><?= e($tpl['details_label'] ?? 'Details (if the template asks for them)') ?><?= $tpl ? ' *' : '' ?></span></label><input class="input" id="is-det" name="details" value="<?= e($details) ?>" maxlength="200"></div>
  <?php endif; ?>
  <div class="span-2"><button class="btn<?= $step === 2 ? ' btn-outline' : '' ?>" type="submit" name="action" value="prepare"><?= $step === 2 ? 'Prepare the text again' : 'Prepare the text' ?></button></div>
</form>

<?php if ($step === 2): ?>
<form method="post" class="card form-grid">
  <?= csrf_field() ?>
  <input type="hidden" name="employee" value="<?= (int) $emp['id'] ?>"><input type="hidden" name="template" value="<?= (int) $tpl['id'] ?>"><input type="hidden" name="end_date" value="<?= e($end) ?>"><input type="hidden" name="details" value="<?= e($details) ?>">
  <h2>2. Check the wording</h2>
  <p class="small muted">Filled in from the "<?= e($tpl['name']) ?>" template. Change anything for this person before issuing. A blank line starts a new paragraph.</p>
  <label class="field"><span>Title printed on the document</span><input class="input" name="title" value="<?= e($title) ?>" maxlength="60" required></label>
  <?php if ($tpl['kind'] === 'certificate'): ?><p class="cert-preview-line small"><em><?= e($tpl['salutation']) ?></em> <strong><?= e($emp['full_name']) ?></strong></p><?php endif; ?>
  <label class="field"><span>Text</span><textarea class="textarea" name="body" rows="9" maxlength="6000" required><?= e($body) ?></textarea></label>
  <label class="check"><input type="checkbox" name="notify" value="1"<?= $notify ? ' checked' : '' ?>> <span>Tell <?= e(strtok($emp['full_name'], ' ')) ?> by email and in their messages</span></label>
  <div class="btn-group"><button class="btn" type="submit" name="action" value="issue"><?= icon('award') ?>Issue <?= $tpl['kind'] === 'certificate' ? 'certificate' : 'letter' ?></button><span class="small muted">You get a reference number, a PDF with a QR code, and it appears in their profile.</span></div>
</form>
<?php endif; ?>
