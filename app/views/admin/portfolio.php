<?php
/** @var array $row @var array $draft @var array $errors @var array $state @var bool $needsReview @var bool $isAdmin */
$v = fn (string $k) => (string) ($draft[$k] ?? '');
[$stateKey, $stateText] = $state;
$liveUrl = $row['slug'] ? absolute_url('team/' . $row['slug']) : null;
$base = is_local_site() ? 'yourdomain.com/team/' : preg_replace('#^https?://#', '', absolute_url('team')) . '/';
?>
<?php if (!(int) $row['allowed']): ?>
<section class="card narrow">
  <h2>Your portfolio page is not turned on</h2>
  <p>A portfolio page is your own public page on the website, with your experience, skills and best work. An admin needs to turn it on for you.</p>
  <?php if ($isAdmin): ?><p><a class="btn" href="<?= e(url('admin/team-pages')) ?>">Turn it on in Team pages</a></p><?php else: ?><p class="muted">Ask an admin to allow it in Team pages.</p><?php endif; ?>
</section>
<?php else: ?>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>

<section class="card pf-status">
  <div>
    <p class="small muted">Status</p>
    <p><span class="status pf-<?= e($stateKey) ?>"><?= e($stateText) ?></span></p>
    <?php if ($row['admin_note'] !== ''): ?><p class="pf-note"><strong>Note from the admin:</strong> <?= e($row['admin_note']) ?></p><?php endif; ?>
    <?php if ($liveUrl && !(int) $row['hidden']): ?><p class="small">Your page: <a href="<?= e($liveUrl) ?>" target="_blank" rel="noopener"><?= e($liveUrl) ?></a></p><?php endif; ?>
  </div>
  <div class="btn-group">
    <?php if ($row['draft']): ?><a class="btn btn-sm btn-outline" href="<?= e(url('admin/portfolio/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview draft</a><?php endif; ?>
    <?php if ($row['live']): ?>
    <form method="post" data-confirm="Take your page offline? The link will stop working until you publish again."><?= csrf_field() ?><input type="hidden" name="action" value="offline"><button class="btn btn-sm btn-outline" type="submit">Take page offline</button></form>
    <?php endif; ?>
  </div>
</section>

<form method="post" enctype="multipart/form-data" class="pf-form">
  <?= csrf_field() ?>
  <section class="card form-grid cols-2">
    <h2 class="span-2">The basics</h2>
    <div class="field span-2">
      <label for="pf-slug"><span>Page address *</span></label>
      <div class="input-prefix"><span><?= e($base) ?></span><input class="input" id="pf-slug" name="slug" value="<?= e($v('slug')) ?>" required maxlength="60" pattern="[a-z0-9]+(-[a-z0-9]+)*" autocapitalize="none" spellcheck="false"></div>
      <span class="small muted">Lowercase letters, numbers and dashes, for example ali-ahmed.</span>
    </div>
    <div class="field"><label for="pf-name"><span>Full name *</span></label><input class="input" id="pf-name" name="name" value="<?= e($v('name')) ?>" required maxlength="80" autocomplete="name"></div>
    <div class="field"><label for="pf-headline"><span>Job title *</span></label><input class="input" id="pf-headline" name="headline" value="<?= e($v('headline')) ?>" required maxlength="120" placeholder="Senior Laravel Developer"></div>
    <div class="field"><label for="pf-location"><span>City and country</span></label><input class="input" id="pf-location" name="location" value="<?= e($v('location')) ?>" maxlength="80" placeholder="Lahore, Pakistan"></div>
    <div class="field"><label for="pf-email"><span>Public email (optional)</span></label><input class="input" id="pf-email" type="email" name="email" value="<?= e($v('email')) ?>" maxlength="190"><span class="small muted">Leave empty to send people to the company contact page.</span></div>
    <div class="field span-2 pf-photo">
      <span class="pf-photo-now"><?php if ($v('photo') !== ''): ?><img src="<?= e(url($v('photo'))) ?>" alt="" width="96" height="96"><?php else: ?><b><?= e(Team::initials($v('name') ?: '?')) ?></b><?php endif; ?></span>
      <div>
        <label for="pf-photo"><span>Photo</span></label>
        <input class="input" id="pf-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
        <span class="small muted">A clear, friendly head-and-shoulders photo. Square works best. JPG, PNG or WebP up to 4 MB.</span>
      </div>
      <?php if ($v('photo') !== ''): ?><button class="btn btn-sm btn-outline" type="submit" name="action" value="remove_photo" formnovalidate>Remove photo</button><?php endif; ?>
    </div>
    <label class="check span-2"><input type="checkbox" name="open" value="1"<?= !empty($draft['open']) ? ' checked' : '' ?>> <span>Show a "Work with me" button<br><span class="small muted">It takes visitors to the company contact page.</span></span></label>
  </section>

  <section class="card form-grid">
    <h2>About you</h2>
    <div class="field">
      <label for="pf-about"><span>Introduction</span></label>
      <textarea class="textarea editor" id="pf-about" name="about" maxlength="5000" data-md data-base="<?= e(url('')) ?>" data-html="<?= e(Seo::markdown($v('about'))) ?>"><?= e($v('about')) ?></textarea>
      <span class="small muted">Two or three short paragraphs: what you do, what you are good at and what you enjoy working on.</span>
    </div>
    <div class="field">
      <label for="pf-skills"><span>Skills</span></label>
      <input class="input" id="pf-skills" name="skills" value="<?= e(implode(', ', (array) ($draft['skills'] ?? []))) ?>" maxlength="1500" placeholder="Laravel, PHP, MySQL, Vue.js, REST APIs">
      <span class="small muted">Separate with commas. Up to 30.</span>
    </div>
  </section>

  <?php foreach (Team::LISTS as $key => [$label, $max, $fields]):
      $items = array_values((array) ($draft[$key] ?? []));
      $items[] = [];
  ?>
  <section class="card pf-list" data-repeat="<?= e($key) ?>" data-max="<?= (int) $max ?>">
    <div class="card-head"><h2><?= e($label) ?></h2><span class="small muted">Up to <?= (int) $max ?>. Leave a row empty to skip it.</span></div>
    <div class="pf-rows" data-rows>
      <?php foreach ($items as $i => $item): ?>
      <fieldset class="pf-row">
        <legend class="sr-only"><?= e($label) ?> <?= $i + 1 ?></legend>
        <?php foreach ($fields as $f => [$fl, $len]): $wide = in_array($f, ['text', 'url'], true); ?>
        <label class="field<?= $wide ? ' span-2' : '' ?>"><span><?= e($fl) ?></span>
          <?php if ($f === 'text'): ?><textarea class="textarea short" name="<?= e($key) ?>[<?= $i ?>][<?= e($f) ?>]" maxlength="<?= (int) $len ?>"><?= e((string) ($item[$f] ?? '')) ?></textarea>
          <?php else: ?><input class="input" name="<?= e($key) ?>[<?= $i ?>][<?= e($f) ?>]" value="<?= e((string) ($item[$f] ?? '')) ?>" maxlength="<?= (int) $len ?>"<?= $f === 'url' ? ' type="url" placeholder="https://"' : '' ?><?= in_array($f, ['start', 'end', 'years', 'year'], true) ? ' placeholder="' . ($f === 'end' ? 'Present' : ($f === 'years' ? '2016 to 2020' : '2021')) . '"' : '' ?>><?php endif; ?>
        </label>
        <?php endforeach; ?>
        <button class="btn btn-sm btn-outline pf-remove" type="button" data-remove-row hidden>Remove</button>
      </fieldset>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-sm btn-outline" type="button" data-add-row hidden><?= icon('plus') ?>Add <?= e(strtolower(rtrim($label, 's'))) ?></button>
  </section>
  <?php endforeach; ?>

  <section class="card form-grid cols-2">
    <h2 class="span-2">Links</h2>
    <?php foreach (Team::LINKS as $k => $label): ?>
    <div class="field"><label for="pf-l-<?= e($k) ?>"><span><?= e($label) ?></span></label><input class="input" id="pf-l-<?= e($k) ?>" type="url" name="links[<?= e($k) ?>]" value="<?= e((string) ($draft['links'][$k] ?? '')) ?>" maxlength="255" placeholder="https://"></div>
    <?php endforeach; ?>
  </section>

  <div class="sticky-save pf-save">
    <button class="btn btn-outline" type="submit" name="action" value="save">Save draft</button>
    <button class="btn" type="submit" name="action" value="publish"><?= $needsReview ? 'Save and send for review' : 'Save and publish' ?></button>
    <span class="small muted"><?= $needsReview ? 'An admin checks your page before it goes live.' : 'Publishing updates your public page straight away.' ?></span>
  </div>
</form>
<?php endif; ?>
