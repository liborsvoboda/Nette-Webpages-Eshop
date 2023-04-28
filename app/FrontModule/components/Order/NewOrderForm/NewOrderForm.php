<?php

namespace App\FrontModule\Components\Order;

use App\Model\Cart\CartRepository;
use App\Model\Customer\CustomerRepository;
use App\Model\Factory\FormFactory;
use App\Model\Product\ProductRepository;
use App\Model\User\UserRepository;
use App\Model\LocaleRepository;
use Nette\Application\UI\Control;
use Nette\Application\UI\Form;
use Nette\Security\User;
use Nextras\FormsRendering\Renderers\Bs4FormRenderer;
use Nextras\FormsRendering\Renderers\FormLayout;

class NewOrderForm extends Control
{
    private UserRepository $userRepository;
    private ProductRepository $productRepository;
    private FormFactory $formFactory;
    private CustomerRepository $customerRepository;
    private User $user;
    private $userData = null;
    private CartRepository $cartRepository;
    private LocaleRepository $localeRepository;

    public $onDone = [];

    public function __construct(UserRepository $userRepository,
                                ProductRepository $productRepository,
                                FormFactory $formFactory,
                                CustomerRepository $customerRepository,
                                CartRepository $cartRepository,
                                User $user,
                                LocaleRepository $localeRepository)
    {
        $this->userRepository = $userRepository;
        $this->productRepository = $productRepository;
        $this->formFactory = $formFactory;
        $this->customerRepository = $customerRepository;
        $this->user = $user;
        $this->cartRepository = $cartRepository;
        $this->localeRepository = $localeRepository;
    }

    public function createComponentForm()
    {
        $users = $this->customerRepository->getSubIdsArray($this->user->getId(), true);
        $form = $this->formFactory->create();
        $form->addSelect('user', 'Objednávka pre:', $users)->setTranslator(null)->setHtmlAttribute('class', 'select2 customer-select');
        $products = $this->productRepository->getActive();
        foreach ($products as $product) {
          $currency = $this->localeRepository->getCurrencySymbolByLangId($product->lang_id);
          $form->addText('id'.$product->id, $product->name.' ('.strval($product->price_vat).' '.$currency.($product->unit ? ' / '.$product->unit : '').')')->setDefaultValue(0)->setHtmlType('number')->setHtmlAttribute('min', '0');
        }
        $form->setRenderer(new Bs4FormRenderer(FormLayout::VERTICAL));
        $form->addSubmit('submit', 'Do košíka');
        $form->onSuccess[] = [$this, 'formSuccess'];
        return $form;
    }

    public function handleGetUserData()
    {
        $id = $_GET['id'];
        $this->userData = $this->userRepository->getById($id)->fetch();
        $this->redrawControl('userData');
    }

    public function formSuccess(Form $form)
    {
        $values = $form->getValues();
        $this->cartRepository->clearCart();
        $userId = $values->user;
        unset($values->user);
        $this->userRepository->loginAsUser($userId);
        usleep(100000);
        $this->userRepository->unsetInfoSession();
        $this->userRepository->copyInfoToSession($userId);
        foreach ($values as $id => $amount) {
            if($amount < 1) {
                continue;
            }
            $this->cartRepository->addToCart(str_replace('id', '', $id), $amount);
        }
        $this->onDone();
    }

    public function render()
    {
        if($this->userData == null) {
            $this->userData = $this->userRepository->getById($this->user->id)->fetch();
        }
        $this->template->userData = $this->userData;
        $this->template->render(__DIR__.'/templates/newOrderForm.latte');
    }
}