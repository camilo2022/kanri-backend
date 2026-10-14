<?php

namespace App\Models;

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

    protected $table = 'movements';

    protected $fillable = [
        'model_id',
        'model_type',
        'production_order_detail_id',
        'status',
        'settings',
    ];

    protected $auditInclude = [
        'model_id',
        'model_type',
        'production_order_detail_id',
        'status',
        'settings',
    ];

    protected $casts = [
        'settings' => 'object',
    ];

    public function production_order_detail(): BelongsTo
    {
        return $this->belongsTo(ProductionOrderDetail::class);
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
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
        $terms = preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY);

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
