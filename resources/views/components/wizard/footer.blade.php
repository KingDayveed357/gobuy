@props(['showPrev' => true, 'showNext' => true, 'submitLabel' => 'Save Changes', 'submitAction' => null])

<div class="gb-wizard-nav" data-gb-footer>
    <button type="button"
            class="btn-prev btn btn-link ps-0 text-body-tertiary"
            data-gb-prev
            style="display: none;">
        <i class="fas fa-arrow-left me-1 fs-10"></i> Back
    </button>

    <div class="ms-auto d-flex align-items-center gap-2">
        <button type="button"
                class="btn btn-phoenix-secondary btn-sm"
                data-gb-next
                style="display: none;">
            Next <i class="fas fa-arrow-right ms-1 fs-10"></i>
        </button>
    </div>
</div>
