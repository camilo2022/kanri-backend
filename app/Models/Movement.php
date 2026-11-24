<?php

namespace App\Models;

use App\Traits\SortableByRelation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Auditable as Auditing;
use OwenIt\Auditing\Contracts\Auditable;

class Movement extends Model implements Auditable
{
    use Auditing;
    use SortableByRelation;

    protected $table = 'movements';

    protected $fillable = [
        'model_id',
        'model_type',
        'production_order_detail_id',
        'status',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'production_order_detail_id',
        'status',
    ];

    public function production_order_detail(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderDetail::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function movement_details_input(): HasMany
    {
        return $this->hasMany(MovementDetail::class);
    }

    public function movement_details_output(): HasMany
    {
        return $this->hasMany(MovementDetail::class, 'model_id')
            ->where('model_type', Movement::class);
    }

    public static function sortable_columns(): array
    {
        $sheet = 'production_order_detail.production_order.technical_sheet';

        return [
            'id' => 'id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'status' => 'status',
            'trademark' => "{$sheet}.product.trademark.name",
            'collection' => "{$sheet}.collection.name",
            'garment_type' => "{$sheet}.garment_type.name",
            'reference' => ["{$sheet}.product.code", 'production_order_detail.production_order.cut'],
            'color' => "{$sheet}.color.name",
            'category' => "{$sheet}.product.subcategory.category.name",
            'destination' => 'production_order_detail.destination',
            'process' => 'model:Process.name',
        ];
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        $search = trim((string) $search);

        if ($search === '') {
            return $query;
        }

        // BÚSQUEDAS COMBINADAS CON GUION
        if (str_contains($search, '-')) {

            [$code, $value] = array_pad(explode('-', $search, 2), 2, null);

            $code = trim($code);
            $value = trim($value);

            if ($code !== '' && $value !== '') {

                return $query->where(function (Builder $q) use ($code, $value) {

                    // REFERENCIA + CORTE
                    $q->whereHas('production_order_detail', function (Builder $detail) use ($code, $value) {
                        $detail->whereHasMorph('model', [Product::class], function (Builder $product) use ($code) {
                            $product->where('code', 'LIKE', "%{$code}%");
                        });
                        $detail->whereHas('production_order', function (Builder $order) use ($value) {
                            $order->where('cut', 'LIKE', "%{$value}%");
                        });
                    })

                    // CÓDIGO + NOMBRE DE COLECCIÓN
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($code, $value) {

                            $detail->whereHas('production_order', function (Builder $order) use ($code, $value) {

                                $order->whereHas('technical_sheet', function (Builder $sheet) use ($code, $value) {

                                    $sheet->whereHas('collection', function (Builder $collection) use ($code, $value) {

                                        $collection->where('settings->code', 'LIKE', "%{$code}%")
                                            ->where('name', 'LIKE', "%{$value}%");

                                    });
                                });
                            });
                        })

                    // CÓDIGO + NOMBRE DE TIPO DE PRENDA
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($code, $value) {
                            $detail->whereHas('production_order', function (Builder $order) use ($code, $value) {
                                $order->whereHas('technical_sheet', function (Builder $sheet) use ($code, $value) {
                                    $sheet->whereHas('garment_type', function (Builder $type) use ($code, $value) {
                                        $type->where('settings->code', 'LIKE', "%{$code}%")
                                            ->where('name', 'LIKE', "%{$value}%");
                                    });
                                });
                            });
                        })

                    // CÓDIGO + NOMBRE DE COLOR
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($code, $value) {
                            $detail->whereHas('production_order', function (Builder $order) use ($code, $value) {
                                $order->whereHas('technical_sheet', function (Builder $sheet) use ($code, $value) {
                                    $sheet->whereHas('color', function (Builder $color) use ($code, $value) {
                                        $color->where('settings->code', 'LIKE', "%{$code}%")
                                            ->where('name', 'LIKE', "%{$value}%");
                                    });
                                });
                            });
                        });

                });
            }
        }

        // BÚSQUEDA NORMAL POR TÉRMINOS
        $terms = [preg_replace('/\s+/', ' ', $search)];

        return $query->where(function (Builder $q) use ($terms) {

            foreach ($terms as $term) {

                $q->where(function (Builder $sq) use ($term) {

                    // REFERENCIA
                    $sq->whereHas('production_order_detail', function (Builder $detail) use ($term) {
                        $detail->whereHasMorph('model', [Product::class], function (Builder $product) use ($term) {
                            $product->where('code', 'LIKE', "%{$term}%");
                        });
                    })

                    // CORTE
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->whereHas('production_order', function (Builder $order) use ($term) {
                                $order->where('cut', 'LIKE', "%{$term}%");
                            });
                        })

                    // MARCA
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->whereHasMorph('model', [Product::class], function (Builder $product) use ($term) {
                                $product->whereHas('trademark', function (Builder $trademark) use ($term) {
                                    $trademark->where('name', 'LIKE', "%{$term}%");
                                });
                            });
                        })

                    // COLECCIÓN
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->whereHas('production_order', function (Builder $order) use ($term) {
                                $order->whereHas('technical_sheet', function (Builder $sheet) use ($term) {
                                    $sheet->whereHas('collection', function (Builder $collection) use ($term) {
                                        $collection->where('name', 'LIKE', "%{$term}%")
                                            ->orWhere('settings->code', 'LIKE', "%{$term}%");
                                    });
                                });
                            });
                        })

                    // TIPO DE PRENDA
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->whereHas('production_order', function (Builder $order) use ($term) {
                                $order->whereHas('technical_sheet', function (Builder $sheet) use ($term) {
                                    $sheet->whereHas('garment_type', function (Builder $type) use ($term) {
                                        $type->where('name', 'LIKE', "%{$term}%")
                                            ->orWhere('settings->code', 'LIKE', "%{$term}%");
                                    });
                                });
                            });
                        })

                    // COLOR
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->whereHas('production_order', function (Builder $order) use ($term) {
                                $order->whereHas('technical_sheet', function (Builder $sheet) use ($term) {
                                    $sheet->whereHas('color', function (Builder $color) use ($term) {
                                        $color->where('name', 'LIKE', "%{$term}%")
                                            ->orWhere('settings->code', 'LIKE', "%{$term}%");
                                    });
                                });
                            });
                        })

                    // CATEGORÍA
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->whereHasMorph('model', [Product::class], function (Builder $product) use ($term) {
                                $product->whereHas('subcategory', function (Builder $subcategory) use ($term) {
                                    $subcategory->whereHas('category', function (Builder $category) use ($term) {
                                        $category->where('name', 'LIKE', "%{$term}%");
                                    });
                                });
                            });
                        })

                    // DESTINO
                        ->orWhereHas('production_order_detail', function (Builder $detail) use ($term) {
                            $detail->where('destination', 'LIKE', "%{$term}%");
                        })

                    // PROCESO
                        ->orWhereHasMorph('model', [Process::class], function (Builder $detail) use ($term) {
                            $detail->where('name', 'LIKE', "%{$term}%");
                        });
                });
            }
        });
    }
}
