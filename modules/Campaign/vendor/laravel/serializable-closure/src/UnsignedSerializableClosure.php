<?php

namespace Laravel\SerializableClosure;

use Closure;
use Laravel\SerializableClosure\Contracts\Serializable;

class UnsignedSerializableClosure
{
    /**
     * The closure's serializable.
     *
     * @var Serializable
     */
    protected $serializable;

    /**
     * Creates a new serializable closure instance.
     *
     * @return void
     */
    public function __construct(Closure $closure)
    {
        $this->serializable = new Serializers\Native($closure);
    }

    /**
     * Resolve the closure with the given arguments.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->serializable, func_get_args());
    }

    /**
     * Gets the closure.
     *
     * @return Closure
     */
    public function getClosure()
    {
        return $this->serializable->getClosure();
    }

    /**
     * Get the serializable representation of the closure.
     *
     * @return array{serializable: Serializable}
     */
    public function __serialize()
    {
        return [
            'serializable' => $this->serializable,
        ];
    }

    /**
     * Restore the closure after serialization.
     *
     * @param  array{serializable: Serializable}  $data
     * @return void
     */
    public function __unserialize($data)
    {
        $this->serializable = $data['serializable'];
    }
}
