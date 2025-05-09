<?php
// app/Traits/LoadsRelations.php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LoadsRelations
{
    public function extractValidRelations(string $modelClass, $request): array
    {
        $relations = $request->query('query')
            ? array_map('trim', explode(',', $request->query('query')))
            : [];

        return array_filter($relations, function ($relation) use ($modelClass) {
            return $this->isValidNestedRelation($modelClass, $relation);
        });
    }

    protected function isValidNestedRelation(string $modelClass, string $relation): bool
    {
        $parts = explode('.', $relation);
        $currentModel = $modelClass;

        foreach ($parts as $part) {
            if (!method_exists($currentModel, $part)) {
                return false;
            }

            // Get the relation instance to check the related model
            $relationMethod = (new $currentModel)->$part();
            $relatedModel = $relationMethod->getRelated();

            // Update current model for next iteration
            $currentModel = get_class($relatedModel);
        }

        return true;
    }
}
