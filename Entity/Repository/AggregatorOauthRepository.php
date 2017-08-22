<?php

namespace Duf\AggregatorBundle\Entity\Repository;

/**
 * AggregatorOauthRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AggregatorOauthRepository extends \Doctrine\ORM\EntityRepository
{
	public function findByPreviousOauth($service, $name = null)
	{
		$qb = $this->_em->createQueryBuilder()
		 				->select('oauth')
						->from($this->_entityName, 'oauth')
						->where('oauth.service = :service')
						->andWhere('oauth.expiresAt > :date_today')
						->setParameters(
							array(
								'service' 		=> $service,
								'date_today' 	=> new \DateTime(),
							)
						);

		if (null !== $name) {
			$qb->andWhere('oauth.name = :name')
			   ->setParameter('name', $name);
		}

		$qb->setMaxResults(1);

		$oauth = $qb->getQuery()->getOneOrNullResult();

		if (!empty($oauth) && null !== $oauth)
			return $oauth;

 		return null;
	}
}