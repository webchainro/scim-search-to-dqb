<?php

namespace Webchain\ScimFilterToDqb\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use Webchain\ScimFilterToDqb\Model\SearchRequest;
use Webchain\ScimFilterToDqb\Parser;
use Webchain\ScimFilterToDqb\Tests\Entity\User;

class ParserTest extends TestCase
{
    /** @var EntityManager */
    private $em;

    /**
     * @dataProvider filterHappyDataProvider
     */
    public function testCreateQueryBuilderFilter($filterString, $expectedDql, $expectedParametersArray)
    {
        $parser = new Parser($this->getEntityManager(), User::class);
        $searchRequest = SearchRequest::fromArray(['filter' => $filterString]);
        $qb = $parser->createQueryBuilder($searchRequest);
        $this->assertEquals($expectedDql, $qb->getQuery()->getDQL());
        $parametersArray = $qb->getParameters()->toArray();
        $resultParametersArray = [];
        /** @var Parameter $parameter */
        foreach ($parametersArray as $parameter) {
            $resultParametersArray[$parameter->getName()] = $parameter->getValue();
        }
        $this->assertEquals($expectedParametersArray, $resultParametersArray);
    }

    public function testCreateQueryBuilderWithPaginationDefaultOffset()
    {
        $parser = new Parser($this->getEntityManager(), User::class);
        $searchRequest = SearchRequest::fromArray([
            'filter' => 'userName eq "bjensen"',
            'count' => 2
        ]);
        $qb = $parser->createQueryBuilder($searchRequest);
        $this->assertContains('LIMIT 2', $qb->getQuery()->getSQL());
    }

    public function testCreateQueryBuilderWithPagination()
    {
        $parser = new Parser($this->getEntityManager(), User::class);
        $searchRequest = SearchRequest::fromArray([
            'filter' => 'userName eq "bjensen"',
            'count' => 2,
            'startIndex' => 5
        ]);
        $qb = $parser->createQueryBuilder($searchRequest);
        $this->assertContains('LIMIT 2 OFFSET 4', $qb->getQuery()->getSQL());
    }

    /**
     * @dataProvider sortHappyDataProvider
     */
    public function testCreateQueryBuilderWithSort($searchRequestArray, $expectedDql)
    {
        $parser = new Parser($this->getEntityManager(), User::class);
        $searchRequest = SearchRequest::fromArray($searchRequestArray);
        $qb = $parser->createQueryBuilder($searchRequest);
        $this->assertEquals($expectedDql, $qb->getQuery()->getDQL());
    }

    /**
     * @dataProvider filterHappyDataProvider
     */
    public function testParseHappyScenarios($filterString, $expectedDql, $expectedParametersArray)
    {
        $parser = new Parser($this->getEntityManager(), User::class);
        $qb = $parser->fromScimToQueryBuilder($filterString);
        $this->assertEquals($expectedDql, $qb->getQuery()->getDQL());
        $parametersArray = $qb->getParameters()->toArray();
        $resultParametersArray = [];
        /** @var Parameter $parameter */
        foreach ($parametersArray as $parameter) {
            $resultParametersArray[$parameter->getName()] = $parameter->getValue();
        }
        $this->assertEquals($expectedParametersArray, $resultParametersArray);
    }

    public function sortHappyDataProvider(): array
    {
        return [
            'Sort default order' => [
                [
                    'filter' => 'userName eq "bjensen"',
                    'sortBy' => 'userName'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp WHERE sftdp.userName = ?1 ORDER BY sftdp.userName ASC',
            ],
            'Sort with order provided' => [
                [
                    'filter' => 'userName eq "bjensen"',
                    'sortBy' => 'userName',
                    'sortOrder' => SearchRequest::SORT_DESCENDING
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp WHERE sftdp.userName = ?1 ORDER BY sftdp.userName DESC',
            ],
            'No joins from filter but added from sort attributes' => [
                [
                    'filter' => 'userName eq "bjensen"',
                    'sortBy' => 'ims.type'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp LEFT JOIN sftdp.ims sftdj1 WHERE sftdp.userName = ?1 ORDER BY sftdj1.type ASC',
            ],
            'Has join from filter and useS the same joins in sort attributes' => [
                [
                    'filter' => 'ims.type eq "bjensen"',
                    'sortBy' => 'ims.type'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp LEFT JOIN sftdp.ims sftdj1 WHERE sftdj1.type = ?1 ORDER BY sftdj1.type ASC',
            ],
            'Has join from filter and adds new joins in sort attributes' => [
                [
                    'filter' => 'meta.lastModified gt "2019-01-28T04:42:34Z"',
                    'sortBy' => 'ims.type'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp LEFT JOIN sftdp.meta sftdj1 LEFT JOIN sftdp.ims sftdj2 WHERE sftdj1.lastModified > ?1 ORDER BY sftdj2.type ASC',
            ],
            'Has joins from filter and uses first joined in sort attributes' => [
                [
                    'filter' => 'group.meta.lastModified gt "2019-01-28T04:42:34Z"',
                    'sortBy' => 'group.name'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp LEFT JOIN sftdp.group sftdj1 LEFT JOIN sftdj1.meta sftdj2 WHERE sftdj2.lastModified > ?1 ORDER BY sftdj1.name ASC',
            ],
            'Has joins from filter and uses second joined in sort attributes' => [
                [
                    'filter' => 'group.meta.lastModified gt "2019-01-28T04:42:34Z"',
                    'sortBy' => 'group.meta.created'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp LEFT JOIN sftdp.group sftdj1 LEFT JOIN sftdj1.meta sftdj2 WHERE sftdj2.lastModified > ?1 ORDER BY sftdj2.created ASC',
            ],
            'Has joins from filter and adds new joins in sort attributes' => [
                [
                    'filter' => 'group.meta.lastModified gt "2019-01-28T04:42:34Z"',
                    'sortBy' => 'ims.type'
                ],
                'SELECT sftdp FROM ' . User::class . ' sftdp LEFT JOIN sftdp.group sftdj1 LEFT JOIN sftdj1.meta sftdj2 LEFT JOIN sftdp.ims sftdj3 WHERE sftdj2.lastModified > ?1 ORDER BY sftdj3.type ASC',
            ],
        ];
    }
    public function filterHappyDataProvider(): array
    {
        return [
            [
                'userName eq "bjensen"',
                "SELECT sftdp FROM " . User::class . " sftdp WHERE sftdp.userName = ?1",
                [1 => "bjensen"]
            ],
            [
                'name.familyName co "O\'Malley"',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.name sftdj1 WHERE sftdj1.familyName LIKE '%O''Malley%'",
                []
            ],

            [
                'userName sw "J"',
                "SELECT sftdp FROM " . User::class . " sftdp WHERE sftdp.userName LIKE 'J%'",
                []
            ],
            [
                'title pr',
                "SELECT sftdp FROM " . User::class . " sftdp WHERE sftdp.title IS NOT NULL",
                []
            ],

            [
                'meta.lastModified gt "2019-01-28T04:42:34Z"',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.meta sftdj1 WHERE sftdj1.lastModified > ?1",
                [1 => new \DateTime('2019-01-28T04:42:34Z')]
            ],
            [
                'title pr and userType eq "Employee"',
                "SELECT sftdp FROM " . User::class . " sftdp WHERE sftdp.title IS NOT NULL AND sftdp.userType = ?1",
                [1 => 'Employee']
            ],

            [
                'title pr or userType eq "Intern"',
                "SELECT sftdp FROM " . User::class . " sftdp WHERE sftdp.title IS NOT NULL OR sftdp.userType = ?1",
                [1 => 'Intern']
            ],
            [
                'userType eq "Employee" and (emails co "example.com" or emails.value co "example.org")',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.emails sftdj1 WHERE sftdp.userType = ?1 AND (sftdj1.value LIKE '%example.com%' OR sftdj1.value LIKE '%example.org%')",
                [1 => 'Employee']
            ],
            [
                'userType ne "Employee" and not (emails co "example.com" or emails.value co "example.org")',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.emails sftdj1 WHERE sftdp.userType <> ?1 AND (sftdj1.value NOT LIKE '%example.com%' AND sftdj1.value NOT LIKE '%example.org%')",
                [1 => 'Employee']
            ],
            [
                'userType eq "Employee" and (emails.type eq "work")',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.emails sftdj1 WHERE sftdp.userType = ?1 AND sftdj1.type = ?2",
                [1 => 'Employee', 2 => 'work']
            ],
            [
                'emails[type eq "work"]',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.emails sftdj1 WHERE sftdj1.type = ?1",
                [1 => 'work']
            ],
            [
                'userType eq "Employee" and emails[type eq "work" and value co "@example.com"]',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.emails sftdj1 WHERE sftdp.userType = ?1 AND (sftdj1.type = ?2 AND sftdj1.value LIKE '%@example.com%')",
                [1 => 'Employee', 2 => "work"]
            ],
            [
                'emails[type eq "work" and value co "@example.com"] or ims[type eq "xmpp" and value co "@foo.com"]',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.emails sftdj1 LEFT JOIN sftdp.ims sftdj2 WHERE (sftdj1.type = ?1 AND sftdj1.value LIKE '%@example.com%') OR (sftdj2.type = ?2 AND sftdj2.value LIKE '%@foo.com%')",
                [1 => 'work', 2 => 'xmpp']
            ],
            [
                'group.meta.lastModified gt "2019-01-28T04:42:34Z" and group.meta.created gt "2019-01-28T04:42:34Z"',
                "SELECT sftdp FROM " . User::class . " sftdp LEFT JOIN sftdp.group sftdj1 LEFT JOIN sftdj1.meta sftdj2 WHERE sftdj2.lastModified > ?1 AND sftdj2.created > ?2",
                [1 => new \DateTime('2019-01-28T04:42:34Z'), 2 => new \DateTime('2019-01-28T04:42:34Z')]
            ]
        ];
    }

    /**
     * @return EntityManagerInterface
     * @throws \Doctrine\ORM\ORMException
     */
    private function getEntityManager(): EntityManagerInterface
    {
        if (null === $this->em) {
            $this->em = $this->buildEntityManager();
        }

        return $this->em;
    }

    /**
     * @return EntityManager
     * @throws \Doctrine\ORM\ORMException
     */
    private function buildEntityManager(): EntityManagerInterface
    {
        $paths = array(realpath(__DIR__ . '/Entity'));

        $config = Setup::createAnnotationMetadataConfiguration($paths, true, null, null, false);
        $dbParams = array('driver' => 'pdo_sqlite', 'memory' => true);
        $cache = new \Doctrine\Common\Cache\ArrayCache;
        $config->setAutoGenerateProxyClasses(true);
        $config->setQueryCacheImpl($cache);
        $config->setMetadataCacheImpl($cache);

        return EntityManager::create($dbParams, $config);
    }

}