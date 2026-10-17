<?php /** @var array $rows */ ?>
<section class="card">
  <h2>Settings</h2>
  <form method="post" class="form-grid">
    <?= csrf_field() ?><input type="hidden" name="action" value="settings">
    <label class="check"><input type="checkbox" name="team_approval" value="1"<?= Team::approvalOn() ? ' checked' : '' ?>> <span>Check pages before they go live<br><span class="small muted">Recommended. When a team member publishes, you get an email and the page goes live only after you approve it. Admins' own pages go live straight away.</span></span></label>
    <label class="check"><input type="checkbox" name="team_list" value="1"<?= setting('team_list', '1') === '1' ? ' checked' : '' ?>> <span>Show a "Meet the team" page<br><span class="small muted">Lists every live portfolio page at <?= e(public_url('team')) ?>. Turn it off to keep only the direct links.</span></span></label>
    <div><button class="btn btn-outline" type="submit">Save settings</button></div>
  </form>
</section>

<section class="card">
  <div class="card-head"><h2>Team members</h2><?php if (Team::members() && setting('team_list', '1') === '1'): ?><a class="link-arrow small" href="<?= e(url('team')) ?>" target="_blank" rel="noopener">Open the team page <?= icon('external') ?></a><?php endif; ?></div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>Person</th><th>Page</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $r): [$key, $label] = $r['state']; $d = $r['d']; ?>
        <tr>
          <td>
            <div class="tp-person">
              <?php if (!empty($d['photo'])): ?><img src="<?= e(url($d['photo'])) ?>" alt="" width="38" height="38"><?php else: ?><b><?= e(Team::initials($r['name'])) ?></b><?php endif; ?>
              <span><strong><?= e($r['name']) ?></strong><?= !$r['is_active'] ? ' <span class="small muted">(disabled)</span>' : '' ?><br><span class="small muted"><?= e(Auth::roleLabel($r['role'])) ?><?= !empty($d['headline']) ? ' · ' . e($d['headline']) : '' ?></span></span>
            </div>
          </td>
          <td class="small"><?php if ($r['slug'] && $key !== 'off' && $key !== 'hidden'): ?><a href="<?= e(url('team/' . $r['slug'])) ?>" target="_blank" rel="noopener">/team/<?= e($r['slug']) ?></a><?php elseif (!empty($d['slug'])): ?><span class="muted">/team/<?= e($d['slug']) ?></span><?php else: ?><span class="muted">None yet</span><?php endif; ?></td>
          <td><span class="status pf-<?= e($key) ?>"><?= e($label) ?></span><?php if ($r['pending'] && $r['submitted_at']): ?><br><span class="small muted">sent <?= e(date('j M, H:i', strtotime($r['submitted_at']))) ?></span><?php endif; ?></td>
          <td>
            <div class="tp-actions">
              <?php if (!(int) $r['allowed']): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>"><button class="btn btn-sm" name="action" value="allow" type="submit"<?= !$r['is_active'] ? ' disabled' : '' ?>>Allow</button></form>
              <?php else: ?>
                <?php if ($r['draft']): ?><a class="btn btn-sm btn-outline" href="<?= e(url('admin/team-pages/' . $r['id'] . '/preview')) ?>" target="_blank" rel="noopener"><?= icon('eye') ?>Preview</a><?php endif; ?>
                <?php if ($r['pending']): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>"><button class="btn btn-sm" name="action" value="approve" type="submit">Approve</button></form>
                <form method="post" class="tp-note"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>"><input class="input" name="note" maxlength="500" placeholder="What should they change?" aria-label="Note for <?= e($r['name']) ?>"><button class="btn btn-sm btn-outline" name="action" value="sendback" type="submit">Send back</button></form>
                <?php endif; ?>
                <?php if ($r['live']): ?>
                <form method="post"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>"><button class="btn btn-sm btn-outline" name="action" value="<?= (int) $r['hidden'] ? 'show' : 'hide' ?>" type="submit"><?= (int) $r['hidden'] ? 'Show' : 'Hide' ?></button></form>
                <?php endif; ?>
                <form method="post" data-confirm="Turn off the portfolio page for <?= e($r['name']) ?>? Their page goes offline straight away. Their draft is kept."><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int) $r['user_id'] ?>"><button class="btn btn-sm btn-outline" name="action" value="revoke" type="submit">Turn off</button></form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
