<?php

namespace App\Support;

/**
 * Unifica los mensajes de validación de toda la API.
 *
 * El texto de cada regla estándar sale SIEMPRE de `lang/es/validation.php`. Si un FormRequest redefine en su
 * `messages()` una regla de forma genérica ('required' => '…'), aquí se descarta para que la misma regla no diga cosas
 * distintas según el formulario.
 *
 * Se respetan los mensajes personalizados de un campo concreto ('person_id.required' => 'Es obligatorio identificar a
 * la persona.'), el `regex` genérico (cada formato es distinto) y las reglas propias que no están en `lang`.
 */
class ValidationMessages
{
    /** Reglas cuyo texto genérico es el mismo en todos los formularios. */
    private const STANDARD = [
        'required', 'required_if', 'required_unless', 'required_with', 'required_with_all', 'required_without',
        'required_without_all', 'required_array_keys', 'required_if_accepted', 'filled', 'present', 'string', 'numeric',
        'integer', 'boolean', 'array', 'date', 'date_format', 'date_equals', 'email', 'max', 'min', 'between', 'size',
        'digits', 'digits_between', 'gt', 'gte', 'lt', 'lte', 'unique', 'exists', 'uppercase', 'lowercase', 'confirmed',
        'same', 'different', 'file', 'image', 'mimes', 'mimetypes', 'distinct', 'before', 'before_or_equal', 'after',
        'after_or_equal', 'url', 'uuid', 'json', 'in', 'not_in', 'alpha', 'alpha_num', 'alpha_dash', 'accepted',
        'declined', 'min_digits', 'max_digits',
    ];

    private const SIZE_RULES = ['between', 'gt', 'gte', 'lt', 'lte', 'max', 'min', 'size'];

    private const SIZE_TYPES = ['array', 'file', 'numeric', 'string'];

    public static function clean(array $messages): array
    {
        foreach (array_keys($messages) as $key) {
            if (self::isGenericStandard((string) $key)) {
                unset($messages[$key]);
            }
        }

        return $messages;
    }

    private static function isGenericStandard(string $key): bool
    {
        $parts = explode('.', $key);

        // 'max.string' es la regla `max` para texto (genérica); 'name.max.string' es `max` del campo `name`
        $isSizeType = count($parts) === 2 && in_array($parts[0], self::SIZE_RULES, true)
            && in_array($parts[1], self::SIZE_TYPES, true);

        return (count($parts) === 1 || $isSizeType) && in_array($parts[0], self::STANDARD, true);
    }
}
