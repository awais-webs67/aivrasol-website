<section class="cookie" data-cookie hidden aria-labelledby="cookie-title" role="region">
  <h2 id="cookie-title">Our cookies taste delicious</h2>
  <p>We use necessary cookies to run this site. With your permission we also use analytics cookies to learn how visitors use it. Read our <a href="<?= e(url('cookie-policy')) ?>">cookie policy</a>.</p>
  <div class="cookie-opts" data-cookie-opts hidden>
    <div class="cookie-opt">
      <div><strong>Strictly necessary</strong><span>Security, forms and your cookie choice. Always on.</span></div>
      <label class="switch"><input type="checkbox" checked disabled aria-label="Strictly necessary cookies, always on"><i></i></label>
    </div>
    <div class="cookie-opt">
      <div><strong>Analytics</strong><span>Anonymous visit statistics with Google Analytics.</span></div>
      <label class="switch"><input type="checkbox" data-consent="analytics" aria-label="Analytics cookies"><i></i></label>
    </div>
    <div class="cookie-opt">
      <div><strong>Marketing</strong><span>Measures our ads. Off unless you turn it on.</span></div>
      <label class="switch"><input type="checkbox" data-consent="marketing" aria-label="Marketing cookies"><i></i></label>
    </div>
  </div>
  <div class="cookie-actions">
    <button class="btn btn-outline" type="button" data-consent-action="reject">Reject all</button>
    <button class="btn btn-outline" type="button" data-cookie-customise>Customise</button>
    <button class="btn btn-outline" type="button" data-consent-action="save" hidden>Save choices</button>
    <button class="btn" type="button" data-consent-action="accept">Accept all</button>
  </div>
</section>
