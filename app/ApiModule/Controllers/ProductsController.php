<?php


namespace App\ApiModule\Controllers;

use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Model\Mall\MallRepository;
use App\Model\Product\ProductFacade;

/**
 * @ControllerPath("/products")
 */
final class ProductsController extends BaseV1Controller
{

    /**
     * @var ProductFacade
     * @inject
     */
    public $productFacade;

    /**
     * @Path("/{locale}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="locale", type="string", description="Locale")
     *     })
     */
    public function products(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $locale = $request->getParameter('locale');
        $products = $this->productFacade->getAllJson($locale);
        return $response->writeJsonBody($products);
    }

    /**
     * @param ApiResponse $response
     * @return ApiResponse
     * @Path("/{locale}/{id}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="id", type="int", description="Product id"),
     *     @RequestParameter(name="locale", type="string", description="Locale")
     *     })
     */
    public function detailProduct(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $id = $request->getParameter('id');
        $locale = $request->getParameter('locale');
        $product = $this->productFacade->getDetailJson($locale, $id);
        return $response->writeJsonBody($product);
    }
}