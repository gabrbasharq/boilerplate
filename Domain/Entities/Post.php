<?php

namespace Domain\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="posts")
 * @ORM\HasLifecycleCallbacks()
 */
class Post
{
    /**
     * @var integer $id
     * @ORM\Column(name="id", type="integer", unique=true, nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(type="string")
     */
    private string $title;

    /**
     * @ORM\Column(type="text")
     */
    private string $body;

    /**
     * Get $id
     *
     * @return  integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set $id
     *
     * @param  integer  $id  $id
     *
     * @return  self
     */
    public function setId($id): Post
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get the value of title
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Set the value of title
     *
     * @return  self
     */
    public function setTitle($title): Post
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get the value of body
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set the value of body
     *
     * @return  self
     */
    public function setBody($body): Post
    {
        $this->body = $body;

        return $this;
    }
}
