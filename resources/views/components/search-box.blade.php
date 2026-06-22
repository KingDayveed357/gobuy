@props(['size' => ''])

<form class="gb-search position-relative w-100" action="{{ route('products.index') }}" method="GET" role="search" data-gb-search autocomplete="off">
    <input class="form-control gb-search-input {{ $size === 'sm' ? 'form-control-sm' : '' }}"
           type="search" name="q" value="{{ request('q') }}"
           placeholder="Search products…" aria-label="Search products"
           role="combobox" aria-expanded="false" aria-autocomplete="list" aria-controls="" data-gb-search-input>
    <span class="fas fa-search gb-search-icon" aria-hidden="true"></span>
    <div class="gb-search-panel dropdown-menu shadow border border-translucent w-100 p-0 mt-1" data-gb-search-panel role="listbox" aria-label="Search suggestions"></div>
</form>
