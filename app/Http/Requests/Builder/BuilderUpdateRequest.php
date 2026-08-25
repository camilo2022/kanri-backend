<?php

namespace App\Http\Requests\Builder;

use App\Models\Category;
use App\Models\Color;
use App\Models\Piece;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Size;
use App\Models\Subcategory;
use App\Models\TechnicalSheet;
use App\Models\Trademark;
use App\Models\Variant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Cache;

class BuilderUpdateRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Error de validación.',
            'attributes' => $this->attributes(),
            'errors' => $validator->errors()
        ], 422));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => ['required'],
            'trademark_id' => ['required', 'array', 'min:1'],
            'trademark_id.*' => ['exists:subitems,id,item_id,' . Trademark::ITEM_ID . ',deleted_at,NULL'],
            'category_id' => ['nullable', 'array'],
            'category_id.*' => ['exists:subitems,id,item_id,' . Category::ITEM_ID . ',deleted_at,NULL'],
            'subcategory_id' => ['nullable', 'array'],
            'subcategory_id.*' => ['exists:subitems,id,item_id,' . Subcategory::ITEM_ID . ',deleted_at,NULL'],
            'percentages' => ['required', 'array'],
            'percentages.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'array' => 'Debe ser um array',
            'numeric' => 'Debe ser un dato numerico',
            'min' => 'Debe ser minimo :min',
            'max' => 'Debe ser minimo :max'
        ];
    }

    public function attributes(): array
    {
        return [
            'trademark_id' => 'Marcas',
            'category_id' => 'Categorías',
            'subcategory_id' => 'Subcategorías',
            'percentages' => 'Porcentajes'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $percentages = $this->input('percentages', []);
            $trademark_ids = $this->input('trademark_id', []);
            $category_ids = $this->input('category_id', []);
            $subcategory_ids = $this->input('subcategory_id', []);

            $builder_id = $this->route('uuid');

            if (!Cache::has("curve_builder:{$builder_id}")) {
                $validator->errors()->add(
                    'builder',
                    'El constructor no existe.'
                );
            }

            $total = collect($percentages)
                ->sum(fn($percentage) => (float) $percentage);

            if ($total != 100) {
                $validator->errors()->add(
                    'percentages',
                    'La suma de los porcentajes debe ser exactamente 100%.'
                );
            }


            if (!empty($subcategory_ids) && empty($category_ids)) {
                $validator->errors()->add(
                    'subcategory_ids',
                    'No puedes seleccionar subcategorías sin seleccionar al menos una categoría.'
                );
            }

            if (!empty($subcategory_ids) && !empty($category_ids)) {
                foreach ($subcategory_ids as $subcategory_id) {
                    $subcategory = Subcategory::with('category')->find($subcategory_id);
                    if (!in_array($subcategory->category->first()->id, $category_ids)) {
                        $validator->errors()->add(
                            'subcategory_ids',
                            'Una o más subcategorías no pertenecen a las categorías seleccionadas.'
                        );
                        break;
                    }
                }
            }

            foreach ($percentages as $key => $value) {

                $size = Size::with('trademark')->find($key);

                if (!$size) {
                    $validator->errors()->add(
                        'percentages',
                        "La talla con ID {$key} no existe."
                    );

                    break;
                }

                $size_trademark_ids = $size->trademark
                    ->pluck('id')
                    ->map(fn($id) => (string) $id)
                    ->toArray();

                $selected_trademark_ids = array_map(
                    'strval',
                    $trademark_ids
                );

                $has_valid_trademark = !empty(array_intersect(
                    $size_trademark_ids,
                    $selected_trademark_ids
                ));

                if (!$has_valid_trademark) {
                    $validator->errors()->add(
                        'percentages',
                        'Una o más tallas no pertenecen a las marcas seleccionadas.'
                    );

                    break;
                }
            }

            if (empty($category_ids)) {

                foreach ($trademark_ids as $trademark_id) {

                    $cache_key = "curve_builder:index:{$trademark_id}";

                    $existing_builder_id = Cache::get($cache_key);

                    if ($existing_builder_id && $existing_builder_id !== $builder_id) {
                        $validator->errors()->add(
                            'trademark_id',
                            'Ya existe un constructor para una de las marcas seleccionadas.'
                        );

                        break;
                    }
                }
            } elseif (empty($subcategory_ids)) {

                foreach ($trademark_ids as $trademark_id) {
                    foreach ($category_ids as $category_id) {
                        $cache_key =
                            "curve_builder:index:"
                            . "{$trademark_id}:"
                            . "{$category_id}";

                        $existing_builder_id = Cache::get($cache_key);

                        if ($existing_builder_id && $existing_builder_id !== $builder_id) {
                            $validator->errors()->add(
                                'category_id',
                                'Ya existe un constructor para una de las combinaciones de marca y categoría seleccionadas.'
                            );

                            break 2;
                        }
                    }
                }
            } else {
                $subcategories = Subcategory::with('category')
                    ->whereIn('id', $subcategory_ids)
                    ->get();

                foreach ($trademark_ids as $trademark_id) {

                    foreach ($subcategories as $subcategory) {

                        $category_id = $subcategory->category
                            ->first()
                            ->id;

                        $cache_key =
                            "curve_builder:index:"
                            . "{$trademark_id}:"
                            . "{$category_id}:"
                            . "{$subcategory->id}";

                        $existing_builder_id = Cache::get($cache_key);

                        if ($existing_builder_id && $existing_builder_id !== $builder_id) {

                            $validator->errors()->add(
                                'subcategory_id',
                                'Ya existe un constructor para una de las combinaciones seleccionadas.'
                            );

                            break 2;
                        }
                    }
                }
            }
        });
    }

    protected function prepareForValidation()
    {
        $this->merge(['uuid' => $this->route('uuid')]);
    }
}
