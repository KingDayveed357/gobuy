<tr>
    <td>
        <span style="padding-left: {{ $depth * 1.5 }}rem;" class="fw-semibold text-body-emphasis">
            @if ($depth > 0)<span class="text-body-tertiary me-1">↳</span>@endif{{ $category->name }}
        </span>
    </td>
    <td class="text-center">{{ $category->products_count }}</td>
    <td><x-admin.status-badge :value="$category->is_active ? 'active' : 'archived'" :label="$category->is_active ? 'Active' : 'Inactive'" /></td>
    <td class="text-end">
        <div class="table-actions">
            <button class="btn btn-sm btn-phoenix-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#cat-edit-{{ $category->id }}">Edit</button>
            <button class="btn btn-sm btn-phoenix-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteModal" data-action="{{ route('admin.categories.destroy', $category) }}">Delete</button>
        </div>
    </td>
</tr>
<tr class="collapse" id="cat-edit-{{ $category->id }}">
    <td colspan="4" class="bg-body-highlight">
        <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="row g-2 align-items-end py-2">
            @csrf @method('PUT')
            <div class="col-sm-5">
                <label class="form-label fs-9">Name</label>
                <input class="form-control form-control-sm" type="text" name="name" value="{{ $category->name }}" required>
            </div>
            <div class="col-sm-4">
                <label class="form-label fs-9">Parent</label>
                <x-admin.category-select :options="$options->reject(fn ($o) => $o['id'] === $category->id)" name="parent_id" :selected="$category->parent_id" include-none class="form-select-sm" />
            </div>
            <div class="col-sm-auto">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="is_active" value="0">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="cat-active-{{ $category->id }}" @checked($category->is_active)>
                    <label class="form-check-label fs-9" for="cat-active-{{ $category->id }}">Active</label>
                </div>
            </div>
            <div class="col-sm-auto">
                <button class="btn btn-sm btn-primary" type="submit">Save</button>
            </div>
        </form>
    </td>
</tr>

@foreach ($byParent->get($category->id, collect()) as $child)
    @include('admin.categories._row', ['category' => $child, 'depth' => $depth + 1, 'byParent' => $byParent, 'options' => $options])
@endforeach
