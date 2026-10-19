<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rule;

final class ReglasLicenciaConduccion
{
    /**
     * @return list<string>
     */
    public static function categorias(): array
    {
        $categorias = config('entrega.categorias_validas');

        if (! is_array($categorias)) {
            return [];
        }

        return array_values(array_filter($categorias, is_string(...)));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'licencia_numero' => ['required', 'string', 'max:50'],
            'licencia_categoria' => ['required', 'string', Rule::in(self::categorias())],
            'licencia_vencimiento' => ['required', 'date', 'after:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'licencia_numero.required' => 'Indica el número de la licencia de conducción.',
            'licencia_numero.string' => 'El número de la licencia no es válido.',
            'licencia_numero.max' => 'El número de la licencia no puede superar 50 caracteres.',
            'licencia_categoria.required' => 'Selecciona la categoría de la licencia.',
            'licencia_categoria.string' => 'La categoría de la licencia no es válida.',
            'licencia_categoria.in' => 'La categoría de la licencia no es válida.',
            'licencia_vencimiento.required' => 'Indica la fecha de vencimiento de la licencia.',
            'licencia_vencimiento.date' => 'La fecha de vencimiento de la licencia no es válida.',
            'licencia_vencimiento.after' => 'La fecha de vencimiento de la licencia debe ser futura.',
        ];
    }
}
