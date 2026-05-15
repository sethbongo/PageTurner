<?php

namespace App\Services;

use Illuminate\Http\Request;

class FieldFilteringService
{
    /**
     * Filter fields from data based on request query
     */
    public function filterFields($data, Request $request = null)
    {
        if ($request === null) {
            $request = request();
        }

        // Check if fields parameter is provided
        $fieldsParam = $request->query('fields');

        if (!$fieldsParam || !config('api.field_filtering')) {
            return $data;
        }

        // Parse fields parameter (comma-separated)
        $fields = array_map('trim', explode(',', $fieldsParam));

        // Filter the data
        if (is_array($data)) {
            return $this->filterArray($data, $fields);
        }

        if (is_object($data)) {
            return $this->filterObject($data, $fields);
        }

        return $data;
    }

    /**
     * Filter array to include only specified fields
     */
    protected function filterArray(array $data, array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $result[$field] = $data[$field];
            }
        }

        return $result;
    }

    /**
     * Filter object to include only specified fields
     */
    protected function filterObject($data, array $fields)
    {
        $array = (array) $data;
        $filtered = $this->filterArray($array, $fields);

        return (object) $filtered;
    }

    /**
     * Filter a collection of items
     */
    public function filterCollection($items, Request $request = null)
    {
        if ($request === null) {
            $request = request();
        }

        if (!config('api.field_filtering')) {
            return $items;
        }

        return $items->map(fn($item) => $this->filterFields($item, $request));
    }

    /**
     * Get allowed fields for a model (from $fillable or $visible)
     */
    public function getAllowedFields($model): array
    {
        if (isset($model->visible) && !empty($model->visible)) {
            return $model->visible;
        }

        if (isset($model->fillable) && !empty($model->fillable)) {
            return $model->fillable;
        }

        // Return all attributes if no restriction
        return array_keys($model->getAttributes());
    }

    /**
     * Validate requested fields are allowed
     */
    public function validateFields(array $requestedFields, $model): array
    {
        $allowedFields = $this->getAllowedFields($model);

        return array_filter($requestedFields, fn($field) => in_array($field, $allowedFields));
    }
}
