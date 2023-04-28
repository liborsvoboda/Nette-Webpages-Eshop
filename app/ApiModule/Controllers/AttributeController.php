<?php


namespace App\ApiModule\Controllers;

use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Model\Attribute\AttributeFacade;

/**
 * @ControllerPath("/attributes")
 */
class AttributeController extends BaseV1Controller {
    /**
     * @var AttributeFacade
     * @inject
     */
    public $attributeFacade;

    /**
     * @Path("/{locale}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="locale", type="string", description="Locale")
     *     })
     */
    public function attributes(ApiRequest $request, ApiResponse $response): ApiResponse
    {
        $locale = $request->getParameter('locale');
        $attributes = $this->attributeFacade->getAllJson($locale);
        return $response->writeJsonBody($attributes);
    }
}
