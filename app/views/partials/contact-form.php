<?php
/** Joined contact form. Optional: $interest (preselected), $idPrefix */
$p = $idPrefix ?? 'cf';
$interest = $interest ?? old('interest');
$groups = [
    'Services' => array_column(Content::offerings('service'), 'short_title'),
    'Hire a developer' => array_column(Content::offerings('hire'), 'short_title'),
];
?>
<form action="<?= e(url('submit/contact')) ?>" method="post" data-ajax-form novalidate>
  <?= csrf_field() ?><?= bot_fields() ?>
  <input type="hidden" name="_back" value="<?= e(request_path()) ?>#<?= e($p) ?>">
  <div class="joined" id="<?= e($p) ?>">
    <div class="cell"><label class="sr-only" for="<?= e($p) ?>-company">Company</label><input class="input" id="<?= e($p) ?>-company" type="text" name="company" placeholder="Company" autocomplete="organization" maxlength="120" value="<?= e(old('company')) ?>"></div>
    <div class="cell"><label class="sr-only" for="<?= e($p) ?>-name">Name</label><input class="input" id="<?= e($p) ?>-name" type="text" name="name" placeholder="Name *" autocomplete="name" required maxlength="100" value="<?= e(old('name')) ?>"></div>
    <div class="cell"><label class="sr-only" for="<?= e($p) ?>-email">Email address</label><input class="input" id="<?= e($p) ?>-email" type="email" name="email" placeholder="Email address *" autocomplete="email" required maxlength="190" value="<?= e(old('email')) ?>"></div>
    <div class="cell"><label class="sr-only" for="<?= e($p) ?>-phone">Phone number</label><input class="input" id="<?= e($p) ?>-phone" type="tel" name="phone" placeholder="Phone or WhatsApp" autocomplete="tel" maxlength="40" value="<?= e(old('phone')) ?>"></div>
    <div class="cell span-2">
      <label class="sr-only" for="<?= e($p) ?>-interest">What do you need help with?</label>
      <select class="select" id="<?= e($p) ?>-interest" name="interest" data-has-other>
        <option value="">What do you need help with?</option>
        <?php foreach ($groups as $label => $options): ?>
        <optgroup label="<?= e($label) ?>">
          <?php foreach ($options as $o): ?><option value="<?= e($o) ?>"<?= $interest === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
        </optgroup>
        <?php endforeach; ?>
        <option value="General question"<?= $interest === 'General question' ? ' selected' : '' ?>>General question</option>
        <option value="Other"<?= $interest === 'Other' ? ' selected' : '' ?>>Other</option>
      </select>
      <?= other_input('interest', old('interest_other')) ?>
    </div>
    <div class="cell span-2"><label class="sr-only" for="<?= e($p) ?>-message">Your message</label><textarea class="textarea" id="<?= e($p) ?>-message" name="message" placeholder="Your message *" required minlength="10" maxlength="5000"><?= e(old('message')) ?></textarea></div>
  </div>
  <div class="form-row">
    <label class="check"><input type="checkbox" name="terms" value="1" required> <span>I agree to the <a href="<?= e(url('privacy-policy')) ?>">privacy policy</a>.</span></label>
    <button class="btn" type="submit">Send message</button>
  </div>
</form>
