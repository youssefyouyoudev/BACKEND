<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Channel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MobileCatalogService
{
    public function publicChannels(): Builder
    {
        return Channel::query()
            ->where('is_active', true)
            ->where('is_live', true)
            ->canonical()
            ->whereHas('playlist', fn (Builder $query) => $query
                ->where('is_public', true)
                ->whereNotNull('approved_at'));
    }

    /**
     * @return LengthAwarePaginator<int, Channel>
     */
    public function channels(array $filters): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters['per_page'] ?? null);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $search = $this->searchTerm($filters['q'] ?? $filters['search'] ?? null);
        $category = $this->searchTerm($filters['category'] ?? null);
        $quality = $this->qualityFilter($filters['quality'] ?? null);
        $featured = (bool) ($filters['featured'] ?? false);

        $cacheKey = 'mobile:channels:'.md5(json_encode([
            'search' => $search,
            'category' => $category,
            'quality' => $quality,
            'featured' => $featured,
            'per_page' => $perPage,
            'page' => $page,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(3), function () use ($search, $category, $quality, $featured, $perPage): LengthAwarePaginator {
            return $this->publicChannels()
                ->with(['category', 'playlist', 'currentProgram', 'streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])
                ->when($featured, fn (Builder $query) => $query->where('is_featured', true))
                ->when($category !== '', function (Builder $query) use ($category): void {
                    $query->where(function (Builder $categoryQuery) use ($category): void {
                        $categoryQuery
                            ->whereHas('category', fn (Builder $relationQuery) => $relationQuery->where('slug', $category))
                            ->orWhere('group_title', $category);
                    });
                })
                ->when($search !== '', fn (Builder $query) => $this->applyChannelSearch($query, $search))
                ->when($quality !== null, fn (Builder $query) => $this->applyQualityFilter($query, $quality))
                ->orderByDesc('is_featured')
                ->orderBy('featured_rank')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate($perPage);
        });
    }

    public function channel(int|string $id): ?Channel
    {
        return Cache::remember("mobile:channel:{$id}", now()->addMinutes(3), fn () => $this->publicChannels()
            ->with(['category', 'playlist', 'currentProgram', 'streams' => fn ($query) => $query->where('is_active', true)->orderBy('priority')])
            ->whereKey($id)
            ->first());
    }

    public function category(string $slug): ?Category
    {
        return Cache::remember("mobile:category:{$slug}", now()->addMinutes(10), fn () => Category::query()
            ->where('is_active', true)
            ->where('slug', $slug)
            ->first());
    }

    public function categories(): Collection
    {
        return Cache::remember('mobile:channel-categories', now()->addMinutes(10), fn () => Category::query()
            ->where('is_active', true)
            ->withCount(['channels' => fn (Builder $query) => $this->publicChannelConstraints($query)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->filter(fn (Category $category): bool => $category->channels_count > 0)
            ->values());
    }

    /**
     * @return LengthAwarePaginator<int, Article>
     */
    public function news(array $filters): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters['per_page'] ?? null, 20);
        $page = max(1, (int) ($filters['page'] ?? 1));
        $search = $this->searchTerm($filters['q'] ?? $filters['search'] ?? null);
        $category = $this->searchTerm($filters['category'] ?? null);

        $cacheKey = 'mobile:news:'.md5(json_encode([
            'search' => $search,
            'category' => $category,
            'per_page' => $perPage,
            'page' => $page,
        ], JSON_THROW_ON_ERROR));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($search, $category, $perPage): LengthAwarePaginator {
            return Article::query()
                ->published()
                ->with(['author', 'category'])
                ->when($category !== '', fn (Builder $query) => $query->whereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category)))
                ->when($search !== '', function (Builder $query) use ($search): void {
                    $query->where(function (Builder $searchQuery) use ($search): void {
                        $searchQuery
                            ->where('title', 'like', '%'.$search.'%')
                            ->orWhere('excerpt', 'like', '%'.$search.'%')
                            ->orWhere('body', 'like', '%'.$search.'%');
                    });
                })
                ->latest('published_at')
                ->paginate($perPage);
        });
    }

    public function article(string $slug): ?Article
    {
        return Cache::remember("mobile:article:{$slug}", now()->addMinutes(5), fn () => Article::query()
            ->published()
            ->with(['author', 'category'])
            ->where('slug', $slug)
            ->first());
    }

    public function leagues(): Collection
    {
        return Cache::remember('mobile:football-leagues', now()->addDay(), fn () => collect(config('football_leagues.top_leagues', []))
            ->map(fn (array $league): array => [
                'id' => (int) ($league['id'] ?? 0),
                'name' => (string) ($league['name'] ?? 'Football League'),
                'slug' => (string) ($league['slug'] ?? Str::slug((string) ($league['name'] ?? 'football-league'))),
                'country' => (string) ($league['country'] ?? 'World'),
                'status' => 'active',
                'image_url' => asset('brand/rifi-logo.png'),
            ])
            ->values());
    }

    public function search(string $query): array
    {
        $query = $this->searchTerm($query);

        if ($query === '') {
            return [
                'channels' => collect(),
                'news' => collect(),
                'leagues' => collect(),
            ];
        }

        return Cache::remember('mobile:search:'.md5($query), now()->addMinutes(3), fn (): array => [
            'channels' => $this->publicChannels()
                ->with(['category', 'playlist', 'currentProgram', 'streams' => fn ($streamQuery) => $streamQuery->where('is_active', true)->orderBy('priority')])
                ->where(fn (Builder $channelQuery) => $this->applyChannelSearch($channelQuery, $query))
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->limit(12)
                ->get(),
            'news' => Schema::hasTable('articles')
                ? Article::query()
                    ->published()
                    ->with(['author', 'category'])
                    ->where(function (Builder $articleQuery) use ($query): void {
                        $articleQuery
                            ->where('title', 'like', '%'.$query.'%')
                            ->orWhere('excerpt', 'like', '%'.$query.'%');
                    })
                    ->latest('published_at')
                    ->limit(8)
                    ->get()
                : collect(),
            'leagues' => $this->leagues()
                ->filter(fn (array $league): bool => str_contains(Str::lower($league['name'].' '.$league['country']), Str::lower($query)))
                ->values(),
        ]);
    }

    private function publicChannelConstraints(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where('is_live', true)
            ->whereHas('playlist', fn (Builder $playlistQuery) => $playlistQuery
                ->where('is_public', true)
                ->whereNotNull('approved_at'));
    }

    private function applyChannelSearch(Builder $query, string $search): Builder
    {
        return $query->where(function (Builder $searchQuery) use ($search): void {
            $searchQuery
                ->where('name', 'like', '%'.$search.'%')
                ->orWhere('tvg_id', 'like', '%'.$search.'%')
                ->orWhere('group_title', 'like', '%'.$search.'%')
                ->orWhereHas('category', fn (Builder $categoryQuery) => $categoryQuery->where('name', 'like', '%'.$search.'%'));
        });
    }

    private function applyQualityFilter(Builder $query, string $quality): Builder
    {
        return $query->where(function (Builder $qualityQuery) use ($quality): void {
            $qualityQuery
                ->where('name', 'like', '%'.$quality.'%')
                ->orWhereJsonContains('metadata->quality', $quality)
                ->orWhereHas('streams', fn (Builder $streamQuery) => $streamQuery->where('quality', 'like', '%'.$quality.'%'));
        });
    }

    private function perPage(mixed $value, int $default = 24): int
    {
        return min(100, max(1, (int) ($value ?: $default)));
    }

    private function searchTerm(mixed $value): string
    {
        return Str::of((string) $value)->squish()->limit(80, '')->toString();
    }

    private function qualityFilter(mixed $value): ?string
    {
        $quality = Str::upper($this->searchTerm($value));

        return in_array($quality, ['SD', 'HD', 'FHD'], true) ? $quality : null;
    }
}
