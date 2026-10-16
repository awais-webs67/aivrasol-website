<?php
/** Job application form. Optional: $job (array) */
$jobSlug = $job['slug'] ?? 'general';
$back = isset($job) ? '/careers/' . $job['slug'] . '#apply' : '/careers#apply';
?>
<form action="<?= e(url('submit/apply')) ?>" method="post" enctype="multipart/form-data" data-ajax-form novalidate>
  <?= csrf_field() ?><?= bot_fields() ?>
  <input type="hidden" name="_back" value="<?= e($back) ?>">
  <input type="hidden" name="job" value="<?= e($jobSlug) ?>">
  <input type="hidden" name="MAX_FILE_SIZE" value="5242880">
  <div class="form-grid cols-2">
    <label class="field"><span>Full name *</span><input class="input" type="text" name="name" autocomplete="name" required maxlength="100" value="<?= e(old('name')) ?>"></label>
    <label class="field"><span>Email *</span><input class="input" type="email" name="email" autocomplete="email" required maxlength="190" value="<?= e(old('email')) ?>"></label>
    <label class="field"><span>Phone or WhatsApp *</span><input class="input" type="tel" name="phone" autocomplete="tel" required maxlength="40" value="<?= e(old('phone')) ?>"></label>
    <label class="field"><span>LinkedIn, GitHub or portfolio</span><input class="input" type="url" name="profile_url" placeholder="https://" maxlength="255" value="<?= e(old('profile_url')) ?>"></label>
    <label class="field span-2"><span>CV (PDF, DOC or DOCX, up to 5 MB) *</span><input class="file-input" type="file" name="cv" accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required></label>
    <label class="field span-2"><span>Short cover note</span><textarea class="textarea" name="message" maxlength="3000" placeholder="Tell us why you would like to join us"><?= e(old('message')) ?></textarea></label>
  </div>
  <div class="form-row">
    <label class="check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the <a href="<?= e(url('privacy-policy')) ?>">privacy policy</a> and to AivraSol storing my CV for recruitment.</span></label>
    <button class="btn" type="submit">Submit application</button>
  </div>
</form>
