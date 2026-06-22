@props(['categories'])

{{-- Mobile Trigger (Visible on small screens) --}}
<div class="d-lg-none">
    <button class="btn text-body ps-0 pe-3 text-nowrap d-flex align-items-center gap-2"
            type="button"
            data-bs-toggle="offcanvas"
            data-bs-target="#mobileMegaMenu"
            aria-controls="mobileMegaMenu">
        <span class="fas fa-bars fs-9"></span>
        <span class="fs-9">All Categories</span>
    </button>
</div>

{{-- Desktop Mega Menu (hidden on mobile) --}}
<div class="d-none d-lg-block mega-menu-container position-relative" id="megaMenuDesktopWrapper">
    <button
        type="button"
        id="desktopMegaMenuTrigger"
        class="btn text-body ps-0 pe-5 text-nowrap mega-menu-trigger d-flex align-items-center gap-2"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="desktopMegaMenu">
        <span class="fas fa-bars fs-9"></span>
        <span>All Categories</span>
    </button>

    <div id="desktopMegaMenu"
         class="mega-menu-panel border border-translucent position-absolute bg-body"
         style="display:none; top:calc(100% + 4px); left:0; width:820px; z-index:1060; max-height:72vh;"
         role="dialog"
         aria-label="Category navigation">
        <div class="row g-0" style="height:100%; max-height:72vh;">

            {{-- Left panel: root categories --}}
            <div class="col-4 mega-menu-left-panel overflow-auto py-2" style="max-height:72vh;">
                <ul class="nav flex-column mega-menu-root-list w-100 mb-0 px-2">
                    <li class="nav-item w-100">
                        <a class="nav-link mega-menu-root-link d-flex align-items-center gap-2 active"
                           href="{{ route('products.index') }}"
                           data-target="mm-all">
                            <span class="fas fa-th-large fs-10 flex-shrink-0 opacity-75"></span>
                            <span class="text-truncate">All Products</span>
                        </a>
                    </li>
                    @foreach($categories as $category)
                    <li class="nav-item w-100">
                        <a class="nav-link mega-menu-root-link d-flex align-items-center gap-2"
                           href="{{ route('products.index', ['category' => $category->slug]) }}"
                           data-target="mm-cat-{{ $category->id }}">
                            <span class="fas fa-tag fs-10 flex-shrink-0 opacity-50"></span>
                            <span class="text-truncate pe-1">{{ $category->name }}</span>
                            @if($category->children->count() > 0)
                                <span class="fas fa-chevron-right fs-11 ms-auto flex-shrink-0 text-body-quaternary"></span>
                            @endif
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            {{-- Right panel: subcategories --}}
            <div class="col-8 mega-menu-right-panel overflow-auto py-3 px-4" style="max-height:72vh;">
                <div class="mb-3">
                    <input type="search"
                           class="form-control mega-menu-search"
                           placeholder="Search categories…"
                           aria-label="Search categories">
                </div>

                <div class="mega-menu-content-area">
                    {{-- All Products panel --}}
                    <div class="mega-menu-content" id="mm-all">
                        <h6 class="text-body-emphasis fw-bold mb-1">All Products</h6>
                        <p class="text-body-tertiary fs-10 mb-3">Browse our full catalog across every category.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-arrow-right me-1"></i> Shop Now
                        </a>
                    </div>

                    @foreach($categories as $category)
                    <div class="mega-menu-content" id="mm-cat-{{ $category->id }}" style="display:none;">
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h6 class="mb-0">
                                <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                                   class="text-body-emphasis fw-bold text-decoration-none">
                                    {{ $category->name }}
                                </a>
                            </h6>
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                               class="text-primary text-decoration-none fs-10 ms-auto">
                                View all <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </div>

                        @if($category->children->count() > 0)
                            <div class="row g-3 subcategories-grid">
                                @foreach($category->children as $child)
                                <div class="col-md-6 subcategory-item" data-name="{{ strtolower($child->name) }}">
                                    <a href="{{ route('products.index', ['category' => $child->slug]) }}"
                                       class="fw-semibold text-body-emphasis text-decoration-none d-block mb-1 fs-9">
                                        {{ $child->name }}
                                    </a>
                                    @if($child->children->count() > 0)
                                        <ul class="list-unstyled mb-0 ms-0 ps-0">
                                            @foreach($child->children->take(5) as $grandchild)
                                                <li>
                                                    <a href="{{ route('products.index', ['category' => $grandchild->slug]) }}"
                                                       class="text-body-tertiary text-decoration-none fs-10 d-block py-1">
                                                        {{ $grandchild->name }}
                                                    </a>
                                                </li>
                                            @endforeach
                                            @if($child->children->count() > 5)
                                                <li>
                                                    <a href="{{ route('products.index', ['category' => $child->slug]) }}"
                                                       class="text-primary text-decoration-none fs-10 d-block py-1 fw-semibold">
                                                        +{{ $child->children->count() - 5 }} more
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-body-tertiary fs-10 mb-3">Browse all products in {{ $category->name }}.</p>
                            <a href="{{ route('products.index', ['category' => $category->slug]) }}"
                               class="btn btn-outline-primary btn-sm">
                                View {{ $category->name }}
                            </a>
                        @endif
                    </div>
                    @endforeach
                </div>

                {{-- Empty state --}}
                <div class="mega-menu-empty-state text-center py-5" style="display:none;">
                    <span class="fas fa-search text-body-quaternary mb-2 d-block" style="font-size:1.5rem;"></span>
                    <p class="text-body-tertiary mb-0 fs-9">No matching categories found.</p>
                </div>
            </div>

        </div>
    </div>
</div>

