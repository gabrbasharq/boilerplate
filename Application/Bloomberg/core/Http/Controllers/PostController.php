<?php

namespace Application\Bloomberg\Http\Controllers;

use Infrastructure\Persistence\Doctrine\Repositories\PostRepo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private PostRepo $repo
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $this->repo->create($request->all());
        return response()->json(['message' => "Created successfully"]);
    }

    public function update(Request $request, int $post): JsonResponse
    {
        $entity = $this->repo->updatePost($request->all(), $post);
        return response()->json(['message' => "Updated successfully", "data" => $entity]);
    }

    public function show($id)
    {
        $entity = $this->repo->findOne($id);
        return $this->sendResponse($entity);
    }
}
