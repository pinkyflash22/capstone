<section class="help-support">
    <h4>Still have a question?</h4><p>Describe an account or app issue. Do not include passwords, verification codes, or sensitive incident details.</p>
    <form data-support-form data-support-email="{{ $supportEmail }}">
        <label for="supportTopic">Topic<select id="supportTopic" name="topic"><option>Account or verification</option><option>Location or reporting issue</option><option>Privacy question</option><option>General question</option></select></label>
        <label for="supportQuestion">Your question<textarea id="supportQuestion" name="question" rows="4" required minlength="10" maxlength="1500" placeholder="What were you trying to do, and what happened?"></textarea></label>
        <p class="help-caption">@if($supportEmail) Opens your email app addressed to {{ $supportEmail }}. You must send the email yourself. @else An online support inbox is not configured yet. Copy your question and share it with the administrator who manages your PyroSense access. @endif</p>
        <button class="help-button" type="submit">{{ $supportEmail ? 'Prepare support email' : 'Copy my question' }}</button>
        <p data-support-status role="status" class="help-caption"></p>
    </form>
</section>
