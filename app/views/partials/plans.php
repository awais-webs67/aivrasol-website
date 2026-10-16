<?php /** @var array $plans  Optional: $preselect (option to select first) */ $preselect = $preselect ?? ''; ?>
<div class="plans<?= count($plans) === 2 ? ' plans-2' : '' ?>">
  <?php foreach ($plans as $plan): $pid = (int) $plan['id']; ?>
  <article class="plan-card<?= $plan['is_highlighted'] ? ' is-highlighted' : '' ?>">
    <div class="plan-head">
      <?php if ($plan['is_highlighted']): ?><span class="plan-badge">Most popular</span><?php endif; ?>
      <span class="plan-icon on-dark"><?= duo($plan['icon']) ?></span>
      <h3><?= e($plan['title']) ?></h3>
      <p><?= e($plan['subtitle']) ?></p>
      <?php if ($plan['price_label'] !== ''): ?><div class="plan-price"><?= e($plan['price_label']) ?><?php if ($plan['price_note'] !== ''): ?><small><?= e($plan['price_note']) ?></small><?php endif; ?></div><?php endif; ?>
    </div>
    <ul class="plan-features">
      <?php foreach (lines($plan['features']) as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
    </ul>
    <form class="plan-form" action="<?= e(url('pricing')) ?>" method="get" data-quote-card data-plan-title="<?= e($plan['title']) ?>">
      <input type="hidden" name="plan" value="<?= $pid ?>">
      <label><?= e($plan['q1_label']) ?>
        <select class="select" name="q1" required>
          <option value="">Select an option</option>
          <?php foreach (Content::planOptions($plan, 'q1') as $o): ?><option<?= $preselect !== '' && $o === $preselect ? ' selected' : '' ?>><?= e($o) ?></option><?php endforeach; ?>
        </select>
      </label>
      <label><?= e($plan['q2_label']) ?>
        <select class="select" name="q2" required>
          <option value="">Select an option</option>
          <?php foreach (Content::planOptions($plan, 'q2') as $o): ?><option><?= e($o) ?></option><?php endforeach; ?>
        </select>
      </label>
      <button class="btn btn-block plan-cta" type="submit">Get a free quote</button>
    </form>
  </article>
  <?php endforeach; ?>
</div>
