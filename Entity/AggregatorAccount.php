<?php

namespace Duf\AggregatorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Duf\AdminBundle\Entity\DufAdminEntity;

use Duf\AdminBundle\Annotations\IndexableAnnotation as Indexable;
use Duf\AdminBundle\Annotations\EditableAnnotation as Editable;

/**
 * AggregatorAccount
 *
 * @ORM\Table(name="aggregator_account")
 * @ORM\Entity(repositoryClass="Duf\AggregatorBundle\Entity\Repository\AggregatorAccountRepository")
 */
class AggregatorAccount extends DufAdminEntity
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="text")
     * @Indexable(index_column=true, index_column_name="Name")
     * @Editable(is_editable=true, label="Name", required=true, type="text", order=1, placeholder="Name of the page")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="account_id", type="text")
     * @Indexable(index_column=true, index_column_name="Account ID")
     * @Editable(is_editable=true, label="Account ID", required=true, type="text", order=2, placeholder="Account ID")
     */
    private $accountId;

    /**
     * @var string
     *
     * @ORM\Column(name="service", type="text")
     */
    private $service;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     * @Editable(is_editable=true, label="Enabled", required=false, type="checkbox", order=3)
     * @Indexable(index_column=true, index_column_name="Enabled", boolean_column=true)
     */
    public $enabled;

    /**
     * Set name
     *
     * @param string $name
     *
     * @return AggregatorAccount
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set accountId
     *
     * @param string $accountId
     *
     * @return AggregatorAccount
     */
    public function setAccountId($accountId)
    {
        $this->accountId = $accountId;

        return $this;
    }

    /**
     * Get accountId
     *
     * @return string
     */
    public function getAccountId()
    {
        return $this->accountId;
    }

    /**
     * Set service
     *
     * @param string $service
     *
     * @return AggregatorAccount
     */
    public function setService($service)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return string
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set enabled
     *
     * @param boolean $enabled
     *
     * @return AggregatorAccount
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
}
