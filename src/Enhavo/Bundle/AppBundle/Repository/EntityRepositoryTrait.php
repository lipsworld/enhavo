<?php
/**
 * EntityRepositoryTrait.php
 *
 * @since 21/01/17
 * @author gseidel
 */

namespace Enhavo\Bundle\AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Enhavo\Bundle\AppBundle\Filter\FilterQuery;
use Enhavo\Bundle\AppBundle\Exception\FilterException;

trait EntityRepositoryTrait
{
    public function findBetween($property, \DateTime $from, \DateTime $to, $criteria = [], $orderBy = [])
    {
        /** @var QueryBuilder $query */
        $query = $this->createQueryBuilder('e');

        $query->where(sprintf('e.%s BETWEEN :from AND :to', $property))
            ->setParameter('from', $from->format('Y-m-d'))
            ->setParameter('to', $to->format('Y-m-d'));

        $i = 0;
        foreach($criteria  as $property => $value) {
            $query->andWhere(sprintf('e.%s = :criteria%s', $property, $i));
            $query->setParameter(sprintf('criteria%s', $i), $value);
            $i++;
        }

        foreach($orderBy  as $property => $value) {
            $query->addOrderBy($property, $value);
        }

        return $query->getQuery()->getResult();
    }

    public function filter(FilterQuery $filterQuery)
    {
        /** @var QueryBuilder $query */
        $query = $this->createQueryBuilder('e');
        $i = 0;
        foreach($filterQuery->getWhere() as $where) {
            $i++;
            $query->andWhere(sprintf('e.%s %s :parameter%s', $where['property'], $this->getOperator($where), $i));
            $query->setParameter(sprintf('parameter%s', $i), $this->getValue($where));
        }

        return $this->getPaginator($query);
    }

    private function getValue($where)
    {
        $value = $where['value'];

        if($where['operator'] == FilterQuery::OPERATOR_LIKE) {
            return '%'.$value.'%';
        }

        return $value;
    }

    private function getOperator($where)
    {
        switch($where['operator']) {
            case(FilterQuery::OPERATOR_EQUALS):
                return '=';
            case(FilterQuery::OPERATOR_LIKE):
                return 'like';
        }
        throw new FilterException('Operator not supported in Repository');
    }
}