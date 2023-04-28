<?php


namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Product\IProductReviewFormFactory;
use App\FrontModule\Components\Product\ProductReviewForm;
use App\FrontModule\Components\AvailabilityForm\AvailabilityForm;
use App\FrontModule\Components\AvailabilityForm\IAvailabilityFormFactory;
use App\Model\Attribute\AttributeRepository;
use App\Model\Product\ProductRepository;
use App\Model\Category\CategoryRepository;
use App\Model\ProductGallery\ProductGalleryRepository;
use App\Model\Services\UserManager;
use App\Model\Setting\SettingRepository;
use App\Model\LocaleRepository;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;

class ProductPresenter extends BasePresenter
{
    /**
     * @var ProductRepository
     * @inject
     */
    public $productRepository;

    /**
     * @var CategoryRepository
     * @inject
     */
    public $categoryRepository;

    /**
     * @var ProductGalleryRepository
     * @inject
     */
    public $productGalleryRepository;

    /**
     * @var AttributeRepository
     * @inject
     */
    public $attributeRepository;

    /**
     * @var SettingRepository
     * @inject
     */
    public $settingRepository;

    /**
     * @var LocaleRepository
     * @inject
     */
    public $localeRepository;

    /**
     * @var IAvailabilityFormFactory
     * @inject
     */
    public $availabilityFormFactory;

    /**
     * @var IProductReviewFormFactory
     * @inject
     */
    public $productReviewFormFactory;

    private $productId;

    public function actionDefault($slug)
    {
        if (!$this->getParameter('slug')) {
            throw new BadRequestException();
        }
        $this->productId = $slug;
        $product = $this->productRepository->getById($this->productId)->fetch();
        $this->template->originalPrice = $product->original_price;
        $attributes = $this->attributeRepository->getProductAttributesAsArray($this->productId);
        $this->template->similarProducts = $this->productRepository->getSimilar($product->category_id, $this->productId);
        $this->template->attributes = $attributes;
        $this->template->product = $product;
        $this->template->favorites = $this->productRepository->getFavorites();
        $this->template->freeDeliveryShow = $this->settingRepository->getFreeDeliveryShow();
        $locale = $this->localeRepository->getLocaleByLangId($this->langId);
        $this->template->freeDeliveryRemains = $this->settingRepository->getFreeDelivery($locale) - $this->cartRepository->getTotalPrice();
        $this->template->gallery = $this->productGalleryRepository->getAllForProduct($this->productId);
        $this->template->category = $this->categoryRepository->getById($product->category_id);
        // $this->template->nextProduct = $this->productRepository->getNextProduct($product);
        // $this->template->previousProduct = $this->productRepository->getPreviousProduct($product);
        $this->template->allowEdit = ($this->getUser()->isLoggedIn() && $this->getUser()->getRoles()[0] === UserManager::USER_ADMIN);

        $isDog = false;
        if ($this->user && $this->user->id) {
            $isDog = $this->productRepository->hasProductDog($this->user->id, $this->productId);
        }
        $this->template->isDog = $isDog;

        $category = $product->category;
        $parents = $this->categoryRepository->getParents($category->id);
        $breads = [];
        foreach ($parents as $parent) {
            $breads[$parent->slug] = $parent->name;
        }
        $breads[$product->slug] = $product->name;
        $this->template->breads = $breads;
        $this->template->limits = $this->productRepository->getLimits($product->id);

        $this->template->reviews = $this->productRepository->getProductReviews($this->productId);
        $this->template->currency = $this->localeRepository->getCurrencyByLang($this->getParameter('locale'));
    }

    public function createComponentAvailabilityForm(): AvailabilityForm
    {
        return $this->availabilityFormFactory->create($this->productId);
    }


    /**
     * @return \App\FrontModule\Components\Product\ProductReviewForm
     */
    public function createComponentProductReviewForm(): ProductReviewForm
    {
        $form = $this->productReviewFormFactory->create($this->productId);

        $form->onFormSuccess[] = function () {
            $this->flashMessage('Recenze byla úspěšně přidána! Děkujeme.', 'success');
            if ($this->isAjax()) {
                $this->redrawControl('productReview');
            } else {
                $this->redirect('this');
            }
        };

        return $form;
    }


    public function handleAddFavorite()
    {
        $productId = $this->getParameter('id');
        $added = $this->productRepository->addFavorite($productId);
        $this->payload->favoriteAddMessage = $this->translator->translate('products.' . ($added ? 'added_favourite' : 'removed_favourite'));
    }

    public function handleAddDog()
    {
        $productId = $this->getParameter('id');
        $price = $this->getParameter('price');
        if ($this->productRepository->hasProductDog($this->user->id, $productId)) {
            $this->productRepository->removeProductDogByUserProduct($this->user->id, $productId);
            $this->payload->dogAddMessage = $this->translator->translate('products.watchdog_removed');
        } else {
            $this->productRepository->addProductDog($productId, $this->user->id, $price);
            $this->payload->dogAddMessage = $this->translator->translate('products.watchdog_added');
        }
    }

}