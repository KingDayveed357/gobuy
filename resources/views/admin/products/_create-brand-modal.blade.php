{{-- Inline brand creator. Submits via fetch (admin.js) so the product
     form keeps its in-progress data; the new brand is appended to the
     product's brand <select> and auto-selected. --}}
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New brand</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-subtle-danger d-none" id="addBrandError"></div>
                <div class="mb-0">
                    <label class="form-label">Name</label>
                    <input class="form-control" type="text" id="newBrandName" placeholder="e.g. Samsung" autocomplete="off">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary js-create-brand"
                        data-store-url="{{ route('admin.brands.store') }}"
                        data-target-select="#productBrandSelect">Create brand</button>
            </div>
        </div>
    </div>
</div>
