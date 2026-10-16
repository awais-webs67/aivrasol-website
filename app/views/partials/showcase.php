<?php
/**
 * A project screenshot shown inside a device frame on a backdrop made from the screenshot itself,
 * so any upload fits and the colours always match the project.
 * @var array $project @var bool $eager
 */
$frame = project_frame($project);
$main = (string) $project['image'];
$phone = (string) ($project['image_mobile'] ?? '');
$alt = $project['title'] . ' screenshot';
$load = !empty($eager) ? ' fetchpriority="high"' : ' loading="lazy"';
$shot = fn (string $src, string $a = '') => '<img src="' . e(asset($src)) . '" alt="' . e($a) . '" decoding="async"' . $load . '>';
$host = preg_replace('#^www\.#', '', (string) parse_url((string) ($project['url'] ?? ''), PHP_URL_HOST));
$bar = fn (string $label, bool $url) => '<div class="dev-bar"><i></i><i></i><i></i><span class="' . ($url ? 'dev-url' : 'dev-title') . '">' . ($url ? icon('lock') : '') . e($label) . '</span></div>';
if ($frame === 'none'): ?>
<div class="showcase sc-none"><?= $shot($main !== '' ? $main : 'assets/img/projects/placeholder.svg', $alt) ?></div>
<?php return; endif;
$bgSrc = $frame === 'mobile' && $main === '' ? $phone : ($main !== '' ? $main : $phone);
$tabletPortrait = $frame === 'tablet' && image_ratio($main) > 0 && image_ratio($main) < 1;
?>
<div class="showcase sc-<?= e($frame) ?>">
  <img class="sc-bg" src="<?= e(asset($bgSrc)) ?>" alt="" aria-hidden="true" decoding="async"<?= $load ?>>
  <?php if ($frame === 'browser' || $frame === 'desktop'): ?>
  <div class="dev dev-window">
    <?= $bar($frame === 'browser' ? ($host ?: strtolower(preg_replace('/[^a-z0-9]+/i', '', $project['title'])) . '.com') : $project['title'], $frame === 'browser') ?>
    <div class="dev-screen"><?= $shot($main, $alt) ?></div>
  </div>
  <?php elseif ($frame === 'laptop' || $frame === 'combo'): ?>
  <div class="dev dev-laptop">
    <div class="dev-lid"><div class="dev-screen"><?= $shot($main, $alt) ?></div></div>
    <div class="dev-base"></div>
  </div>
  <?php if ($frame === 'combo'): ?>
  <div class="dev dev-phone dev-phone-front"><span class="dev-notch"></span><div class="dev-screen"><?= $shot($phone, $project['title'] . ' on mobile') ?></div></div>
  <?php endif; ?>
  <?php elseif ($frame === 'tablet'): ?>
  <div class="dev dev-tablet<?= $tabletPortrait ? ' is-portrait' : '' ?>"><div class="dev-screen"><?= $shot($main, $alt) ?></div></div>
  <?php else: ?>
  <div class="dev dev-phone"><span class="dev-notch"></span><div class="dev-screen"><?= $shot($main !== '' ? $main : $phone, $alt) ?></div></div>
  <?php endif; ?>
</div>
