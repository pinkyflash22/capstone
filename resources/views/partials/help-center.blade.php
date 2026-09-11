@php
    $supportEmail = config('public-support.email');
    $supportEmail = filter_var($supportEmail, FILTER_VALIDATE_EMAIL) ? $supportEmail : '';
@endphp
<template id="help-content-aboutBfpUrbiztondo" data-title="About PyroSense">
    <div class="help-prose">
        <div class="help-intro"><span class="help-eyebrow">Community safety, connected</span><h3>Clear information. Better coordination.</h3><p>PyroSense brings citizen reports, station resources, and prevention planning into one workspace for participating users and stations.</p></div>
        <div class="help-grid"><section><h4>For the community</h4><p>Find public station information, submit incident details, and follow the updates available for your report.</p></section><section><h4>For station personnel</h4><p>Review reports, manage resources and duty schedules, and turn reviewed forecasts into mitigation actions.</p></section></div>
        <section><h4>What the information means</h4><p>Records reflect the latest information available to the platform. Coverage, connectivity, and staff updates affect what you see. Forecasts are decision-support estimates, not a replacement for professional judgment.</p></section>
        <div class="help-callout"><strong>A coordination tool, not a promise of immediate dispatch.</strong><p>For immediate danger, contact emergency services directly. Do not wait for the app to update.</p></div>
    </div>
</template>
<template id="help-content-faq" data-title="Help & frequently asked questions">
    <div class="help-prose">
        <div class="help-intro"><span class="help-eyebrow">Start here</span><h3>How can we help?</h3><p>Quick answers about reports, accounts, location, and support.</p></div>
        <label class="help-search" for="helpSearch">Search questions<input id="helpSearch" type="search" placeholder="Try location, verification, or offline" autocomplete="off"></label>
        <div data-help-faq>
            <details><summary>How do I report a fire?</summary><p>For immediate danger, contact emergency services directly. Use the report option to provide the location, landmarks, and what you observed. Only take photos if it is safe, and check whether the app confirms submission.</p></details>
            <details><summary>My location is inaccurate. What should I do?</summary><p>Check location permission and review the map pin. If the page allows it, adjust the pin and add a clear landmark. A weak GPS signal can place you away from the incident; do not move toward danger to improve the signal.</p></details>
            <details><summary>Does a saved offline report reach the station?</summary><p>Saving locally is not confirmation of delivery. Reconnect and check the submission status. For urgent help, use a working emergency contact channel instead of waiting for synchronization.</p></details>
            <details><summary>Why has the truck marker or report status not changed?</summary><p>Map and status updates can be delayed by connectivity or station activity. The marker is not proof of physical arrival. Use an official station channel if you need to confirm the response.</p></details>
            <details><summary>Where is my verification email?</summary><p>Check Spam and confirm you entered the correct address. Use the latest code; PyroSense verification codes expire after 10 minutes. Request a new code if it expires and never share it with anyone.</p></details>
            <details><summary>Are AI forecasts guaranteed to be correct?</summary><p>No. Forecasts depend on available records and model limitations. They help authorized staff prioritize review; they do not establish that a fire will happen or that an area is safe.</p></details>
            <details><summary>Is an anonymous report completely untraceable?</summary><p>No. You can report without a registered account, but report content, location, attachments, and security records may still identify you. Include only relevant information.</p></details>
            <details><summary>How do I ask about training or station services?</summary><p>Contact your local fire station using the official directory. Training requests and general inquiries are not emergency reports.</p></details>
        </div>
        <p class="help-empty" data-help-empty hidden>No matching questions. Try another word or prepare a support question below.</p>
        @include('partials.support-question', ['supportEmail' => $supportEmail])
    </div>
</template>
<template id="help-content-contact" data-title="Contact & support">
    <div class="help-prose">
        <div class="help-callout"><strong>Immediate danger? Contact emergency services.</strong><p>This support form is not monitored for dispatch and does not create an incident report.</p><a class="help-button" href="tel:911">Call 911</a></div>
        <section><h4>Station services and public inquiries</h4><p>Find the current station contact through the official directory. This directory is for station coordination, not a PyroSense technical-support ticket.</p><a class="help-button help-button--secondary" href="https://region1.bfp.gov.ph/contact-us/" target="_blank" rel="noopener noreferrer">Open BFP Region 1 directory</a></section>
        @include('partials.support-question', ['supportEmail' => $supportEmail])
    </div>
</template>
<template id="help-content-terms" data-title="Terms of Service">@include('partials.policies.terms')</template>
<template id="help-content-privacy" data-title="Privacy Policy">@include('partials.policies.privacy')</template>
