<?php

namespace Illuminate\Contracts\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ModelIdentifier
{
    /**
     * The class name of the model.
     *
     * @var class-string<Model>
     */
    public $class;

    /**
     * The unique identifier of the model.
     *
     * This may be either a single ID or an array of IDs.
     *
     * @var mixed
     */
    public $id;

    /**
     * The relationships loaded on the model.
     *
     * @var array
     */
    public $relations;

    /**
     * The connection name of the model.
     *
     * @var string|null
     */
    public $connection;

    /**
     * The class name of the model collection.
     *
     * @var class-string<Collection>|null
     */
    public $collectionClass;

    /**
     * Create a new model identifier.
     *
     * @param  class-string<Model>  $class
     * @param  mixed  $id
     * @param  mixed  $connection
     */
    public function __construct($class, $id, array $relations, $connection)
    {
        $this->id = $id;
        $this->class = $class;
        $this->relations = $relations;
        $this->connection = $connection;
    }

    /**
     * Specify the collection class that should be used when serializing / restoring collections.
     *
     * @param  class-string<Collection>  $collectionClass
     * @return $this
     */
    public function useCollectionClass(?string $collectionClass)
    {
        $this->collectionClass = $collectionClass;

        return $this;
    }
}
