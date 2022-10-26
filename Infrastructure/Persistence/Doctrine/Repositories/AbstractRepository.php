<?php

namespace Infrastructure\Persistence\Doctrine\Repositories;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Illuminate\Http\JsonResponse;

abstract class AbstractRepository
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected string $entityNamespace,
    ) {
    }

    protected function find(int $entity_id)
    {
        return $this->em->find($this->entityNamespace, $entity_id);
    }

    protected function findByActive(): object | array
    {
        return $this->em->getRepository($this->entityNamespace)->findBy(['active' => 1]);
    }

    public function getPaginatedData(QueryBuilder $query, $filter)
    {
        $page = 1;

        if (isset($filter['page'])) {
            $page = $filter['page'];
        }

        if ($page == 'all') {
            return $this->returnWithoutPagination($query);
        }

        $paginatedData = (new Paginator($query->getQuery(), $page, $filter['perPage']))->getData();

        $normalizedItems = [];

        foreach ($paginatedData['items'] as $page) {

            if (is_array($page) && isset($page[0])) {
                unset($page[0]);
                $normalizedItems[] = $page;
            } else {
                $normalizedItems[] = $page;
            }
        }

        unset($paginatedData['items']);
        $paginatedData['items'] = $normalizedItems;

        return $paginatedData;
    }

    public function returnWithoutPagination($query)
    {
        $result = $this->em->createQuery($query->getDQL());
        $result = $result->getResult();

        $normalizedItems = [];
        foreach ($result as $page) {
            if (is_array($page) && isset($page[0])) {
                unset($page[0]);
                $normalizedItems[] = $page;
            } else {
                $normalizedItems[] = $page;
            }
        }

        return $normalizedItems;
    }

    public function paginated(QueryBuilder $query, $offset, $limit)
    {
        $page = ($offset <= 0) ? 1 : ($offset / $limit) + 1;

        $paginatedData = (new Paginator($query->getQuery(), $page, $limit))->getData();

        $normalizedItems = [];

        foreach ($paginatedData['items'] as $page) {

            if (is_array($page) && isset($page[0])) {
                unset($page[0]);
                $normalizedItems[] = $page;
            } else {
                $normalizedItems[] = $page;
            }
        }

        unset($paginatedData['items']);
        $paginatedData['items'] = $normalizedItems;

        return $paginatedData;
    }

    protected function filterByQuery(QueryBuilder $qb, $filters)
    {
        if (isset($filters['q']) && !empty($filters['q']) && isset($filters['fieldsNames']) && !empty($filters['fieldsNames'])) {
            $searchedQuery = $filters['q'];
            $fieldsNames = array_filter(explode(',', $filters['fieldsNames']));
            if (!empty($fieldsNames)) {
                $orX = $qb->expr()->orX();
                foreach ($fieldsNames as $key => $fieldName) {
                    if (in_array($fieldName, $this->getAllEntityFieldsNames())) {
                        $orX->add("{$qb->getAllAliases()[0]}.{$fieldName} LIKE :{$fieldName}{$key}");
                        $qb->setParameter($fieldName . $key, '%' . $searchedQuery . '%');
                    }
                }
                if ($orX->getParts()) {
                    $qb->andWhere($qb->expr()->orX($orX));
                }
            }
        }
        return $qb;
    }


    private function removeQueryBuilderParameterByName(QueryBuilder $qb, $parameterName)
    {
        $params = $qb->getParameters();
        foreach ($params as $key => $param) {
            if ($param->getName() == 'published') {
                $params->remove($key);
            }
        }
        return $qb;
    }

    public function findAllByFiltersAndPagination($filters, $offset = 0, $limit = 25)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('e')->from($this->entityNamespace, 'e');

        $this->filterByQuery($qb, $filters);

        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ((in_array($key, $this->getAllEntityFieldsNames()) || in_array($key, $this->getAllEntityAssociationNames())) && !is_array($filters[$key])) {
                    $qb->andWhere("e.$key = :$key");
                    $qb->setParameter($key, $value);
                } else if (in_array($key, $this->getAllEntityFieldsNames()) && is_array($filters[$key])) {
                    $qb->andWhere("e.$key In(:$key)");
                    $qb->setParameter($key, $value);
                } elseif (in_array($key, $this->getAllEntityAssociationNames())) {

                    if (is_array($value) && isset($filters['withFilters'])) {
                        $orX = $qb->expr()->andX();
                        foreach ($value as $item) {
                            $orX->add($qb->expr()->isMemberOf(":{$key}{$item}", "e.{$key}"));
                            $qb->setParameter($key . $item, $item);
                        }
                        if ($orX->getParts()) {
                            $qb->andWhere($qb->expr()->andX($orX));
                        }
                    } else {
                        $qb->andWhere($qb->expr()->isMemberOf(":{$key}", "e.{$key}"))
                            ->setParameter($key, $value);
                    }
                }
            }
        }

        if (isset($filters['notEqual']) && !empty($filters['notEqual'])) {
            foreach ($filters['notEqual'] as $key => $value) {
                $qb->andWhere($qb->expr()->notIn("e.$key", $value));
            }
        }

        if (isset($filters['notEqualAssociation']) && !empty($filters['notEqualAssociation'])) {
            foreach ($filters['notEqualAssociation'] as $key => $value) {
                $qb->andWhere("e.$key != :$key")->setParameter(":$key", $value);
            }
        }


        if (isset($filters['notIsNull']) && !empty($filters['notIsNull'])) {
            if (in_array($filters['notIsNull'], $this->getAllEntityAssociationNames())) {
                $qb->andWhere("e.{$filters['notIsNull']} is not empty");
            } else {
                $qb->andWhere($qb->expr()->isNotNull("e.{$filters['notIsNull']}"));
            }
        }

        if (isset($filters['isNull']) && !empty($filters['isNull'])) {
            $qb->andWhere($qb->expr()->isNull("e.{$filters['isNull']}"));
        }

        if (isset($filters['hasDateRange']) && isset($filters['dateRange']['field']) && isset($filters['dateRange']['from']) && isset($filters['dateRange']['to'])) {
            $qb->andWhere("e.{$filters['dateRange']['field']} BETWEEN :from AND :to")
                ->setParameter('from', $filters['dateRange']['from'])
                ->setParameter('to', $filters['dateRange']['to']);
        }

        if (
            isset($filters['notIsNullOrKey']) && !empty($filters['notIsNullOrKey']) &&
            isset($filters['notIsNullOrValue']) && !empty($filters['notIsNullOrValue'])
        ) {

            // remove old part
            $qb_where_part = $qb->getDqlPart('where')->getParts();
            $qb->resetDQLPart('where');
            foreach ($qb_where_part as $where_clause) {
                if ("e.{$filters['notIsNullOrKey']} = :{$filters['notIsNullOrKey']}" === $where_clause) continue;
                $qb->andWhere($where_clause);
            }

            if (in_array($filters['notIsNullOrValue'], $this->getAllEntityAssociationNames())) {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->orX()->add("e.{$filters['notIsNullOrValue']} is not empty"),
                        $qb->expr()->eq("e.{$filters['notIsNullOrKey']}", ':' . @$filters['notIsNullOrKey'])
                    )
                );
                $qb->setParameter($filters['notIsNullOrKey'], @$filters[$filters['notIsNullOrKey']]);
            } else {
                $qb->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNotNull("e.{$filters['notIsNullOrValue']}"),
                        $qb->expr()->eq("e.{$filters['notIsNullOrKey']}", ':' . @$filters['notIsNullOrKey'])
                    )
                );
                $qb->setParameter($filters['notIsNullOrKey'], @$filters[$filters['notIsNullOrKey']]);
            }
        }

        if (isset($filters['orderBy']) && !empty($filters['orderBy']) && isset($filters['order']) && !empty($filters['order'])) {
            $orderByAccepted = ['asc', 'ASC', 'DESC', 'desc'];
            if (in_array($filters['order'], $orderByAccepted) && in_array($filters['orderBy'], $this->getAllEntityFieldsNames())) {
                $qb->orderBy("e.{$filters['orderBy']}", $filters['order']);
            }
        } else {
            $qb->orderBy("e.id", 'ASC');
        }

        if (isset($filters['groupBy']) && !empty($filters['groupBy'])) {
            $qb->groupBy('e.' . $filters['groupBy']);
        }


        $limit = ($limit <= 2000) ? $limit : 25;
        $qb->setFirstResult($offset)->setMaxResults($limit);
        if (isset($filters['pagination']) && $filters['pagination'] === false) {
            return $qb->getQuery()->getResult();
        }
        return $this->paginated($qb, $offset, $limit);
    }

    protected function getAllEntityFieldsNames(): array
    {
        return $this->em->getClassMetadata($this->entityNamespace)->getFieldNames();
    }

    protected function getAllEntityAssociationNames(): array
    {
        return $this->em->getClassMetadata($this->entityNamespace)->getAssociationNames();
    }

    protected function findBy(array $findBy): JsonResponse
    {
        return $this->em->getRepository($this->entityNamespace)->findOneBy($findBy);
    }

    protected function findAll(): array
    {
        return $this->em->getRepository($this->entityNamespace)->findAll();
    }

    protected function findAllBy(array $findBy, ?array $order): array
    {
        return $this->em->getRepository($this->entityNamespace)->findBy($findBy, $order);
    }

    protected function getEntity(): Entity
    {
        return new $this->entityNamespace();
    }

    protected function save($entity)
    {
        try {
            $this->em->persist($entity);
            $this->em->flush();
        } catch (Exception $e) {
        }
        return $entity;
    }

    protected function onlySave($entity)
    {
        $this->em->persist($entity);
        $this->em->flush();
        $this->em->clear();
    }

    public function update($entity, array $attributes)
    {
        $acceptedAttributes = [];
        foreach ($attributes as $key => $value) {
            if (
                in_array($key, $this->getAllEntityFieldsNames()) ||
                in_array($key, $this->getAllEntityAssociationNames())
            ) {
                $acceptedAttributes[$key] = $value;
            }
        }
        $this->setAttributes($entity, $acceptedAttributes);
        $this->addRelatedEntity($entity, $acceptedAttributes);
        return $this->save($entity);
    }

    public function setAttributes($entity, $arrAttributes)
    {
        foreach ($arrAttributes as $attr => $val) {
            if (method_exists($entity, 'set' . $attr)) {
                $param = new \ReflectionParameter(array(get_class($entity), 'set' . $attr), 0);

                if (!is_null($param->getClass())) {
                    $entityClass = $param->getClass()->name;
                    $value = $this->findExternal($entityClass, $val);
                    $entity->{'set' . $attr}($value);
                } else {
                    $entity->{'set' . $attr}($val);
                }
            }
        }
    }

    public function findExternal(string $entityNamespace, int $entityId)
    {
        $entityLoaded = $this->em->find($entityNamespace, $entityId);
        return $entityLoaded;
    }

    public function addRelatedEntity($entity, $arrAttributes)
    {
        foreach ($arrAttributes as $parentAttribute => $values) {
            if (is_array($values)) {
                foreach ($values as $val) {
                    if (method_exists($entity, 'add' . $parentAttribute)) {

                        $param = new \ReflectionParameter(array($this->entityNamespace, 'add' . $parentAttribute), 0);

                        if (!is_null($param->getClass())) {

                            $entityClass = $param->getClass()->name;
                            $newRelatedEntity  = new $entityClass;

                            $this->setAttributes($newRelatedEntity, $val);

                            if (method_exists($newRelatedEntity, 'setCreatedAt')) {
                                $newRelatedEntity->setCreatedAt(new \DateTime('now'));
                            }

                            $parentClassId  = get_class($entity);
                            $parentClassId = substr($parentClassId, strrpos($parentClassId, '\\') + 1);

                            $newRelatedEntity->{'set' . $parentClassId}($entity);

                            $entity->{'add' . $parentAttribute}($newRelatedEntity);
                        }
                    } elseif (method_exists($entity, 'addMultiple' . $parentAttribute)) {
                        $param = new \ReflectionParameter(array($this->entityNamespace, 'addMultiple' . $parentAttribute), 0);
                        if (!is_null($param->getClass())) {
                            $entityClass = $param->getClass()->name;
                            $allRefrencies = [];
                            foreach ($values as $value) {
                                $allRefrencies[] = $this->findExternal($entityClass, $value);
                            }
                            $entity->{'setMultiple' . $parentAttribute}($allRefrencies);
                        }
                    }
                }
            }
        }
    }

    protected function delete($entity)
    {
        $this->em->remove($entity);
        $this->em->flush();
    }

    protected function deleteMultiple(array $entities)
    {
        foreach ($entities as $entity) {
            $this->em->remove($entity);
        }
        $this->em->flush();
    }

    protected function updateMultiple(array $entities)
    {
        foreach ($entities as $entity) {
            $this->em->persist($entity);
        }
        $this->em->flush();
    }

    public function deleteAll($arrValue)
    {
        $entitiesId = implode(",", $arrValue);
        $q = $this->em->createQuery("delete {$this->entityNamespace} u where u.id IN ($entitiesId)");
        return $q->execute();
    }

    /**
     * @param $entityId
     * @return mixed
     */
    public function deleteById($entityId)
    {
        try {
            $q = $this->em->createQuery("delete {$this->entityNamespace} u where u.id = $entityId");
            return $q->execute();
        } catch (\Exception $e) {
            echo $e->getMessage();
            exit;
        }
    }

    public function deleteByFieldAndId($field, $entityId)
    {
        $q = $this->em->createQuery("delete {$this->entityNamespace} u where u.$field = $entityId");
        return $q->execute();
    }

    public function deleteByArrKeyValue($arrKeyValue)
    {
        $condition = '';
        foreach ($arrKeyValue as $key => $value) {
            if ($condition) {
                $condition .= ' AND ';
            }
            $condition .= 'u.' . $key . ' = \'' . $value . '\'';
        }

        $q = $this->em->createQuery("delete {$this->entityNamespace} u where {$condition}");
        return $q->execute();
    }

    public function count()
    {
        $qb = $this->em->createQueryBuilder();
        return $qb
            ->select('count(e.id)')
            ->from($this->entityNamespace, 'e')
            ->getQuery()
            ->useQueryCache(true)
            ->useResultCache(true, 3600)
            ->getSingleScalarResult();
    }

    public function loadNew($arrAttributes = null)
    {
        $entity =  new $this->entityNamespace();
        if ($arrAttributes) {
            $this->setAttributes($entity, $arrAttributes);
            $this->addRelatedEntity($entity, $arrAttributes);
        }

        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt(new \DateTime('now'));
        }

        return $entity;
    }

    public function load($entity, $arrAttributes = null)
    {
        if ($arrAttributes) {
            $this->setAttributes($entity, $arrAttributes);
            $this->loadRelatedEntity($entity, $arrAttributes);
        }

        return $entity;
    }

    public function loadRelatedEntity($entity, $arrAttributes)
    {
        foreach ($arrAttributes as $attr => $val) {
            if (is_array($val)) {
                foreach ($val as $id) {
                    if (method_exists($entity, 'add' . $attr)) {

                        $param = new \ReflectionParameter(array($this->entityNamespace, 'add' . $attr), 0);

                        if (!is_null($param->getClass())) {
                            $entityClass = $param->getClass()->name;

                            $value = $this->findExternal($entityClass, $id);
                            $entity->{'add' . $attr}($value);
                        }
                    }
                }
            }
        }
    }

    public function firstOrNew($arrKeyValue)
    {
        $entity = $this->findBy($arrKeyValue);
        if ($entity) {
            return $entity;
        }

        return $this->loadNew($arrKeyValue);
    }

    public function firstOrCreate($arrKeyValue)
    {
        $entity = $this->findBy($arrKeyValue);
        if ($entity) {
            return $entity;
        }

        $entity = $this->loadNew($arrKeyValue);
        $this->save($entity);
        return $entity;
    }

    public function normalizeSearchTerm($searchTerm)
    {
        $term = '%' . preg_replace('/\s+/', '%', $searchTerm) . '%';
        return $term;
    }

    public function resetFlag($flagName)
    {
        return $this->em->createQuery('UPDATE ' . $this->entityNamespace  . " e SET e.{$flagName} = 0")->execute();
    }
}
