<?php

namespace App\ApiModule\Controllers;

use Apitte\Core\Annotation\Controller\ControllerPath;
use Apitte\Core\Annotation\Controller\Method;
use Apitte\Core\Annotation\Controller\Path;
use Apitte\Core\Annotation\Controller\RequestParameters;
use Apitte\Core\Annotation\Controller\RequestParameter;
use Apitte\Core\Http\ApiRequest;
use Apitte\Core\Http\ApiResponse;
use App\Model\Menu\MenuFacade;

/**
 * @ControllerPath("/menuItems")
 */
class MenuController extends BaseV1Controller {

    /**
     * @var MenuFacade
     * @inject
     */
    public $menuFacade;

    /**
     * @Path("/{locale}")
     * @Method("GET")
     * @RequestParameters({
     *     @RequestParameter(name="locale", type="string", description="Locale")
     *     })
     */
    public function menuItems(ApiRequest $request, ApiResponse $response): ApiResponse {
        $locale = $request->getParameter('locale');
        $menuItems = $this->menuFacade->getAllJson($locale);
        return $response->writeJsonBody($menuItems);
    }

}
