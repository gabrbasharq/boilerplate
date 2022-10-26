<?php

namespace Infrastructure\Persistence\Doctrine\Repositories;

use Domain\Entities\Post;
use Doctrine\ORM\EntityManagerInterface;

class PostRepo extends AbstractRepository
{
    protected string $entityNamespace = "Domain\Entities\Post";

    public function __construct(
        protected EntityManagerInterface $em
    ) {
        parent::__construct($em, $this->entityNamespace);
    }

    public  function create(array $data)
    {
        $post = $this->prepareData($data);
        $this->save($post);
    }

    public function updatePost(array $data, int $postId)
    {
        $post = $this->find($postId);
        return $this->update($post, $data);
    }

    public function findOne(int $id)
    {
        return $this->find($id);
    }

    public function deletePost(Post $post)
    {
        // $this->em->remove($post);
        // $this->em->flush();
    }

    public function prepareData(array $data)
    {
        return new Post($data);
    }
}
