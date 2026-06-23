<div>
    <div class="d-flex align-items-center justify-content-between mb-5">
        <h2 class="mb-0">
            Wishlist
            <span class="text-body-tertiary fw-normal ms-2 fs-5">({{ $items->total() }})</span>
        </h2>
    </div>

    @if ($items->isEmpty())
        <div class="text-center py-9 border-y border-translucent">
            <div class="mb-3"><span class="far fa-heart fs-3 text-body-tertiary"></span></div>
            <h5 class="mb-1">Your wishlist is empty</h5>
            <p class="text-body-tertiary mb-4">Tap the heart on any product to save it here.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary">Browse products</a>
        </div>
    @else
        <div class="border-y border-translucent" wire:loading.class="opacity-75">
            <div class="table-responsive scrollbar">
                <table class="table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="align-middle" scope="col" style="width:7%;"></th>
                            <th class="align-middle" scope="col" style="width:38%; min-width:250px;">PRODUCTS</th>
                            <th class="align-middle" scope="col" style="width:20%;">VARIANT</th>
                            <th class="align-middle text-end" scope="col" style="width:13%;">PRICE</th>
                            <th class="align-middle text-end pe-0" scope="col" style="width:22%;"> </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($items as $item)
                            @php
                                $product = $item->product;
                                $variant = $product->primaryVariant();
                            @endphp
                            <tr class="position-static" wire:key="wish-{{ $product->id }}">
                                <td class="align-middle white-space-nowrap ps-0 py-2">
                                    <a class="border border-translucent rounded-2 d-inline-block p-1" href="{{ route('products.show', $product) }}">
                                        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" width="53" height="53" style="object-fit:contain;">
                                    </a>
                                </td>
                                <td class="align-middle pe-4">
                                    <a class="fw-semibold mb-0 line-clamp-2 text-body-emphasis text-decoration-none" href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                                    <span class="d-block fs-10 text-body-tertiary">{{ $product->category?->name }}</span>
                                </td>
                                <td class="align-middle white-space-nowrap fs-9 text-body">{{ $product->hasVariants() ? 'Multiple options' : ($variant?->label() ?? '&mdash;') }}</td>
                                <td class="align-middle fs-9 fw-semibold text-end"><x-price-tag :product="$product" /></td>
                                <td class="align-middle text-end text-nowrap pe-0">
                                    <button class="btn btn-sm text-body-quaternary text-danger-hover me-2" title="Remove"
                                            wire:click="remove({{ $product->id }})" wire:loading.attr="disabled" wire:target="remove({{ $product->id }})">
                                        <span class="fas fa-trash"></span>
                                    </button>
                                    @if ($product->hasVariants())
                                        <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-phoenix-primary fs-10"><span class="fas fa-sliders me-1"></span>Choose options</a>
                                    @else
                                        <button class="btn btn-primary btn-sm fs-10" {{ ! $product->isInStock() ? 'disabled' : '' }}
                                                wire:click="moveToCart({{ $product->id }})" wire:loading.attr="disabled" wire:target="moveToCart({{ $product->id }})">
                                            <span class="fas fa-shopping-cart me-1 fs-10"></span>{{ $product->isInStock() ? 'Add to cart' : 'Sold out' }}
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($items->hasPages())
            {{-- Premium pagination: showing X-Y of Z + page numbers --}}
            <div class="d-flex flex-column flex-sm-row align-items-center justify-content-between gap-3 mt-4">
                <p class="text-body-tertiary fs-9 mb-0">
                    Showing {{ $items->firstItem() }}&ndash;{{ $items->lastItem() }} of {{ $items->total() }} items
                </p>
                <nav aria-label="Wishlist pages">
                    <ul class="pagination pagination-sm mb-0">
                        {{-- Previous --}}
                        <li class="page-item {{ $items->onFirstPage() ? 'disabled' : '' }}">
                            <button class="page-link" wire:click="previousPage" {{ $items->onFirstPage() ? 'disabled' : '' }} aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </button>
                        </li>

                        @php
                            $current   = $items->currentPage();
                            $last      = $items->lastPage();
                            $pageStart = max(1, $current - 2);
                            $pageEnd   = min($last, $current + 2);
                        @endphp

                        @if ($pageStart > 1)
                            <li class="page-item"><button class="page-link" wire:click="gotoPage(1)">1</button></li>
                            @if ($pageStart > 2)
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            @endif
                        @endif

                        @for ($p = $pageStart; $p <= $pageEnd; $p++)
                            <li class="page-item {{ $p === $current ? 'active' : '' }}">
                                <button class="page-link" wire:click="gotoPage({{ $p }})"
                                    {!! $p === $current ? 'aria-current="page"' : '' !!}>{{ $p }}</button>
                            </li>
                        @endfor

                        @if ($pageEnd < $last)
                            @if ($pageEnd < $last - 1)
                                <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
                            @endif
                            <li class="page-item"><button class="page-link" wire:click="gotoPage({{ $last }})">{{ $last }}</button></li>
                        @endif

                        {{-- Next --}}
                        <li class="page-item {{ $items->hasMorePages() ? '' : 'disabled' }}">
                            <button class="page-link" wire:click="nextPage" {{ ! $items->hasMorePages() ? 'disabled' : '' }} aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </button>
                        </li>
                    </ul>
                </nav>
            </div>
        @endif
    @endif
</div>
