<?php

namespace Webchain\ScimFilterToDqb\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
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
        $this->assertEquals($expectedDql, $qb->getQuery()->getDQL());
        $parametersArray = $qb->getParameters()->toArray();
        $resultParametersArray = [];
        /** @var Parameter $parameter */
        foreach ($parametersArray as $parameter) {
            $resultParametersArray[$parameter->getName()] = $parameter->getValue();
        }
        $this->assertEquals($expectedParametersArray, $resultParametersArray);
    }

    public function parserHappyDataProvider()
    {
        return [
//            [
//                'userName eq "bjensen"',
//                "SELECT " . Parser::PRIMARY_ENTITY_ALIAS . " FROM " . User::class . " " . Parser::PRIMARY_ENTITY_ALIAS . " WHERE " . Parser::PRIMARY_ENTITY_ALIAS . ".userName = ?1",
//                [1 => "bjensen"]
//            ],
//
//            [
//                'name.familyName co "O\'Malley"',
//                "SELECT " . Parser::PRIMARY_ENTITY_ALIAS . " FROM " . User::class . " " . Parser::PRIMARY_ENTITY_ALIAS . " LEFT JOIN " . Parser::PRIMARY_ENTITY_ALIAS . ".name " . Parser::JOINS_ALIAS_SUFFIX . "0 WHERE " . Parser::JOINS_ALIAS_SUFFIX . "0.familyName LIKE '%O''Malley%'",
//                []
//            ],
//
//            [
//                'userName sw "J"',
//                "SELECT " . Parser::PRIMARY_ENTITY_ALIAS . " FROM " . User::class . " " . Parser::PRIMARY_ENTITY_ALIAS . " WHERE " . Parser::PRIMARY_ENTITY_ALIAS . ".userName LIKE 'J%'",
//                []
//            ],
//            [
//                'title pr',
//                "SELECT " . Parser::PRIMARY_ENTITY_ALIAS . " FROM Webchain\ScimFilterToDqb\Tests\Entity\User " . Parser::PRIMARY_ENTITY_ALIAS . " WHERE " . Parser::PRIMARY_ENTITY_ALIAS . ".title IS NOT NULL",
//                []
//            ],
//
//            [
//                'meta.lastModified gt "2019-01-28T04:42:34Z"',
//                "SELECT sftdp FROM Webchain\ScimFilterToDqb\Tests\Entity\User sftdp LEFT JOIN sftdp.meta sftdj0 WHERE sftdj0.lastModified > ?1",
//                [1 => new \DateTime('2019-01-28T04:42:34Z')]
//            ],
//            [
//                'title pr and userType eq "Employee"',
//                "SELECT sftdp FROM Webchain\ScimFilterToDqb\Tests\Entity\User sftdp WHERE sftdp.title IS NOT NULL AND sftdp.userType = ?1",
//                [1 => 'Employee']
//            ],
//
//            [
//                'title pr or userType eq "Intern"',
//                "SELECT sftdp FROM Webchain\ScimFilterToDqb\Tests\Entity\User sftdp WHERE sftdp.title IS NOT NULL OR sftdp.userType = ?1",
//                [1 => 'Intern']
//            ],
//            [
//                'userType eq "Employee" and (emails co "example.com" or emails.value co "example.org")',
//                "SELECT sftdp FROM Webchain\ScimFilterToDqb\Tests\Entity\User sftdp LEFT JOIN sftdp.emails sftdj0 WHERE sftdp.userType = ?1 AND (sftdp.emails LIKE '%example.com%' OR sftdj0.value LIKE '%example.org%')",
//                []
//            ],
            [
                'userType ne "Employee" and not (emails co "example.com" or emails.value co "example.org")',
                "SELECT sftdp FROM Webchain\ScimFilterToDqb\Tests\Entity\User sftdp LEFT JOIN sftdp.emails sftdj0 WHERE sftdp.userType <> ?1 AND (sftdp.emails NOT LIKE '%example.com%' AND sftdj0.value NOT LIKE '%example.org%')",
                [1 => 'Employee']
            ],
//            [
//                'userType eq "Employee" and (emails.type eq "work")',
//                "",
//                []
//            ],
//            [
//                'emails[type eq "work"]',
//                "",
//                []
//            ],
//            [
//                'userType eq "Employee" and emails[type eq "work" and value co "@example.com"]',
//                "",
//                []
//            ],
//
//            [
//                'emails[type eq "work" and value co "@example.com"] or phoneNumbers[value co "111" and type eq "work"]',
//                "",
//                []
//            ],
//
//            [
//                'userName eq "john" and name sw "mike"',
//                "",
//                []
//            ],
//
//            [
//                'userName eq "john" or name sw "mike"',
//                "",
//                []
//            ],
//
//            [
//                'userName eq "john" or name sw "mike" and id ew "123"',
//                "",
//                []
//            ],
//
//            [
//                'userName eq "john" and (name sw "mike" or id ew "123")',
//                "",
//                []
//            ],
//
//            [
//                'userName eq "john" and not (name sw "mike" or id ew "123")',
//                "",
//                []
//            ],
        ];
    }

    /**
     * @return EntityManager
     */
    private function getEntityManager()
    {
        if (null === $this->em) {
            $this->em = $this->buildEntityManager();
        }

        return $this->em;
    }

    private function buildEntityManager()
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

    public function error_provider_v2()
    {
        return [
            ['none a valid filter', "[Syntax Error] line 0, col 5: Error: Expected comparision operator, got 'a'"],
            ['username xx "mike"', "[Syntax Error] line 0, col 9: Error: Expected comparision operator, got 'xx'"],
            ['username eq', "[Syntax Error] line 0, col 9: Error: Expected SP, got end of string."],
            ['username eq ', "[Syntax Error] line 0, col 11: Error: Expected comparison value, got end of string."],
            ['emails[type[value eq "1"]]', "[Syntax Error] line 0, col 11: Error: Expected SP, got '['"],
            ['members.value', '[Syntax Error] line 0, col 8: Error: Expected SP, got end of string.'],
        ];
    }


}