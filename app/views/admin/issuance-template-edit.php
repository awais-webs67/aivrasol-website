<?php /** @var ?array $t @var array $values @var array $errors */
$err = fn (string $k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
$inv = fn (string $k) => isset($errors[$k]) ? ' aria-invalid="true"' : '';
?>
<p><a class="link-arrow small" href="<?= e(url('admin/issuance/templates')) ?>">← Back to templates</a></p>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<form method="post" class="card form-grid cols-2">
  <?= csrf_field() ?>
  <div class="field"><label for="dt-name"><span>Template name *</span></label><input class="input" id="dt-name" name="name" value="<?= e($values['name']) ?>" maxlength="80"<?= $inv('name') ?>><?= $err('name') ?></div>
  <div class="field"><label for="dt-code"><span>Code for reference numbers *</span></label><input class="input" id="dt-code" name="code" value="<?= e($values['code']) ?>" maxlength="6"<?= $t && $t['is_builtin'] ? ' readonly' : '' ?><?= $inv('code') ?>><span class="small muted">2 to 6 letters. Reference numbers look like <?= e(Documents::codePrefix()) ?>-<?= e($values['code'] ?: 'PRO') ?>-<?= gmdate('Y') ?>-0001.</span><?= $err('code') ?></div>
  <div class="field"><label for="dt-kind"><span>Layout</span></label><select class="select" id="dt-kind" name="kind"><?php foreach (DOC_KINDS as $k => $l): ?><option value="<?= e($k) ?>"<?= $values['kind'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="dt-title"><span>Title printed on it *</span></label><input class="input" id="dt-title" name="title" value="<?= e($values['title']) ?>" maxlength="60"<?= $inv('title') ?>><?= $err('title') ?></div>
  <div class="field span-2"><label for="dt-sal"><span>Opening line</span></label><input class="input" id="dt-sal" name="salutation" value="<?= e($values['salutation']) ?>" maxlength="120"><span class="small muted">Certificates: shown above the name, for example "This certificate is presented to". Letters: for example "To whom it may concern". Leave empty for none.</span></div>
  <div class="field span-2"><label for="dt-body"><span>Text *</span></label><textarea class="textarea" id="dt-body" name="body" rows="9" maxlength="6000"<?= $inv('body') ?>><?= e($values['body']) ?></textarea>
    <span class="small muted">Placeholders: <code>{name}</code> <code>{designation}</code> <code>{department_part}</code> <code>{join_date}</code> <code>{end_date}</code> <code>{duration}</code> <code>{company}</code> <code>{employment_type}</code> <code>{id_part}</code> <code>{details}</code> and pronouns <code>{he}</code> <code>{him}</code> <code>{his}</code> (<code>{He}</code>, <code>{His}</code> at the start of a sentence). On certificates the text follows the name, so start with words like "has successfully completed".</span><?= $err('body') ?></div>
  <label class="check"><input type="checkbox" name="needs_end_date" value="1"<?= $values['needs_end_date'] ? ' checked' : '' ?>> <span>Ask for an end date when issuing</span></label>
  <div class="field"><label for="dt-det"><span>Ask for extra details (fills <code>{details}</code>)</span></label><input class="input" id="dt-det" name="details_label" value="<?= e($values['details_label']) ?>" maxlength="120" placeholder="For example: Course name"<?= $inv('details_label') ?>><?= $err('details_label') ?></div>
  <div class="field"><label for="dt-sort"><span>Order</span></label><input class="input" id="dt-sort" type="number" name="sort_order" value="<?= (int) $values['sort_order'] ?>"></div>
  <div class="span-2"><button class="btn" type="submit">Save template</button></div>
</form>
