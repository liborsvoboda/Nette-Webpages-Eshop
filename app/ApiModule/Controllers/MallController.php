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

/**
 * @ControllerPath("/mall")
 */
final class MallController extends BaseV1Controller
{

    /**
     * @var MallRepository
     * @inject
     */
    public $mallRepository;

    /**
     * @Path("/")
     * @Method("GET")
     */
    public function index(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        return $response->writeBody('OK');
    }

    /**
     * @param ApiResponse $response
     * @return ApiResponse
     * @Path("/category/{lang}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="lang", type="string", description="Language")
     *     })
     */
    public function category(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $lang = $request->getParameter('lang');
        $categories = $this->mallRepository->getAll($lang);
        return $response->writeJsonBody($categories);
    }
}