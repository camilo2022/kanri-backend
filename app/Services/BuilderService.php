<?php

namespace App\Services;

use App\Models\Category;

class BuilderService
{
    /**
     * Claves de caché con que se encuentra un constructor: por marca; por marca y categoría si la categoría no tiene
     * subcategorías elegidas; y por marca, categoría y subcategoría. Gana la más específica.
     *
     * @param  array{trademark_ids: array, category_ids: array, subcategory_ids: array}  $builder
     */
    public function indexKeys(array $builder): array
    {
        $categories = Category::with(['subcategories' => fn ($query) => $query->whereIn('id', $builder['subcategory_ids'])])
            ->whereIn('id', $builder['category_ids'])
            ->get();

        $index_keys = [];

        foreach ($builder['trademark_ids'] as $trademark_id) {
            if ($categories->isEmpty()) {
                $index_keys[] = "curve_builder:index:{$trademark_id}";

                continue;
            }

            foreach ($categories as $category) {
                if ($category->subcategories->isEmpty()) {
                    $index_keys[] = "curve_builder:index:{$trademark_id}:{$category->id}";

                    continue;
                }

                foreach ($category->subcategories as $subcategory) {
                    $index_keys[] = "curve_builder:index:{$trademark_id}:{$category->id}:{$subcategory->id}";
                }
            }
        }

        return array_values(array_unique($index_keys));
    }
}
