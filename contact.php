<?php
require_once __DIR__ . '/includes/functions.php';

/*
 * Contact page. For now the form is a placeholder ("dummy") — it validates the
 * input and shows a friendly confirmation, but does not send an email yet.
 * When you're ready to receive messages, the sending code goes in the marked
 * spot below (email or save-to-database).
 */

$flash = null;
$sent  = false;
$vals  = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($vals as $k => $_) { $vals[$k] = trim($_POST[$k] ?? ''); }

    if ($vals['name'] === '' || $vals['email'] === '' || $vals['message'] === '') {
        $flash = ['err', 'Please fill in your name, email and message.'];
    } elseif (!filter_var($vals['email'], FILTER_VALIDATE_EMAIL)) {
        $flash = ['err', 'Please enter a valid email address.'];
    } else {
        // ── Placeholder: message accepted but not delivered anywhere yet. ──
        //    To make it live later, send an email or save to the database here.
        $sent  = true;
        $flash = ['ok', 'Thanks, ' . $vals['name'] . '! Your message has been received — we\'ll get back to you soon.'];
        $vals  = ['name' => '', 'email' => '', 'subject' => '', 'message' => '']; // clear the form
    }
}

$pageTitle = 'Contact';
include __DIR__ . '/includes/header.php';
?>

<a id="top"></a>
<section class="block" style="padding-top:56px">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">Get in Touch</span>
        <h2>Contact Us</h2>
        <div class="divider"></div>
      </div>
    </div>

    <div class="contact-cols">
      <!-- Contact form -->
      <div class="panel">
        <h3 style="margin-bottom:6px">Send us a message</h3>
        <p style="color:var(--steel);margin:0 0 20px;font-size:.94rem">Questions about the team, tryouts or sponsorship? Drop us a line.</p>

        <?php if ($flash): ?>
          <div class="flash <?= $flash[0] ?>"><?= e($flash[1]) ?></div>
        <?php endif; ?>

        <form method="post" novalidate>
          <div class="cf-row">
            <div class="field">
              <label for="cf-name">Name</label>
              <input type="text" id="cf-name" name="name" value="<?= e($vals['name']) ?>" placeholder="Your name" required>
            </div>
            <div class="field">
              <label for="cf-email">Email</label>
              <input type="email" id="cf-email" name="email" value="<?= e($vals['email']) ?>" placeholder="you@example.com" required>
            </div>
          </div>
          <div class="field">
            <label for="cf-subject">Subject <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional)</span></label>
            <input type="text" id="cf-subject" name="subject" value="<?= e($vals['subject']) ?>" placeholder="What's this about?">
          </div>
          <div class="field">
            <label for="cf-message">Message</label>
            <textarea id="cf-message" name="message" placeholder="Type your message..." required><?= e($vals['message']) ?></textarea>
          </div>
          <button class="btn btn-primary" type="submit">Send Message</button>
        </form>
      </div>

      <!-- Contact details -->
      <aside class="contact-info">
        <h3>Reach the Outlaws</h3>
        <p>Prefer to talk? Give us a call — we'd love to hear from you.</p>

        <a class="contact-phone" href="tel:+13055460376">
          <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/>
          </svg>
          <span>(305) 546-0376</span>
        </a>

        <div class="contact-note">More ways to reach us coming soon.</div>
      </aside>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
