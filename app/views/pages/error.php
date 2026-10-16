<?php /** @var int $code */ ?>
<section class="container error-page">
  <div class="error-code"><?= (int) $code ?></div>
  <h1 class="display-2"><?= $code === 404 ? 'You seem to be lost!' : ($code === 403 ? 'Access denied' : 'Something went wrong') ?></h1>
  <span class="bar center"></span>
  <p class="lead center"><?= $code === 404 ? 'The page you are looking for does not exist or has moved.' : 'Please try again in a moment. If the problem continues, contact us.' ?></p>
  <div class="btn-group btn-center">
    <a class="btn" href="<?= e(url('/')) ?>">Go to home page</a>
    <a class="btn btn-outline" href="<?= e(url('contact')) ?>">Contact us</a>
  </div>
</section>
