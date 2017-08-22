<?php

namespace Duf\AggregatorBundle\Entity\Repository;

use Duf\AdminBundle\Entity\Repository\DufAdminRepository;

/**
 * AggregatorPostRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AggregatorPostRepository extends DufAdminRepository
{
	public function findByPagination($account, $start, $limit)
	{
		$qb = $this->_em->createQueryBuilder()
						->select('post')
						->from($this->_entityName, 'post')
						->where('post.account = :account')
						->orderBy('post.postDate', 'DESC')
						->setParameters(
							array(
								'account' => $account,
							)
						)
						->setFirstResult($start)
						->setMaxResults($limit);

 		return $qb->getQuery()->getResult();
	}
}