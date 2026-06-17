{{-- Inline category creator. Submits via fetch (admin.js) so the product
     form keeps its in-progress data; the new category is appended to the
     product's category <select> and auto-selected. --}}
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">New category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-subtle-danger d-none" id="addCategoryError"></div>
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input class="form-control" type="text" id="newCategoryName" placeholder="e.g. Laptops" autocomplete="off">
                </div>
                <div class="mb-0">
                    <label class="form-label">Parent category</label>
                    <x-admin.category-select :options="$options" name="new_category_parent" include-none id="newCategoryParent" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-phoenix-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary js-create-category"
                        data-store-url="{{ route('admin.categories.store') }}"
                        data-target-select="#productCategorySelect">Create category</button>
            </div>
        </div>
    </div>
</div>
