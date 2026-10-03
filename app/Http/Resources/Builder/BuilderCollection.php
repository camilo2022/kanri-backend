<?php

namespace App\Http\Resources\Builder;

use App\Models\Category;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\Trademark;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BuilderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        $trademark_ids = $this->collection
            ->pluck('trademark_ids')
            ->flatten()
            ->unique()
            ->values();

        $category_ids = $this->collection
            ->pluck('category_ids')
            ->flatten()
            ->unique()
            ->values();

        $subcategory_ids = $this->collection
            ->pluck('subcategory_ids')
            ->flatten()
            ->unique()
            ->values();

        $size_ids = $this->collection
            ->pluck('percentages')
            ->map(fn($percentages) => array_keys($percentages))
            ->flatten()
            ->unique()
            ->values();

        $trademarks = Trademark::whereIn('id', $trademark_ids)
            ->get()
            ->keyBy('id');

        $categories = Category::whereIn('id', $category_ids)
            ->get()
            ->keyBy('id');

        $subcategories = Subcategory::whereIn('id', $subcategory_ids)
            ->get()
            ->keyBy('id');

        $sizes = Size::whereIn('id', $size_ids)
            ->get()
            ->keyBy('id');

        return [
            'builders' => $this->collection->map(function ($builder) use (
                $trademarks,
                $categories,
                $subcategories,
                $sizes
            ) {
                return [
                    'id' => $builder['id'],
                    'trademarks' => collect($builder['trademark_ids'])
                        ->map(fn($id) => $trademarks->get($id))
                        ->filter()
                        ->values(),
                    'categories' => collect($builder['category_ids'])
                        ->map(fn($id) => $categories->get($id))
                        ->filter()
                        ->values(),
                    'subcategories' => collect($builder['subcategory_ids'])
                        ->map(fn($id) => $subcategories->get($id))
                        ->filter()
                        ->values(),
                    'percentages' => collect($builder['percentages'])
                        ->map(function ($percentage, $sizeId) use ($sizes) {
                            return [
                                'size' => $sizes->get($sizeId),
                                'percentage' => (float) $percentage,
                            ];
                        })
                        ->filter(fn($item) => $item['size'] !== null)
                        ->values(),
                ];
            }),
        ];
    }
}
