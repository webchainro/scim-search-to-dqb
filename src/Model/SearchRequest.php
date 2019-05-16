<?php

namespace Webchain\ScimFilterToDqb\Model;

use Assert\Assertion;
use Psr\Http\Message\ServerRequestInterface;
use Tmilos\ScimFilterParser\Ast\AttributePath;

/**
 * @see https://tools.ietf.org/html/rfc7644#section-3.4.3
 * Class SearchRequest
 * @package Webchain\ScimFilterToDqb\Model
 */
class SearchRequest
{
    const SORT_ASCENDING = 'ascending';
    const SORT_DESCENDING = 'descending';

    /** @var string|null */
    private $filter;
    /** @var AttributePath|null */
    private $sortBy;
    /** @var string|null */
    private $sortOrder;
    /** @var int */
    private $startIndex = 1;
    /** @var int|null */
    private $count;

    public static function fromRequest(ServerRequestInterface $request): SearchRequest
    {
        $parameters = [];
        if ($request->getMethod() === 'POST' && self::endsWith((string)$request->getUri(), '.search')) {
            $parameters = $request->getParsedBody();
        }

        if ($request->getMethod() === 'GET') {
            $parameters = $request->getQueryParams();
        }

        return self::fromArray($parameters);
    }

    /**
     * @param $parameters
     * @return SearchRequest
     */
    public static function fromArray(array $parameters = []): SearchRequest
    {
        $searchRequest = new static();
        if (isset($parameters['filter'])) {
            $searchRequest->setFilter($parameters['filter']);
        }
        if (isset($parameters['sortBy'])) {
            $sorBy = $parameters['sortBy'];
            $sorBy = is_string($sorBy) ? AttributePath::fromString($sorBy): $sorBy;
            $searchRequest->setSortBy($sorBy);
        }
        if (isset($parameters['sortOrder'])) {
            $searchRequest->setSortOrder($parameters['sortOrder']);
        }
        if (isset($parameters['startIndex'])) {
            $searchRequest->setStartIndex(intval($parameters['startIndex']));
        }
        if (isset($parameters['count'])) {
            $searchRequest->setCount(intval($parameters['count']));
        }

        return $searchRequest;
    }

    /**
     * @return mixed
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return mixed
     */
    public function getStartIndex()
    {
        return $this->startIndex;
    }

    /**
     * @return mixed
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $startIndex
     */
    public function setStartIndex(int $startIndex): void
    {
        $this->startIndex = $startIndex > 1 ? $startIndex : 1;
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count > 0 ? $count : 0;
    }

    /**
     * @return null|AttributePath
     */
    public function getSortBy(): ?AttributePath
    {
        return $this->sortBy;
    }

    /**
     * @param AttributePath $sortBy
     */
    public function setSortBy(AttributePath $sortBy): void
    {
        $this->sortBy = $sortBy;
    }

    /**
     * @return string
     */
    public function getSortOrder(): ?string
    {
        return $this->sortOrder ?? self::SORT_ASCENDING;
    }

    /**
     * @param string $sortOrder
     */
    public function setSortOrder(string $sortOrder): void
    {
        Assertion::choice($sortOrder, [self::SORT_ASCENDING, self::SORT_DESCENDING], "Sort order $sortOrder not supported");
        $this->sortOrder = $sortOrder;
    }

    public function hasFilter(): bool
    {
        return isset($this->filter);
    }

    public function hasSortBy(): bool
    {
        return isset($this->sortBy);
    }

    public function hasStartIndex(): bool
    {
        return isset($this->startIndex);
    }

    public function hasCount(): bool
    {
        return isset($this->count);
    }

    private static function endsWith(string $haystack, string $needle): bool
    {
        return $needle === substr($haystack, -strlen($needle));
    }
}
