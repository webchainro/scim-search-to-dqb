<?php

namespace Webchain\ScimFilterToDqb;

use Webchain\ScimFilterToDqb\Model\SearchRequest;

class SelectBuilder
{
    public static function buildString(SearchRequest $searchRequest, Joiner $joiner): string
    {
        if ($searchRequest->hasAttributes()) {
            return self::buildAttributesString($searchRequest, $joiner);
        }

        if ($searchRequest->hasExcludedAttributes() && !$searchRequest->hasAttributes()) {

        }

        return Joiner::PRIMARY_ENTITY_ALIAS;
    }

    /**
     * @param SearchRequest $searchRequest
     * @param Joiner $joiner
     * @return string
     */
    private static function buildAttributesString(SearchRequest $searchRequest, Joiner $joiner): string
    {
        $attributesMap = [];
        $attributes = $searchRequest->getAttributes();
        if ($searchRequest->hasExcludedAttributes()) {
            $attributes = array_udiff($searchRequest->getAttributes(), $searchRequest->getExcludedAttributes(),
                function ($attribute, $excluded) {
                    return (int)((string)$attribute !== (string)$excluded);
                });
        }

        foreach ($attributes as $attributePath) {

            $currentJoin = $joiner->getJoinByAlias(Joiner::PRIMARY_ENTITY_ALIAS);

            foreach ($attributePath->attributeNames as $index => $attribute) {
                if ($index > 0) {
                    if ($currentJoin->hasColumnJoined($attribute)) {
                        $currentJoin = $currentJoin->getJoinedWithByColumn($attribute);
                    } else {
                        $nextAlias = $joiner->detectNextAlias($currentJoin->getAlias(), $attribute, $index);
                        $currentJoin = $joiner->getJoinByAlias($nextAlias);
                    }
                }

                if (!isset($attributesMap[$currentJoin->getAlias()])) {
                    $attributesMap[$currentJoin->getAlias()] = [];
                }
                $attributesMap[$currentJoin->getAlias()][] = $attribute;
            }
        }

        $tables = [];
        foreach ($attributesMap as $alias => $attributes) {
            $tables[] = "$alias.{" . implode(',', $attributes) . "}";
        }

        return 'partial ' . implode(',', $tables);
    }
}
