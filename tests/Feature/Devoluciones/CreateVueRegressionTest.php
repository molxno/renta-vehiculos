<?php

declare(strict_types=1);

it('serializes the return cost preview damages with indexed query arrays', function () {
    $create = (string) file_get_contents(resource_path('js/pages/Devoluciones/Create.vue'));

    expect($create)->toContain('function actualizarCosto')
        ->and($create)->toContain("queryStringArrayFormat: 'indices'")
        ->and($create)->toContain('forceFormData: true');
});

it('formats the contractual end date and time instead of rendering the raw ISO value', function () {
    $create = (string) file_get_contents(resource_path('js/pages/Devoluciones/Create.vue'));

    expect($create)->toMatch(
        '/Fin contractual<\/dt>\s*<dd[^>]*>\s*\{\{\s*formatFechaHora\(reservation\.fin\)\s*\}\}/',
    );
});
