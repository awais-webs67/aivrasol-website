<p><a class="link-arrow small" href="<?= e(url('admin/content/' . $key)) ?>">← Back to <?= e(strtolower($res['label'])) ?></a></p>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<form method="post" enctype="multipart/form-data" class="card form-grid cols-2 edit-form">
  <?= csrf_field() ?>
  <?php foreach ($res['fields'] as $name => $f): $val = (string) ($values[$name] ?? ''); $err = $errors[$name] ?? null; $id = 'f-' . $name; $wide = in_array($f['type'], ['textarea', 'lines', 'image', 'markdown'], true); ?>
  <?php if ($f['type'] === 'bool'): ?>
  <label class="check span-2"><input type="checkbox" name="<?= e($name) ?>" value="1"<?= $val ? ' checked' : '' ?>> <span><?= e($f['label']) ?></span></label>
  <?php else: ?>
  <div class="field<?= $wide ? ' span-2' : '' ?>">
    <label for="<?= e($id) ?>"><span><?= e($f['label']) ?><?= !empty($f['required']) ? ' *' : '' ?></span></label>
    <?php if ($f['type'] === 'select'): ?>
    <select class="select" id="<?= e($id) ?>" name="<?= e($name) ?>"<?= $err ? ' aria-invalid="true"' : '' ?>><?php foreach ($f['options'] as $ov => $ol): ?><option value="<?= e($ov) ?>"<?= $val === (string) $ov ? ' selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?></select>
    <?php elseif (in_array($f['type'], ['textarea', 'lines', 'markdown'], true)): ?>
    <textarea class="textarea<?= $f['type'] === 'lines' ? ' tall' : ($f['type'] === 'markdown' ? ' editor' : '') ?>" id="<?= e($id) ?>" name="<?= e($name) ?>"<?= isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '' ?><?= $err ? ' aria-invalid="true"' : '' ?>><?= e($val) ?></textarea>
    <?php elseif ($f['type'] === 'image'): ?>
    <?php if ($val !== ''): ?><img class="thumb" src="<?= e(url($val)) ?>" alt="" width="240" height="150"><label class="check"><input type="checkbox" name="<?= e($name) ?>_remove" value="1"> <span>Remove this image</span></label><?php endif; ?>
    <input class="file-input" id="<?= e($id) ?>" type="file" name="<?= e($name) ?>" accept="image/jpeg,image/png,image/webp">
    <?php else: ?>
    <input class="input" id="<?= e($id) ?>" type="<?= ['int' => 'number', 'url' => 'url', 'date' => 'date'][$f['type']] ?? 'text' ?>" name="<?= e($name) ?>" value="<?= e($val) ?>"<?= isset($f['max']) ? ' maxlength="' . (int) $f['max'] . '"' : '' ?><?= $err ? ' aria-invalid="true"' : '' ?>>
    <?php endif; ?>
    <?php if (!empty($f['help'])): ?><span class="small muted"><?= e($f['help']) ?></span><?php endif; ?>
    <?php if ($err): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>
  <div class="span-2 btn-group"><button class="btn" type="submit">Save <?= e($res['singular']) ?></button><?php if ($row && isset($res['public'])): ?><a class="btn btn-outline" href="<?= e(url($res['public']($row))) ?>" target="_blank" rel="noopener">View on website</a><?php endif; ?></div>
</form>
<?php if ($row): ?>
<form method="post" class="danger-zone" data-confirm="Delete this <?= e($res['singular']) ?> for good?">
  <?= csrf_field() ?><input type="hidden" name="action" value="delete">
  <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete <?= e($res['singular']) ?></button>
</form>
<?php endif; ?>
