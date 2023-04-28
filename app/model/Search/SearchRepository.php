<?php


namespace App\Model\Search;


use App\Model\BaseRepository;
use App\Model\Category\CategoryRepository;
use App\Model\Product\ProductRepository;

class SearchRepository extends BaseRepository
{

    protected $productRepository, $categoryRepository;

    public function __construct(ProductRepository $productRepository, CategoryRepository $categoryRepository)
    {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
    }

    public function search($string)
    {
        if (strlen($string) < 3) {
            return null;
        }
        $out = null;
        $products = $this->productRepository->search($string, 7);
        $categories = $this->categoryRepository->search($string, 3);
        if($products) {
            foreach ($products as $product) {
                $out[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'img' => $product->image,
                    'slug' => $product->slug,
                    'type' => 'product'
                ];
            }
        }
        if($categories) {
            foreach ($categories as $category) {
                $out[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'img' => '',
                    'slug' => $category->slug,
                    'type' => 'category'
                ];
            }
        }
    return $out;
    }
}
