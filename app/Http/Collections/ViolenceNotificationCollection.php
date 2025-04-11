<?php

namespace App\Http\Collections;

use App\Http\Resources\ViolenceNotificationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ViolenceNotificationCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'violence_notifications' => ViolenceNotificationResource::collection($this->collection),
            'links' => $this->paginationLinks($request),
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
            ],
        ];
    }

    protected function paginationLinks($request)
    {
        $paginator = $this->resource;
        $limit = $request->get('limit', $this->perPage());
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $baseUrl = $paginator->path();
        $nextPage = $currentPage + 1;

        $links = null;
        if ($nextPage <= $lastPage) {
            $links = $baseUrl . '?' . http_build_query([
                'page' => $nextPage,
                'limit' => $limit,
            ]);
        }

        return $links;
    }
}
