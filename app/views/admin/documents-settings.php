<?php
$sig = setting('doc_signature') !== '' && Upload::privatePath(setting('doc_signature'));
$stamp = setting('doc_stamp') !== '' && Upload::privatePath(setting('doc_stamp'));
$v = fn (string $k, string $d = '') => (string) ($_POST[$k] ?? setting($k, $d));
$err = fn (string $k) => isset($errors[$k]) ? '<span class="field-error">' . e($errors[$k]) . '</span>' : '';
?>
<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<p class="btn-group">
  <a class="btn btn-sm btn-outline" href="<?= e(url('admin/documents/sample/letter')) ?>" target="_blank" rel="noopener"><?= icon('file') ?>Preview a sample letter</a>
  <a class="btn btn-sm btn-outline" href="<?= e(url('admin/documents/sample/card')) ?>" target="_blank" rel="noopener"><?= icon('user') ?>Preview a sample ID card</a>
</p>
<form method="post" enctype="multipart/form-data" class="settings-form">
  <?= csrf_field() ?>
  <fieldset class="card form-grid cols-2">
    <legend>Who signs</legend>
    <label class="field"><span>Signatory name</span><input class="input" type="text" name="doc_signatory_name" value="<?= e($v('doc_signatory_name')) ?>" maxlength="80" placeholder="e.g. Muhammad Awais"><?= $err('doc_signatory_name') ?></label>
    <label class="field"><span>Signatory title</span><input class="input" type="text" name="doc_signatory_title" value="<?= e($v('doc_signatory_title')) ?>" maxlength="80" placeholder="e.g. Chief Executive Officer"><?= $err('doc_signatory_title') ?></label>
    <div class="field">
      <label for="d-sig"><span>Signature image</span></label>
      <?php if ($sig): ?><img class="sig-preview" src="<?= e(url('admin/documents/image/signature')) ?>" alt="Current signature" width="240" height="90"><label class="check"><input type="checkbox" name="doc_signature_remove" value="1"> <span>Remove signature</span></label><?php endif; ?>
      <input class="file-input" id="d-sig" type="file" name="doc_signature" accept="image/png,image/jpeg,image/webp">
      <span class="small muted">Sign on white paper in dark ink, then photograph or scan it. A PNG with a transparent background looks best. Changing it updates new ID cards and letters; letters already issued keep the signature they were signed with.</span>
      <?= $err('doc_signature') ?>
    </div>
    <div class="field">
      <label for="d-stamp"><span>Company stamp (optional)</span></label>
      <?php if ($stamp): ?><img class="sig-preview" src="<?= e(url('admin/documents/image/stamp')) ?>" alt="Current stamp" width="120" height="120"><label class="check"><input type="checkbox" name="doc_stamp_remove" value="1"> <span>Remove stamp</span></label><?php endif; ?>
      <input class="file-input" id="d-stamp" type="file" name="doc_stamp" accept="image/png,image/jpeg,image/webp">
      <span class="small muted">Round company seal, PNG with a transparent background.</span>
      <?= $err('doc_stamp') ?>
    </div>
  </fieldset>
  <fieldset class="card form-grid cols-2">
    <legend>Experience letter template</legend>
    <div class="field span-2">
      <label for="d-tpl"><span>Letter text</span></label>
      <textarea class="textarea editor" id="d-tpl" name="doc_letter_template" maxlength="6000"><?= e($v('doc_letter_template', Documents::DEFAULT_LETTER)) ?></textarea>
      <span class="small muted">Placeholders: {name}, {designation}, {department}, {department_part}, {join_date}, {end_date}, {duration}, {employment_type}, {employee_code}, {company}, {father_name}, {national_id}, {id_part}, {he}, {He}, {him}, {his}, {His}. Pronouns follow the setting on each employee.</span>
      <?= $err('doc_letter_template') ?>
    </div>
  </fieldset>
  <fieldset class="card form-grid cols-2">
    <legend>ID cards and numbering</legend>
    <label class="field"><span>Code prefix</span><input class="input" type="text" name="doc_code_prefix" value="<?= e($v('doc_code_prefix', 'AVS')) ?>" maxlength="5"><span class="small muted">Used for new employee IDs and letter numbers, e.g. AVS-0012.</span><?= $err('doc_code_prefix') ?></label>
    <label class="field"><span>ID card valid for (years)</span><input class="input" type="number" name="doc_card_valid_years" value="<?= e($v('doc_card_valid_years', '2')) ?>" min="1" max="5"></label>
    <label class="field"><span>Company registration number (optional)</span><input class="input" type="text" name="doc_company_reg" value="<?= e($v('doc_company_reg')) ?>" maxlength="60"><span class="small muted">Shown in the letter footer.</span></label>
    <div class="field span-2">
      <label for="d-back"><span>ID card back text</span></label>
      <textarea class="textarea" id="d-back" name="doc_card_back" maxlength="300"><?= e($v('doc_card_back', Documents::DEFAULT_CARD_BACK)) ?></textarea>
      <span class="small muted">Placeholders: {company}, {email}.</span>
      <?= $err('doc_card_back') ?>
    </div>
  </fieldset>
  <div class="sticky-save"><button class="btn" type="submit">Save</button></div>
</form>
