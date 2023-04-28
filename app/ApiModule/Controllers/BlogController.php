<?php


namespace App\ApiModule\Controllers;

use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Model\Blog\BlogFacade;

/**
 * @ControllerPath("/blog")
 */

class BlogController  extends BaseV1Controller {
    /**
     * @var BlogFacade
     * @inject
     */
    public $blogFacade;
    
     /**
     * @Path("/{locale}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="locale", type="string", description="Locale")
     *     })
     */
    public function pages(ApiRequest $request, ApiResponse $response): ApiResponse
    {
    
        $locale = $request->getParameter('locale');
        
        $blogPages = $this->blogFacade->getAllJson($locale);
        return $response->writeJsonBody($blogPages);
    }
    
    /**
     * @param ApiResponse $response
     * @return ApiResponse
     * @Path("/{locale}/{slug}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="slug", type="string", description="slug"),
     *     @RequestParameter(name="locale", type="string", description="Locale")
     *     })
     */
    public function detailPage(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $slug = $request->getParameter('slug');
        $locale = $request->getParameter('locale');
        $blogPage = $this->blogFacade->getDetailJson($locale, $slug);
        return $response->writeJsonBody($blogPage);
    }
}
