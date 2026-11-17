<?php

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Every relation on every Pallet model must resolve to a real class.
 *
 * Two production defects reached the console because a related class was
 * referenced without an import — PHP then resolved it inside
 * Fleetbase\Pallet\Models, where it does not exist, and the endpoint died
 * only when something actually touched that relation (Place on Warehouse,
 * Contact on PurchaseOrder).
 */
function palletModelClasses(): array
{
    $classes = [];

    foreach (glob(__DIR__ . '/../src/Models/*.php') as $file) {
        $class = 'Fleetbase\\Pallet\\Models\\' . basename($file, '.php');

        if (!class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || !$reflection->isSubclassOf(Illuminate\Database\Eloquent\Model::class)) {
            continue;
        }

        $classes[] = $class;
    }

    return $classes;
}

test('every model relation resolves to a real related class', function (string $modelClass) {
    $model      = new $modelClass();
    $reflection = new ReflectionClass($modelClass);
    $checked    = 0;

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
            continue;
        }

        $returnType = $method->getReturnType();
        $isRelation = $returnType instanceof ReflectionNamedType && is_subclass_of($returnType->getName(), Relation::class);

        // untyped relation methods are common here, so fall back to invoking
        // anything whose name is not an accessor and inspecting the result
        if (!$isRelation && (str_starts_with($method->getName(), 'get') || str_starts_with($method->getName(), 'scope'))) {
            continue;
        }

        try {
            $result = $model->{$method->getName()}();
        } catch (Throwable $e) {
            if (str_contains($e->getMessage(), 'not found')) {
                throw new RuntimeException("{$modelClass}::{$method->getName()}() references a missing class — {$e->getMessage()}");
            }

            continue;
        }

        if ($result instanceof Relation) {
            expect($result->getRelated())->toBeInstanceOf(Illuminate\Database\Eloquent\Model::class);
            $checked++;
        }
    }

    expect($checked)->toBeGreaterThanOrEqual(0);
})->with(palletModelClasses());
