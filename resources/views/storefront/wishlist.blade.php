@extends('layouts.storefront')

@section('title', 'Wishlist — gobuy')

@section('content')
<section class="pt-5 pb-9">
    <div class="container-small cart">
        <nav class="mb-3" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
            </ol>
        </nav>
        <h2 class="mb-5">Wishlist<span class="text-body-tertiary fw-normal ms-2" id="wishlist-count">(0)</span></h2>
        
        <div class="border-y border-translucent" id="productWishlistTable">
            <div class="table-responsive scrollbar">
                <table class="table fs-9 mb-0">
                    <thead>
                        <tr>
                            <th class="sort white-space-nowrap align-middle fs-10" scope="col" style="width:7%;"></th>
                            <th class="sort white-space-nowrap align-middle" scope="col" style="width:30%; min-width:250px;" data-sort="products">PRODUCTS</th>
                            <th class="sort align-middle" scope="col" data-sort="color" style="width:16%;"></th>
                            <th class="sort align-middle" scope="col" data-sort="size" style="width:10%;"></th>
                            <th class="sort align-middle text-end" scope="col" data-sort="price" style="width:10%;">PRICE</th>
                            <th class="sort align-middle text-end pe-0" scope="col" style="width:35%;"> </th>
                        </tr>
                    </thead>
                    <tbody class="list" id="profile-wishlist-table-body">
                        <!-- Loaded via JS -->
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="d-none mt-4 text-center" id="empty-wishlist-msg">
                <h5 class="text-body-tertiary">Your wishlist is empty</h5>
                <a href="{{ route('products.index') }}" class="btn btn-primary mt-3">Browse Products</a>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    (function() {
        const WishlistAPI = {
            get: function() {
                try { return JSON.parse(localStorage.getItem('wishlist') || '[]'); } catch(e) { return []; }
            },
            remove: function(id) {
                let list = this.get();
                list = list.filter(i => i !== id);
                localStorage.setItem('wishlist', JSON.stringify(list));
            }
        };

        const list = WishlistAPI.get();
        const tbody = document.getElementById('profile-wishlist-table-body');
        const countSpan = document.getElementById('wishlist-count');
        const emptyMsg = document.getElementById('empty-wishlist-msg');
        
        countSpan.textContent = `(${list.length})`;

        if (list.length === 0) {
            tbody.innerHTML = '';
            emptyMsg.classList.remove('d-none');
            return;
        }

        fetch('{{ route('wishlist.items') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ ids: list })
        })
        .then(res => res.json())
        .then(data => {
            if (data.html) {
                tbody.innerHTML = data.html;
                
                // Add event listeners for remove buttons
                document.querySelectorAll('.remove-wishlist-btn').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        const id = parseInt(this.getAttribute('data-id'));
                        WishlistAPI.remove(id);
                        
                        // Remove the row
                        this.closest('tr').remove();
                        
                        // Update count
                        const currentList = WishlistAPI.get();
                        countSpan.textContent = `(${currentList.length})`;
                        
                        if (currentList.length === 0) {
                            emptyMsg.classList.remove('d-none');
                        }
                    });
                });
            } else {
                tbody.innerHTML = '';
                emptyMsg.classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error('Error fetching wishlist items', err);
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-4">Failed to load wishlist.</td></tr>';
        });
    })();
</script>
@endpush
