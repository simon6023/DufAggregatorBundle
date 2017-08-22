<?php

namespace Duf\AggregatorBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AggregatorPost
 *
 * @ORM\Table(name="aggregator_post")
 * @ORM\Entity(repositoryClass="Duf\AggregatorBundle\Entity\Repository\AggregatorPostRepository")
 */
class AggregatorPost
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="service", type="string", length=50)
     */
    private $service;

    /**
     * @var bool
     *
     * @ORM\Column(name="enabled", type="boolean", nullable=true)
     */
    private $enabled;

    /**
     * @var string
     *
     * @ORM\Column(name="caption", type="string", length=255, nullable=true)
     */
    private $caption;

    /**
     * @var string
     *
     * @ORM\Column(name="image", type="string", length=255, nullable=true)
     */
    private $image;

    /**
     * @var string
     *
     * @ORM\Column(name="video", type="string", length=255, nullable=true)
     */
    private $video;

    /**
     * @var string
     *
     * @ORM\Column(name="postUser", type="text", nullable=true)
     */
    private $postUser;

    /**
     * @var string
     *
     * @ORM\Column(name="postId", type="text")
     */
    private $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="link", type="text", nullable=true)
     */
    private $link;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="postDate", type="datetime")
     */
    private $postDate;

    /**
     * @ORM\ManyToOne(targetEntity="Duf\AggregatorBundle\Entity\AggregatorAccount")
     * @ORM\JoinColumn(nullable=false)
     */
     private $account;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set service
     *
     * @param string $service
     *
     * @return AggregatorPost
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
     * @return AggregatorPost
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

    /**
     * Set caption
     *
     * @param string $caption
     *
     * @return AggregatorPost
     */
    public function setCaption($caption)
    {
        $this->caption = $caption;

        return $this;
    }

    /**
     * Get caption
     *
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * Set image
     *
     * @param string $image
     *
     * @return AggregatorPost
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return string
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set video
     *
     * @param string $video
     *
     * @return AggregatorPost
     */
    public function setVideo($video)
    {
        $this->video = $video;

        return $this;
    }

    /**
     * Get video
     *
     * @return string
     */
    public function getVideo()
    {
        return $this->video;
    }

    /**
     * Set postUser
     *
     * @param string $postUser
     *
     * @return AggregatorPost
     */
    public function setPostUser($postUser)
    {
        $this->postUser = $postUser;

        return $this;
    }

    /**
     * Get postUser
     *
     * @return string
     */
    public function getPostUser()
    {
        return $this->postUser;
    }

    /**
     * Set postId
     *
     * @param string $postId
     *
     * @return AggregatorPost
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
    }

    /**
     * Get postId
     *
     * @return string
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * Set link
     *
     * @param string $link
     *
     * @return AggregatorPost
     */
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }

    /**
     * Get link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set postDate
     *
     * @param \DateTime $postDate
     *
     * @return AggregatorPost
     */
    public function setPostDate($postDate)
    {
        $this->postDate = $postDate;

        return $this;
    }

    /**
     * Get postDate
     *
     * @return \DateTime
     */
    public function getPostDate()
    {
        return $this->postDate;
    }

    /**
     * Set account
     *
     * @param \Duf\AggregatorBundle\Entity\AggregatorAccount $account
     *
     * @return AggregatorPost
     */
    public function setAccount(\Duf\AggregatorBundle\Entity\AggregatorAccount $account)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account
     *
     * @return \Duf\AggregatorBundle\Entity\AggregatorAccount
     */
    public function getAccount()
    {
        return $this->account;
    }
}