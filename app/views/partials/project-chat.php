<?php /** @var string $endpoint @var string $placeholder */ ?>
<div class="chat" data-chat="<?= e($endpoint) ?>">
  <div class="chat-log" data-chat-log role="log" aria-live="polite" aria-label="Messages" tabindex="0">
    <p class="chat-empty muted" data-chat-empty>No messages yet. Say hello!</p>
  </div>
  <form class="chat-form" data-chat-form method="post" action="<?= e($endpoint) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label class="sr-only" for="chat-body">Message</label>
    <textarea class="textarea" id="chat-body" name="body" rows="2" maxlength="5000" placeholder="<?= e($placeholder ?? 'Write a message') ?>"></textarea>
    <div class="chat-actions">
      <label class="chat-file"><?= icon('paperclip') ?><span data-chat-filename>Attach a file</span><input type="file" name="file" data-chat-file></label>
      <button class="btn btn-sm" type="submit">Send</button>
    </div>
    <p class="chat-status small" data-chat-status role="status"></p>
  </form>
  <noscript><p class="small muted">Turn on JavaScript to use the chat.</p></noscript>
</div>
