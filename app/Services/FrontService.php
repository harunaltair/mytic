<?php

namespace App\Services;

use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Repositories\Contracts\WorkshopRepositoryInterface;

class FrontService
{
    protected $categoryRepository;
    protected $workshopRepository;

    public function __construct(CategoryRepositoryInterface $categoryRepository, WorkshopRepositoryInterface $workshopRepository)
    {
        $this->categoryRepository = $categoryRepository;
        $this->workshopRepository = $workshopRepository;
    }

    public function getFrontPageData()
    {
        $categories = $this->categoryRepository->getAllCategories();
        $workshops = $this->workshopRepository->getAllNewWorkshops();

        return compact('categories', 'workshops');
    }
}
