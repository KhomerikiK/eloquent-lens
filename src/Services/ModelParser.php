<?php

namespace EloquentLens\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Throwable;

class ModelParser
{
    protected array $modelPaths;
    protected string $namespace;
    protected array $excludedModels;
    protected array $parsedModels = [];

    public function __construct(array $modelPaths, string $namespace, array $excludedModels = [])
    {
        $this->modelPaths = $modelPaths;
        $this->namespace = $namespace;
        $this->excludedModels = $excludedModels;
    }

    /**
     * Parse all models and return structured data.
     */
    public function parse(): array
    {
        $this->parsedModels = [];
        $classes = $this->discoverModels();

        // Detect duplicate short names so we can disambiguate
        $shortNameCounts = [];
        foreach ($classes as $class) {
            $short = class_basename($class);
            $shortNameCounts[$short] = ($shortNameCounts[$short] ?? 0) + 1;
        }

        foreach ($classes as $class) {
            try {
                $data = $this->parseModel($class);
                if ($data) {
                    // Disambiguate duplicate short names using relative namespace
                    $key = $data['name'];
                    if (($shortNameCounts[$key] ?? 0) > 1) {
                        $relative = str_replace($this->namespace . '\\', '', $class);
                        $key = str_replace('\\', '/', $relative);
                        $data['name'] = $key;
                    }
                    $this->parsedModels[$key] = $data;
                }
            } catch (Throwable $e) {
                // Skip models that can't be parsed
                continue;
            }
        }

        // Calculate complexity scores
        foreach ($this->parsedModels as $name => &$model) {
            $model['complexity'] = $this->calculateComplexity($model);
        }

        return $this->parsedModels;
    }

    /**
     * Discover all Eloquent model classes.
     */
    protected function discoverModels(): array
    {
        $classes = [];

        foreach ($this->modelPaths as $path) {
            if (! is_dir($path)) {
                continue;
            }

            $files = File::allFiles($path);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $className = $this->getClassFromFile($file->getPathname());

                if (! $className) continue;
                if (in_array($className, $this->excludedModels)) continue;

                try {
                    if (! class_exists($className)) continue;

                    $reflection = new ReflectionClass($className);

                    if ($reflection->isAbstract()) continue;
                    if (! $reflection->isSubclassOf(Model::class)) continue;

                    $classes[] = $className;
                } catch (Throwable $e) {
                    continue;
                }
            }
        }

        return $classes;
    }

    /**
     * Get FQCN from file path.
     */
    protected function getClassFromFile(string $filePath): ?string
    {
        $contents = file_get_contents($filePath);

        if (preg_match('/namespace\s+(.+?);/', $contents, $nsMatch) &&
            preg_match('/class\s+(\w+)/', $contents, $classMatch)) {
            return $nsMatch[1] . '\\' . $classMatch[1];
        }

        return null;
    }

    /**
     * Parse a single model class.
     */
    protected function parseModel(string $class): ?array
    {
        $reflection = new ReflectionClass($class);
        $shortName = $reflection->getShortName();

        // Create instance for introspection
        try {
            $instance = $reflection->newInstanceWithoutConstructor();
        } catch (Throwable $e) {
            return null;
        }

        // Build model data resiliently — each field wrapped so one failure
        // doesn't drop the entire model from the dashboard
        $safe = function (callable $fn, $default = []) {
            try { return $fn(); } catch (Throwable $e) { return $default; }
        };

        return [
            'name'          => $shortName,
            'fqcn'          => $class,
            'namespace'     => $reflection->getNamespaceName(),
            'table'         => $safe(fn () => $this->getTableName($instance), ''),
            'traits'        => $safe(fn () => $this->getTraits($reflection)),
            'fillable'      => $safe(fn () => $this->getFillable($instance)),
            'guarded'       => $safe(fn () => $this->getGuarded($instance)),
            'hidden'        => $safe(fn () => $this->getHidden($instance)),
            'casts'         => $safe(fn () => $this->getCasts($instance)),
            'accessors'     => $safe(fn () => $this->getAccessors($reflection, $instance)),
            'mutators'      => $safe(fn () => $this->getMutators($reflection, $instance)),
            'scopes'        => $safe(fn () => $this->getScopes($reflection)),
            'globalScopes'  => $safe(fn () => $this->getGlobalScopes($reflection)),
            'relationships' => $relationships = $safe(fn () => $this->getRelationships($reflection, $instance)),
            'methods'       => $safe(fn () => $this->getCustomMethods($reflection, array_keys($relationships))),
            'observers'     => $safe(fn () => $this->getObservers($reflection)),
            'policies'      => $safe(fn () => $this->getPolicies($class)),
            'columns'       => $safe(fn () => $this->getColumnsFromModel($instance)),
            'indexes'       => [],
            'hasTimestamps' => $safe(fn () => $instance->usesTimestamps(), false),
            'hasSoftDeletes'=> in_array(SoftDeletes::class, class_uses_recursive($class)),
            'linesOfCode'   => $safe(fn () => $this->countLines($reflection), 0),
            'filePath'      => $reflection->getFileName(),
            'complexity'    => 0, // calculated later
        ];
    }

    protected function getTableName(Model $model): string
    {
        return $model->getTable();
    }

    protected function getTraits(ReflectionClass $reflection): array
    {
        $traits = [];
        foreach (class_uses_recursive($reflection->getName()) as $trait) {
            $traits[] = class_basename($trait);
        }
        return $traits;
    }

    protected function getFillable(Model $model): array
    {
        return $model->getFillable();
    }

    protected function getGuarded(Model $model): array
    {
        return $model->getGuarded();
    }

    protected function getHidden(Model $model): array
    {
        return $model->getHidden();
    }

    protected function getCasts(Model $model): array
    {
        try {
            $casts = [];

            // Read the $casts property directly via reflection
            // This gives us only what the developer explicitly defined,
            // without Laravel's auto-injected key type (e.g. id => int)
            $ref = new ReflectionClass($model);
            if ($ref->hasProperty('casts')) {
                $prop = $ref->getProperty('casts');
                $prop->setAccessible(true);
                $casts = $prop->getValue($model) ?: [];
            }

            // Also check for Laravel 11+ casts() method defined on the model itself
            if (method_exists($model, 'casts')) {
                $method = new ReflectionMethod($model, 'casts');
                if ($method->class === get_class($model)) {
                    $methodCasts = $model->casts();
                    if (is_array($methodCasts)) {
                        $casts = array_merge($casts, $methodCasts);
                    }
                }
            }

            return $casts;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Detect accessors (both legacy get*Attribute and new Attribute methods).
     */
    protected function getAccessors(ReflectionClass $reflection, Model $instance): array
    {
        $accessors = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) continue;

            $name = $method->getName();

            // Legacy: getFooAttribute
            if (preg_match('/^get(.+)Attribute$/', $name, $matches)) {
                $accessors[] = Str::snake($matches[1]);
                continue;
            }

            // New style: returns Illuminate\Database\Eloquent\Casts\Attribute
            $rt = $method->getReturnType();
            if ($rt instanceof ReflectionNamedType) {
                if ($rt->getName() === 'Illuminate\Database\Eloquent\Casts\Attribute') {
                    $accessors[] = Str::snake($name);
                }
            }
        }

        return $accessors;
    }

    /**
     * Detect mutators (both legacy set*Attribute and new Attribute methods).
     */
    protected function getMutators(ReflectionClass $reflection, Model $instance): array
    {
        $mutators = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) continue;

            $name = $method->getName();

            // Legacy: setFooAttribute
            if (preg_match('/^set(.+)Attribute$/', $name, $matches)) {
                $mutators[] = Str::snake($matches[1]);
            }
        }

        return $mutators;
    }

    /**
     * Get local scopes.
     */
    protected function getScopes(ReflectionClass $reflection): array
    {
        $scopes = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) continue;

            if (preg_match('/^scope(.+)$/', $method->getName(), $matches)) {
                $scopeName = lcfirst($matches[1]);
                $scopes[] = $scopeName;
            }
        }

        return $scopes;
    }

    /**
     * Detect global scopes from booted/boot methods.
     */
    protected function getGlobalScopes(ReflectionClass $reflection): array
    {
        $scopes = [];

        // Read the source file and look for addGlobalScope calls
        $source = file_get_contents($reflection->getFileName());

        if (preg_match_all('/addGlobalScope\(\s*(?:new\s+)?([A-Za-z\\\\]+)/', $source, $matches)) {
            foreach ($matches[1] as $scope) {
                $scopes[] = class_basename($scope);
            }
        }

        return $scopes;
    }

    /**
     * Get custom public methods defined on the model (excluding Laravel internals).
     */
    protected function getCustomMethods(ReflectionClass $reflection, array $relationshipNames = []): array
    {
        $methods = [];
        $exclude = [
            'boot', 'booted', 'booting',
            'newFactory', 'newCollection', 'newEloquentBuilder',
            'resolveRouteBinding', 'resolveChildRouteBinding', 'resolveSoftDeletableRouteBinding',
            'toSearchableArray', 'searchableAs', 'shouldBeSearchable',
            'broadcastOn', 'broadcastAs', 'broadcastWith', 'broadcastChannel',
            'prunable', 'prunableQuery',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) continue;
            if ($method->isStatic()) continue;

            $name = $method->getName();

            // Skip magic methods
            if (str_starts_with($name, '__')) continue;

            // Skip accessors / mutators
            if (preg_match('/^(get|set).+Attribute$/', $name)) continue;

            // Skip scopes
            if (preg_match('/^scope[A-Z]/', $name)) continue;

            // Skip relationship methods (already shown in Relations tab)
            if (in_array($name, $relationshipNames)) continue;

            // Skip new-style accessors (return Attribute)
            $rt = $method->getReturnType();
            if ($rt instanceof ReflectionNamedType && $rt->getName() === 'Illuminate\Database\Eloquent\Casts\Attribute') continue;

            // Skip known Laravel boilerplate
            if (in_array($name, $exclude)) continue;

            $params = [];
            foreach ($method->getParameters() as $param) {
                $p = '$' . $param->getName();
                if ($param->isOptional()) {
                    $p .= ' = ...';
                }
                $params[] = $p;
            }

            $methods[] = [
                'name'   => $name,
                'params' => '(' . implode(', ', $params) . ')',
            ];
        }

        return $methods;
    }

    /**
     * Detect Eloquent relationships via reflection.
     */
    protected function getRelationships(ReflectionClass $reflection, Model $instance): array
    {
        $relationships = [];
        $relationTypes = [
            Relations\HasOne::class,
            Relations\HasMany::class,
            Relations\BelongsTo::class,
            Relations\BelongsToMany::class,
            Relations\MorphOne::class,
            Relations\MorphMany::class,
            Relations\MorphTo::class,
            Relations\MorphToMany::class,
            Relations\MorphedByMany::class,
            Relations\HasOneThrough::class,
            Relations\HasManyThrough::class,
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName()) continue;
            if ($method->getNumberOfRequiredParameters() > 0) continue;

            // Check return type hint first (only named types, not union/intersection)
            $returnType = $method->getReturnType();
            if ($returnType instanceof ReflectionNamedType) {
                $typeName = $returnType->getName();
                foreach ($relationTypes as $relationType) {
                    if ($typeName === $relationType || is_subclass_of($typeName, $relationType)) {
                        $relData = $this->resolveRelationship($method, $instance, $relationType);
                        if ($relData) {
                            $relationships[$method->getName()] = $relData;
                        }
                        continue 2;
                    }
                }
            }

            // Fallback: try to call the method and check return type
            try {
                $result = $method->invoke($instance);
                foreach ($relationTypes as $relationType) {
                    if ($result instanceof $relationType) {
                        $relationships[$method->getName()] = $this->extractRelationData($result, $relationType);
                        break;
                    }
                }
            } catch (Throwable $e) {
                // Skip methods that can't be invoked
                continue;
            }
        }

        return $relationships;
    }

    protected function resolveRelationship(ReflectionMethod $method, Model $instance, string $type): ?array
    {
        try {
            $result = $method->invoke($instance);

            if ($result instanceof Relations\Relation) {
                return $this->extractRelationData($result, $type);
            }
        } catch (Throwable $e) {
            // Parse source code as fallback
            return $this->parseRelationFromSource($method, $type);
        }

        return null;
    }

    protected function extractRelationData($relation, string $type): array
    {
        $relatedModel = null;
        try {
            $relatedModel = class_basename(get_class($relation->getRelated()));
        } catch (Throwable $e) {}

        $data = [
            'type'  => $this->getRelationTypeName($type),
            'model' => $relatedModel,
        ];

        // Pivot table for many-to-many
        if ($relation instanceof Relations\BelongsToMany) {
            try {
                $data['pivot'] = $relation->getTable();
            } catch (Throwable $e) {}
        }

        return $data;
    }

    protected function parseRelationFromSource(ReflectionMethod $method, string $type): ?array
    {
        $source = file_get_contents($method->getDeclaringClass()->getFileName());
        $methodName = $method->getName();

        // Find the method body and extract the related model
        if (preg_match('/function\s+' . $methodName . '\s*\([^)]*\)[^{]*\{([^}]+)\}/s', $source, $matches)) {
            $body = $matches[1];

            // Look for Model::class references
            if (preg_match('/([A-Z][A-Za-z]+)::class/', $body, $modelMatch)) {
                return [
                    'type'  => $this->getRelationTypeName($type),
                    'model' => $modelMatch[1],
                ];
            }
        }

        return null;
    }

    protected function getRelationTypeName(string $class): string
    {
        return match ($class) {
            Relations\HasOne::class         => 'hasOne',
            Relations\HasMany::class        => 'hasMany',
            Relations\BelongsTo::class      => 'belongsTo',
            Relations\BelongsToMany::class  => 'belongsToMany',
            Relations\MorphOne::class       => 'morphOne',
            Relations\MorphMany::class      => 'morphMany',
            Relations\MorphTo::class        => 'morphTo',
            Relations\MorphToMany::class    => 'morphToMany',
            Relations\MorphedByMany::class  => 'morphedByMany',
            Relations\HasOneThrough::class  => 'hasOneThrough',
            Relations\HasManyThrough::class => 'hasManyThrough',
            default => class_basename($class),
        };
    }

    /**
     * Detect observers registered on the model.
     */
    protected function getObservers(ReflectionClass $reflection): array
    {
        $observers = [];
        $source = file_get_contents($reflection->getFileName());

        // Check for observe() calls in boot/booted
        if (preg_match_all('/(?:static::observe|self::observe)\(\s*(?:new\s+)?([A-Za-z\\\\]+)/', $source, $matches)) {
            foreach ($matches[1] as $observer) {
                $observers[] = class_basename($observer);
            }
        }

        // Check for event method overrides (creating, created, updating, etc.)
        $eventMethods = ['creating', 'created', 'updating', 'updated', 'deleting', 'deleted', 'saving', 'saved', 'restoring', 'restored'];

        foreach ($eventMethods as $event) {
            if (preg_match('/static::' . $event . '\s*\(/', $source)) {
                $observers[] = $event . ' (inline)';
            }
        }

        return $observers;
    }

    /**
     * Try to find a policy for this model.
     */
    protected function getPolicies(string $modelClass): array
    {
        $policies = [];
        $modelName = class_basename($modelClass);
        $policyClass = str_replace('Models\\' . $modelName, 'Policies\\' . $modelName . 'Policy', $modelClass);

        if (class_exists($policyClass)) {
            $policies[] = class_basename($policyClass);
        }

        return $policies;
    }

    /**
     * Get columns from model properties (no DB queries).
     * Merges fillable, guarded, hidden, and cast keys for a complete picture.
     */
    protected function getColumnsFromModel(Model $model): array
    {
        $columns = array_unique(array_merge(
            $model->getFillable(),
            $model->getGuarded() !== ['*'] ? $model->getGuarded() : [],
            $model->getHidden(),
            array_keys($this->getCasts($model)),
        ));

        // Always include 'id' if not already present
        if (!in_array('id', $columns)) {
            array_unshift($columns, 'id');
        }

        // Add timestamp columns if the model uses them
        if ($model->usesTimestamps()) {
            foreach (['created_at', 'updated_at'] as $ts) {
                if (!in_array($ts, $columns)) {
                    $columns[] = $ts;
                }
            }
        }

        // Add soft delete column if applicable
        if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
            if (!in_array('deleted_at', $columns)) {
                $columns[] = 'deleted_at';
            }
        }

        return $columns;
    }

    /**
     * Count lines of code in the model file.
     */
    protected function countLines(ReflectionClass $reflection): int
    {
        try {
            return count(file($reflection->getFileName()));
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Calculate a complexity score (0-100).
     */
    protected function calculateComplexity(array $model): int
    {
        $score = 0;

        $score += count($model['relationships']) * 6;
        $score += count($model['scopes']) * 3;
        $score += count($model['globalScopes']) * 8;
        $score += count($model['accessors']) * 2;
        $score += count($model['mutators']) * 2;
        $score += count($model['observers']) * 5;
        $score += count($model['casts']) * 1;
        $score += count($model['traits']) * 2;
        $score += min($model['linesOfCode'] / 10, 20);

        return min((int) $score, 100);
    }
}
