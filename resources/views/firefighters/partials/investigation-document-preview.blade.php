<aside class="investigation-preview" aria-label="Live {{ $previewType }} investigation document preview"
    data-document-preview="{{ $previewType }}"
    data-preview-url="{{ route('firefighter.my-station.document-preview', ['type' => $previewType]) }}">
    <div class="investigation-preview__heading">
        <strong><i class="fa-regular fa-file-word" aria-hidden="true"></i> Live document preview</strong>
        <div class="investigation-preview__zoom" role="group" aria-label="Document preview zoom">
            <button type="button" data-preview-zoom="out" aria-label="Zoom out document preview" title="Zoom out">&minus;</button>
            <output data-preview-zoom-label aria-live="polite" aria-atomic="true">100%</output>
            <button type="button" data-preview-zoom="in" aria-label="Zoom in document preview" title="Zoom in">+</button>
            <button type="button" data-preview-zoom="reset" title="Reset preview zoom to 100%">Reset</button>
            <button type="button" data-preview-retry>Refresh</button>
        </div>
        <small>Rendered from the downloadable Word template. Updates after you pause typing. Automatic submission dates are provisional.</small>
        <div class="investigation-preview__status" role="status" aria-live="polite" data-preview-status>Preparing document preview...</div>
    </div>
    <div class="investigation-preview__scroll" tabindex="0" aria-label="Document preview, scroll to read">
        <div class="investigation-preview__zoom-frame">
            <div class="investigation-preview__zoom-content" data-preview-pages></div>
        </div>
    </div>
</aside>
