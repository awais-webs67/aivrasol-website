<?php /** @var array $p @var array $totals @var array $updates @var array $entries */ ?>
<p><a class="link-arrow small" href="<?= e(url('portal')) ?>">← All projects</a></p>
<div class="project-head">
  <div>
    <span class="pstatus pstatus-<?= e($p['status']) ?>"><?= e(Portal::STATUSES[$p['status']] ?? $p['status']) ?></span>
    <h1 class="portal-title"><?= e($p['title']) ?></h1>
    <?php if ($p['summary']): ?><p class="lead"><?= nl2br(e($p['summary'])) ?></p><?php endif; ?>
  </div>
  <dl class="p-dl head-facts">
    <?php if ($p['start_date']): ?><dt>Started</dt><dd><?= e(date('j M Y', strtotime($p['start_date']))) ?></dd><?php endif; ?>
    <?php if ($p['due_date']): ?><dt>Target date</dt><dd><?= e(date('j M Y', strtotime($p['due_date']))) ?></dd><?php endif; ?>
    <?php if ($p['manager_name']): ?><dt>Project manager</dt><dd><?= e($p['manager_name']) ?></dd><?php endif; ?>
  </dl>
</div>
<div class="p-card">
  <div class="progress progress-lg" aria-label="<?= (int) $p['progress'] ?>% complete"><span class="w-<?= (int) (round($p['progress'] / 5) * 5) ?>"></span></div>
  <p class="small"><strong><?= (int) $p['progress'] ?>% complete</strong></p>
</div>
<div class="portal-grid wide-left">
  <section class="p-card" id="chat" aria-labelledby="chat-title">
    <h2 id="chat-title">Messages</h2>
    <?= partial('project-chat', ['endpoint' => url('portal/projects/' . $p['id'] . '/messages'), 'placeholder' => 'Message the team. You can attach files up to 10 MB.']) ?>
  </section>
  <section class="p-card" aria-labelledby="updates-title">
    <h2 id="updates-title">Updates</h2>
    <?= partial('project-updates', ['updates' => $updates]) ?>
  </section>
</div>
<section class="p-card" aria-labelledby="money-title">
  <h2 id="money-title">Budget and payments</h2>
  <?= partial('project-money', ['p' => $p, 'totals' => $totals, 'entries' => $entries, 'staff' => false]) ?>
</section>
