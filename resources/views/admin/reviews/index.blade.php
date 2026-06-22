@extends('admin.layouts.app')

@section('title', 'Reviews — gobuy admin')
@section('page-title', 'Reviews')

@section('content')
    <x-admin.page-header title="Review moderation" subtitle="Approve, reject, or audit customer reviews" />

    <div class="mb-3 d-flex flex-wrap gap-2">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
            <a href="{{ route('admin.reviews.index', ['status' => $value]) }}"
               class="btn btn-sm {{ $status === $value ? 'btn-primary' : 'btn-phoenix-secondary' }}">
                {{ $label }} <span class="badge bg-body-secondary text-body ms-1">{{ $counts[$value] }}</span>
            </a>
        @endforeach
    </div>

    <x-admin.table
        :cols="[
            ['label' => 'Product'],
            ['label' => 'Customer'],
            ['label' => 'Rating'],
            ['label' => 'Review'],
            ['label' => 'Action', 'align' => 'end'],
        ]"
        :empty="$reviews->isEmpty()"
        empty-icon="fa-star"
        empty-text="No reviews here."
    >
        @foreach ($reviews as $review)
            <tr>
                <td class="fw-semibold text-body-emphasis">{{ $review->product?->name ?? '—' }}</td>
                <td>{{ $review->user?->name ?? '—' }}</td>
                <td class="text-warning text-nowrap">
                    @for ($i = 1; $i <= 5; $i++)<span class="fa{{ $i <= $review->rating ? 's' : 'r' }} fa-star fs-10"></span>@endfor
                </td>
                <td class="fs-9" style="max-width: 360px;">
                    @if ($review->title)<span class="fw-semibold d-block">{{ $review->title }}</span>@endif
                    <span class="text-body-secondary">{{ \Illuminate\Support\Str::limit($review->body, 160) }}</span>
                </td>
                <td class="text-end text-nowrap">
                    @if ($review->status !== \App\Modules\Review\Models\Review::STATUS_APPROVED)
                        <form action="{{ route('admin.reviews.approve', $review) }}" method="POST" class="d-inline">
                            @csrf<button class="btn btn-sm btn-phoenix-success">Approve</button>
                        </form>
                    @endif
                    @if ($review->status !== \App\Modules\Review\Models\Review::STATUS_REJECTED)
                        <form action="{{ route('admin.reviews.reject', $review) }}" method="POST" class="d-inline">
                            @csrf<button class="btn btn-sm btn-phoenix-danger">Reject</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-admin.table>

    <div class="mt-4">{{ $reviews->links() }}</div>
@endsection
