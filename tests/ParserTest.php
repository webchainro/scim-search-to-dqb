<?php

namespace Webchain\ScimFilterToDqb\Tests;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Tools\Setup;
use PHPUnit\Framework\TestCase;
use Webchain\ScimFilterToDqb\Parser;
use Webchain\ScimFilterToDqb\Tests\Entity\User;

class ParserTest extends TestCase
{
    /** @var EntityManager */
    private $em;

    /**
     * @dataProvider parserHappyDataProvider
     */
    public function testParseHappyScenarios($filterString, $expectedDql, $expectedParametersArray)
    {
        $parser = new Parser($this->getEntityManager(), User::class);
        $qb = $parser->fromScimToQueryBuilder($filterString);
        $this->assertEquals(trim(preg_replace('/\s\s+/', ' ', $expectedDql)), $qb->getQuery()->getDQL());
        $parametersArray = $qb->getParameters()->toArray();
        $resultParametersArray = [];
        /** @var Parameter $parameter */
        foreach ($parametersArray as $parameter) {
            $resultParametersArray[$parameter->getName()] = $parameter->getValue();
        }
        $this->assertEquals($expectedParametersArray, $resultParametersArray);
    }

    public function parserHappyDataProvider(): array
    {
        return [
            [
                'userName eq "bjensen"',
                "
                 SELECT 
                    sftdp 
                 FROM 
                    " . User::class . " sftdp 
                 WHERE 
                    sftdp.userName = ?1",

                [1 => "bjensen"]
            ],

            [
                'name.familyName co "O\'Malley"',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.name sftdj1 
                WHERE 
                    sftdj1.familyName LIKE '%O''Malley%'",
                []
            ],

            [
                'userName sw "J"',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                WHERE 
                    sftdp.userName LIKE 'J%'",
                []
            ],
            [
                'title pr',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                WHERE 
                    sftdp.title IS NOT NULL",
                []
            ],

            [
                'meta.lastModified gt "2019-01-28T04:42:34Z"',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.meta sftdj1 
                WHERE 
                    sftdj1.lastModified > ?1",
                [1 => new \DateTime('2019-01-28T04:42:34Z')]
            ],
            [
                'title pr and userType eq "Employee"',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                WHERE 
                    sftdp.title IS NOT NULL 
                AND 
                    sftdp.userType = ?1",
                [1 => 'Employee']
            ],

            [
                'title pr or userType eq "Intern"',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                WHERE 
                    sftdp.title IS NOT NULL 
                OR 
                    sftdp.userType = ?1",
                [1 => 'Intern']
            ],
            [
                'userType eq "Employee" and (emails co "example.com" or emails.value co "example.org")',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.emails sftdj1 
                WHERE 
                    sftdp.userType = ?1 
                AND (
                    sftdj1.value LIKE '%example.com%' OR sftdj1.value LIKE '%example.org%')",
                [1 => 'Employee']
            ],
            [
                'userType ne "Employee" and not (emails co "example.com" or emails.value co "example.org")',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.emails sftdj1 
                WHERE 
                    sftdp.userType <> ?1 
                AND (
                    sftdj1.value NOT LIKE '%example.com%' 
                    AND 
                    sftdj1.value NOT LIKE '%example.org%'
                )",
                [1 => 'Employee']
            ],
            [
                'userType eq "Employee" and (emails.type eq "work")',
                "SELECT 
                    sftdp 
                FROM 
                    " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.emails sftdj1 
                WHERE 
                    sftdp.userType = ?1 
                AND 
                    sftdj1.type = ?2",
                [1 => 'Employee', 2 => 'work']
            ],
            [
                'emails[type eq "work"]',
                "SELECT 
                  sftdp 
                FROM 
                  " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.emails sftdj1 
                WHERE 
                    sftdj1.type = ?1",
                [1 => 'work']
            ],
            [
                'userType eq "Employee" and emails[type eq "work" and value co "@example.com"]',
                "SELECT 
                  sftdp 
                FROM 
                  " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.emails sftdj1 
                WHERE 
                    sftdp.userType = ?1 
                AND (
                    sftdj1.type = ?2 
                    AND 
                    sftdj1.value LIKE '%@example.com%'
                )",
                [1 => 'Employee', 2 => "work"]
            ],
            [
                'emails[type eq "work" and value co "@example.com"] or ims[type eq "xmpp" and value co "@foo.com"]',
                "SELECT 
                  sftdp 
                FROM 
                  " . User::class . " sftdp 
                LEFT JOIN 
                    sftdp.emails sftdj1 
                LEFT JOIN 
                    sftdp.ims sftdj2 
                WHERE (
                    sftdj1.type = ?1 
                    AND 
                    sftdj1.value LIKE '%@example.com%'
                ) 
                OR 
                (
                    sftdj2.type = ?2 
                    AND 
                    sftdj2.value LIKE '%@foo.com%'
                )",
                [1 => 'work', 2 => 'xmpp']
            ],
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