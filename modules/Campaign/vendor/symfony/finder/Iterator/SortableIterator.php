<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Finder\Iterator;

/**
 * SortableIterator applies a sort on a given Iterator.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @implements \IteratorAggregate<string, \SplFileInfo>
 */
class SortableIterator implements \IteratorAggregate
{
    public const SORT_BY_NONE = 0;

    public const SORT_BY_NAME = 1;

    public const SORT_BY_TYPE = 2;

    public const SORT_BY_ACCESSED_TIME = 3;

    public const SORT_BY_CHANGED_TIME = 4;

    public const SORT_BY_MODIFIED_TIME = 5;

    public const SORT_BY_NAME_NATURAL = 6;

    public const SORT_BY_NAME_CASE_INSENSITIVE = 7;

    public const SORT_BY_NAME_NATURAL_CASE_INSENSITIVE = 8;

    public const SORT_BY_EXTENSION = 9;

    public const SORT_BY_SIZE = 10;

    /** @var \Traversable<string, \SplFileInfo> */
    private \Traversable $iterator;

    private \Closure|int $sort;

    /**
     * @param  \Traversable<string, \SplFileInfo>  $iterator
     * @param  int|callable  $sort  The sort type (SORT_BY_NAME, SORT_BY_TYPE, or a PHP callback)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, int|callable $sort, bool $reverseOrder = false)
    {
        $this->iterator = $iterator;
        $order = $reverseOrder ? -1 : 1;

        if ($sort === self::SORT_BY_NAME) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * strcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
        } elseif ($sort === self::SORT_BY_NAME_NATURAL) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * strnatcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
        } elseif ($sort === self::SORT_BY_NAME_CASE_INSENSITIVE) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * strcasecmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
        } elseif ($sort === self::SORT_BY_NAME_NATURAL_CASE_INSENSITIVE) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * strnatcasecmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
        } elseif ($sort === self::SORT_BY_TYPE) {
            $this->sort = static function (\SplFileInfo $a, \SplFileInfo $b) use ($order) {
                if ($a->isDir() && $b->isFile()) {
                    return -$order;
                } elseif ($a->isFile() && $b->isDir()) {
                    return $order;
                }

                return $order * strcmp($a->getRealPath() ?: $a->getPathname(), $b->getRealPath() ?: $b->getPathname());
            };
        } elseif ($sort === self::SORT_BY_ACCESSED_TIME) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * ($a->getATime() - $b->getATime());
        } elseif ($sort === self::SORT_BY_CHANGED_TIME) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * ($a->getCTime() - $b->getCTime());
        } elseif ($sort === self::SORT_BY_MODIFIED_TIME) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * ($a->getMTime() - $b->getMTime());
        } elseif ($sort === self::SORT_BY_EXTENSION) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * strnatcmp($a->getExtension(), $b->getExtension());
        } elseif ($sort === self::SORT_BY_SIZE) {
            $this->sort = static fn (\SplFileInfo $a, \SplFileInfo $b) => $order * ($a->getSize() - $b->getSize());
        } elseif ($sort === self::SORT_BY_NONE) {
            $this->sort = $order;
        } elseif (\is_callable($sort)) {
            $this->sort = $reverseOrder ? static fn (\SplFileInfo $a, \SplFileInfo $b) => -$sort($a, $b) : $sort(...);
        } else {
            throw new \InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.');
        }
    }

    public function getIterator(): \Traversable
    {
        if ($this->sort === 1) {
            return $this->iterator;
        }

        $array = iterator_to_array($this->iterator, true);

        if ($this->sort === -1) {
            $array = array_reverse($array);
        } else {
            uasort($array, $this->sort);
        }

        return new \ArrayIterator($array);
    }
}
