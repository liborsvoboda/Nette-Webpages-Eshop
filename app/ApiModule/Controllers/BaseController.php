<?php


namespace App\ApiModule\Controllers;


use Apitte\Core\Annotation\Controller\GroupPath;
use Apitte\Core\UI\Controller\IController;
use App\Model\DbRepository;

/**
 * @GroupPath("/api")
 */
abstract class BaseController implements IController
{

}