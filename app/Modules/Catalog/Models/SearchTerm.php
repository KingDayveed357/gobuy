<?php

namespace App\Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SearchTerm extends Model
{
    protected $fillable = ['term', 'hits', 'last_searched_at'];

    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }

    /**
     * Record a search, incrementing the term's running hit counter.
     */
    public static function record(string $term): void
    {
        $term = Str::lower(trim($term));

        if ($term === '' || mb_strlen($term) < 2 || mb_strlen($term) > 60) {
            return;
        }

        $row = static::firstOrNew(['term' => $term]);
        $row->hits = ($row->hits ?? 0) + 1;
        $row->last_searched_at = now();
        $row->save();
    }

    /**
     * @return Collection<int, string>
     */
    public static function trending(int $limit = 6): Collection
    {
        return static::orderByDesc('hits')->orderByDesc('last_searched_at')->limit($limit)->pluck('term');
    }
}
