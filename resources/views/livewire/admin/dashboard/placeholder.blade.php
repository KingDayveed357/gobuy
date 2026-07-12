<div>
    <div class="row g-3 mb-4">
        @for ($i = 0; $i < 4; $i++)
            <div class="col-12 col-md-6 col-lg-3">
                <div class="admin-stat">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <x-admin.skeleton height="0.7rem" width="55%" />
                        <x-admin.skeleton width="2.25rem" height="2.25rem" class="rounded-3" />
                    </div>
                    <x-admin.skeleton height="1.6rem" width="70%" />
                </div>
            </div>
        @endfor
    </div>

    <div class="row g-3">
        <div class="col-12 col-xl-8">
            <x-admin.card title="Recent orders" flush>
                <div class="p-3">
                    <x-admin.skeleton type="table" :rows="6" :cols="4" />
                </div>
            </x-admin.card>
        </div>
        <div class="col-12 col-xl-4">
            <x-admin.card title="Low stock" flush>
                <div class="p-3">
                    <x-admin.skeleton type="list" :rows="5" />
                </div>
            </x-admin.card>
        </div>
    </div>
</div>
