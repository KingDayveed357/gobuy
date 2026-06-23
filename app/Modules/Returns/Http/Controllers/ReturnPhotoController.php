<?php

namespace App\Modules\Returns\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Returns\Models\ReturnRequest;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves return evidence photos behind an authorization gate (the owning
 * customer, or an admin who handles returns) instead of exposing public,
 * guessable media URLs — return photos can carry PII and damage evidence.
 */
class ReturnPhotoController extends Controller
{
    public function show(ReturnRequest $return, Media $media): BinaryFileResponse
    {
        $isOwner = auth('web')->check() && $return->user_id === auth('web')->id();
        $isAdmin = auth('admin')->check() && auth('admin')->user()?->can('manage_returns');

        abort_unless($isOwner || $isAdmin, 403);

        // The media must actually belong to this return's photo collection.
        abort_unless(
            (int) $media->model_id === $return->id
            && $media->model_type === $return->getMorphClass()
            && $media->collection_name === ReturnRequest::MEDIA_PHOTOS,
            404,
        );

        return response()->file($media->getPath());
    }
}
