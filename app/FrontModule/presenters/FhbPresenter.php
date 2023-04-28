<?php


namespace App\FrontModule\Presenters;


use App\Model\Fhb\FhbService;
use App\Model\Order\OrderRepository;
use App\Model\Product\ProductRepository;
use Tracy\Debugger;

class FhbPresenter extends BasePresenter
{

    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var OrderRepository
     * @inject
     */
    public $orderRepository;

    /**
     * @var FhbService
     * @inject
     */
    public $fhbService;

    public function actionDefault($id = null)
    {

    }

    public function actionProduct($id = null)
    {
        Debugger::log('id: ' . $id, 'fhb-backapi-product');
        Debugger::log('method: ' . $this->request->method, 'fhb-backapi-product');
        Debugger::log('post: ' . serialize($this->request->post), 'fhb-backapi-product');
        Debugger::log('parameters: ' . serialize($this->request->parameters), 'fhb-backapi-product');
        $post = $this->request->post;
        $this->productRepository->updateStockCount($id, $post['stockQuantity']);
        $this->terminate();
    }

    public function actionConfirmed($id)
    {
        $this->orderRepository->setStatus($id, OrderRepository::STATUS_PROCESSING);
    }

    public function actionSent($id)
    {
        $this->orderRepository->setStatus($id, OrderRepository::STATUS_SENT);
        $this->fhbService->saveTrackingLink($id);
    }

    public function actionDelivered($id)
    {
        $this->orderRepository->setStatus($id, OrderRepository::STATUS_DELIVERED);
    }

    public function actionReturned($id)
    {
        $this->orderRepository->setStatus($id, OrderRepository::STATUS_RETURNED);
    }
}