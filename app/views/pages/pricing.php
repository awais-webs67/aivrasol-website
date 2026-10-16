<?php
/** @var array $plans @var ?array $selected @var string $q1 @var string $q2 @var array $faqs */
$current = $selected ?? ($plans[0] ?? null);
?>
<section class="page-hero">
  <div class="container page-hero-inner single">
    <div>
      <ol class="breadcrumb"><li><a href="<?= e(url('/')) ?>">Home</a></li><li aria-current="page">Pricing</li></ol>
      <h1 class="display-1">Simple pricing, free quotes</h1>
      <span class="bar"></span>
      <p class="lead">Choose how you want to work with us. Every quote is free, fixed and clear, with no hidden fees. The price you see is the price you pay.</p>
    </div>
  </div>
</section>

<?php $groups = ['project' => ['Build a project', 'Fixed-price projects with clear milestones. You own everything we build.'], 'hire' => ['Hire developers', 'Add vetted developers or a whole team to your company, full-time or by the hour.']]; ?>
<?php foreach ($groups as $cat => [$gTitle, $gText]): $group = array_values(array_filter($plans, fn ($p) => $p['category'] === $cat)); if (!$group) continue; ?>
<section class="section<?= $cat === 'hire' ? ' section-soft' : '' ?>" id="<?= $cat ?>-plans" aria-labelledby="<?= $cat ?>-plans-title">
  <div class="container">
    <div class="section-head center">
      <h2 class="display-2" id="<?= $cat ?>-plans-title"><?= e($gTitle) ?></h2>
      <span class="bar center"></span>
      <p class="lead"><?= e($gText) ?></p>
    </div>
    <?= partial('plans', ['plans' => $group]) ?>
  </div>
</section>
<?php endforeach; ?>

<?php if ($current): ?>
<section class="section section-grey" id="quote" aria-labelledby="quote-form-title">
  <div class="container container-narrow">
    <div class="section-head center">
      <h2 class="display-2" id="quote-form-title">Request your free quote</h2>
      <span class="bar center"></span>
      <p class="lead">Tell us what you need. We reply with a clear plan and price within one business day.</p>
    </div>
    <form action="<?= e(url('submit/quote')) ?>" method="post" data-ajax-form novalidate>
      <?= csrf_field() ?><?= bot_fields() ?>
      <input type="hidden" name="_back" value="/pricing#quote">
      <div class="joined">
        <div class="cell span-2">
          <label class="sr-only" for="qf-plan">Plan</label>
          <select class="select" id="qf-plan" name="plan_id" data-plan-select>
            <?php foreach ($plans as $p): ?><option value="<?= (int) $p['id'] ?>"<?= (int) $p['id'] === (int) $current['id'] ? ' selected' : '' ?>><?= e($p['title']) ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php foreach ($plans as $p): $active = (int) $p['id'] === (int) $current['id']; ?>
        <div class="cell" data-plan-fields="<?= (int) $p['id'] ?>"<?= $active ? '' : ' hidden' ?>>
          <label class="sr-only" for="qf-q1-<?= (int) $p['id'] ?>"><?= e($p['q1_label']) ?></label>
          <select class="select" id="qf-q1-<?= (int) $p['id'] ?>" name="q1"<?= $active ? '' : ' disabled' ?>>
            <option value=""><?= e($p['q1_label']) ?></option>
            <?php foreach (Content::planOptions($p, 'q1') as $o): ?><option<?= $active && $q1 === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="cell" data-plan-fields="<?= (int) $p['id'] ?>"<?= $active ? '' : ' hidden' ?>>
          <label class="sr-only" for="qf-q2-<?= (int) $p['id'] ?>"><?= e($p['q2_label']) ?></label>
          <select class="select" id="qf-q2-<?= (int) $p['id'] ?>" name="q2"<?= $active ? '' : ' disabled' ?>>
            <option value=""><?= e($p['q2_label']) ?></option>
            <?php foreach (Content::planOptions($p, 'q2') as $o): ?><option<?= $active && $q2 === $o ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php endforeach; ?>
        <div class="cell"><label class="sr-only" for="qf-name">Name</label><input class="input" id="qf-name" type="text" name="name" placeholder="Name *" autocomplete="name" required maxlength="100" value="<?= e(old('name')) ?>"></div>
        <div class="cell"><label class="sr-only" for="qf-email">Email</label><input class="input" id="qf-email" type="email" name="email" placeholder="Email address *" autocomplete="email" required maxlength="190" value="<?= e(old('email')) ?>"></div>
        <div class="cell"><label class="sr-only" for="qf-company">Company</label><input class="input" id="qf-company" type="text" name="company" placeholder="Company" autocomplete="organization" maxlength="120" value="<?= e(old('company')) ?>"></div>
        <div class="cell"><label class="sr-only" for="qf-phone">Phone</label><input class="input" id="qf-phone" type="tel" name="phone" placeholder="Phone or WhatsApp" autocomplete="tel" maxlength="40" value="<?= e(old('phone')) ?>"></div>
        <div class="cell span-2"><label class="sr-only" for="qf-msg">Project details</label><textarea class="textarea" id="qf-msg" name="message" placeholder="Tell us about your project (optional)" maxlength="3000"><?= e(old('message')) ?></textarea></div>
      </div>
      <div class="form-row">
        <p class="form-note">Free and with no obligation.</p>
        <button class="btn" type="submit">Get my free quote</button>
      </div>
    </form>
  </div>
</section>
<?php endif; ?>

<?= partial('faq', ['faqs' => $faqs, 'faqTitle' => 'Pricing questions']) ?>
