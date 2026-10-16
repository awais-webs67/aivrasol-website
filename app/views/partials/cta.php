<section class="cta-band">
  <div class="container cta-inner">
    <div>
      <h2><?= e($ctaTitle ?? 'Have a project in mind?') ?></h2>
      <p><?= e($ctaText ?? 'Book a free consultation and get a clear plan and quote within one business day.') ?></p>
    </div>
    <div class="btn-group">
      <button class="btn btn-light" type="button" data-modal-open="consult-modal">Free consultation</button>
      <a class="btn btn-ghost" href="<?= e(url('portfolio')) ?>">See our work</a>
    </div>
  </div>
</section>
