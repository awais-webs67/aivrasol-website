<dialog class="modal" id="consult-modal" aria-labelledby="consult-title">
  <div class="modal-head">
    <h2 id="consult-title">Looking for a free consultation?</h2>
    <span class="bar"></span>
    <p>Tell us a little about your idea. An expert will contact you within one business day.</p>
    <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close') ?></button>
  </div>
  <div class="modal-body">
    <form class="form-grid" action="<?= e(url('submit/consultation')) ?>" method="post" data-ajax-form novalidate>
      <?= csrf_field() ?><?= bot_fields() ?>
      <input type="hidden" name="_back" value="<?= e(request_path()) ?>">
      <label class="field"><span>Name</span><input class="input" type="text" name="name" autocomplete="name" required maxlength="100"></label>
      <label class="field"><span>Email</span><input class="input" type="email" name="email" autocomplete="email" required maxlength="190"></label>
      <label class="field"><span>Your idea <em class="muted">(optional)</em></span><textarea class="textarea" name="message" rows="3" maxlength="2000"></textarea></label>
      <button class="btn btn-block" type="submit">Book my free consultation</button>
      <p class="form-note">We never share your details. See our <a href="<?= e(url('privacy-policy')) ?>">privacy policy</a>.</p>
    </form>
  </div>
</dialog>

<dialog class="modal" id="quote-modal" aria-labelledby="quote-title">
  <div class="modal-head">
    <h2 id="quote-title">Get your free quote</h2>
    <span class="bar"></span>
    <p>Just a few details and we will send you a clear, fixed quote.</p>
    <button class="modal-close" type="button" data-modal-close aria-label="Close"><?= icon('close') ?></button>
  </div>
  <div class="modal-body">
    <form class="form-grid" action="<?= e(url('submit/quote')) ?>" method="post" data-ajax-form novalidate>
      <?= csrf_field() ?><?= bot_fields() ?>
      <input type="hidden" name="_back" value="<?= e(request_path()) ?>">
      <input type="hidden" name="plan_id" value="">
      <input type="hidden" name="q1" value="">
      <input type="hidden" name="q2" value="">
      <input type="hidden" name="q1_other" value="">
      <input type="hidden" name="q2_other" value="">
      <p class="modal-summary" data-quote-summary></p>
      <label class="field"><span>Name</span><input class="input" type="text" name="name" autocomplete="name" required maxlength="100"></label>
      <label class="field"><span>Email</span><input class="input" type="email" name="email" autocomplete="email" required maxlength="190"></label>
      <label class="field"><span>Phone or WhatsApp <em class="muted">(optional)</em></span><input class="input" type="tel" name="phone" autocomplete="tel" maxlength="40"></label>
      <label class="field"><span>Project details <em class="muted">(optional)</em></span><textarea class="textarea" name="message" rows="3" maxlength="3000"></textarea></label>
      <button class="btn btn-block" type="submit">Send quote request</button>
    </form>
  </div>
</dialog>
