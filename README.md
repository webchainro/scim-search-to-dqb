# SCIM filter parser to QB (Doctrine Query Builder)

Transforms a SCIM string filter into a Doctrine Query Builder. SCIM stands for System for Cross-domain Identity Management and more details about filtering can be found at https://tools.ietf.org/html/rfc7644#section-3.4.2.2 .

# Usage


```
// Route would look something like `/v1/Users?filter=userType eq "Employee" and (emails.type eq "work")`

$parser = new Parser($this->getEntityManager(), User::class);
$qb = $parser->fromScimToQueryBuilder($filterString);


$qb->getQuery()->getDQL();
// Should give you: 
// SELECT sftdp FROM User sftdp LEFT JOIN sftdp.emails sftdj1 WHERE sftdp.userType = ?1 AND sftdj1.type = ?2


$qb->getParameters();
// Should give you:
/*
result = {Doctrine\Common\Collections\ArrayCollection} [1]
 elements = {array} [2]
  0 = {Doctrine\ORM\Query\Parameter} [3]
   name = "1"
   value = "Employee"
   type = 2
  1 = {Doctrine\ORM\Query\Parameter} [3]
   name = "2"
   value = "work"
   type = 2
/*
```

For more details look at the [unit tests](tests/ParserTest.php)

By default the parser third parameter will use the [string parser](https://github.com/tmilos/scim-filter-parser)'s library default. Both versions are supported

The library assumes that the associations between Entities are defined. If you need examples take a look an the [User](tests/Entity/User.php) entity or study [doctrine association mappings](https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/association-mapping.html)

