# Estándar de código

## Versiones y lenguaje

El estado instalado y verificado del proyecto es PHP 8.5.0, Laravel 13.35.0,
Laravel Boost 2.10.3, Pest 5.3.1, Inertia Laravel 3.5.1, Inertia Vue 3.8.0 y
Vue 3.5.43. Usa las APIs compatibles con esas versiones y consulta Laravel
Boost antes de depender de una API versionada. No uses APIs deprecadas.

Todo archivo PHP debe comenzar con `declare(strict_types=1);`. Declara tipos en
parámetros, retornos y propiedades. Las clases son `final` por defecto; usa
`readonly` para DTO y objetos de valor, enums para vocabularios cerrados,
`match`, promoción de constructor y sintaxis moderna de PHP 8.5.

## Arquitectura Laravel

Mantén un MVC limpio en el monolito:

```text
Ruta -> Controller delgado -> FormRequest -> Action
     -> Modelo
```

Cada caso de uso tiene una clase en `app/Actions/<Contexto>`. Usa Services
solo para lógica compartida. Pasa DTO readonly entre capas. Usa Policies para
autorización y Events o Notifications para efectos secundarios. Crea Jobs solo
cuando haya asincronía real. Las props de Inertia deben estar tipadas mediante
Resource o DTO y tener tipos TypeScript espejo.

## Modelos, persistencia y dominio

- Los modelos contienen relaciones, casts y scopes, no casos de uso.
- Modela estados con enums y transiciones permitidas explícitas.
- Representa dinero con enteros en centavos o un objeto de valor; nunca uses
  `float`.
- Usa eager loading para evitar N+1 y activa `preventLazyLoading` fuera de
  producción.
- Usa transacciones en casos de uso con varias escrituras.
- Las migraciones deben ser reversibles y definir claves foráneas e índices.

## SOLID y patrones

Usa Strategy para tarifas o pasarelas, Adapter para proveedores, State para
estados y Factory para construcción compleja. Define contratos en
`app/Contracts` solo para dependencias externas o intercambiables, con una
implementación real y una falsa enlazadas en `config/servicios.php`.

Un patrón solo es válido si resuelve una variación presente. Está prohibida la
sobreingeniería: no añadas repositorios genéricos sobre Eloquent, CQRS ni
capas vacías.

## Vue e Inertia

Usa `<script setup lang="ts">`, componentes pequeños y props tipadas. La lógica
de negocio vive en PHP; Vue presenta estado y coordina interacción. Usa
Wayfinder para llamar rutas y acciones de Laravel, no URLs duras. Los
componentes Vue tienen un único elemento raíz.

## Pest y calidad

- Usa `it` y `describe`, un comportamiento por prueba, datasets y factories.
- Usa `RefreshDatabase` y fakes enlazados por el contenedor; no hagas mocks de
  Eloquent.
- Usa Feature para HTTP e Inertia y Unit para Actions y Services.
- Usa `arch()` para proteger reglas de capas.
- Trabaja con TDD: rojo, verde y refactor.
- Exige 100 % de cobertura de `app/`; nunca excluyas código para esconder una
  carencia de cobertura.

## Seguridad

Valida entradas en FormRequest, declara `$fillable` explícito y aplica
Policies por rol. No escribas PII en logs. Los secretos viven únicamente en
el entorno y nunca en el repositorio, `.env`, fixtures o mensajes de commit.

## Estilo y tamaño

Usa Pint con el preset de Laravel y Larastan sin bajar el nivel. Nombra el
dominio en español y los términos técnicos en inglés; los textos de UI son en
español. Mantén cada PR por debajo de aproximadamente 400 líneas y limita cada
PR a una tarea.
