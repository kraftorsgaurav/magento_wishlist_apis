<?php

namespace Kraftors\CustomRestApi\Model;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\WishlistFactory;
use Kraftors\CustomRestApi\Api\WishlistManagementInterface;
use Kraftors\CustomRestApi\Api\Data\RequestInterface;
use Magento\Catalog\Helper\Product\Configuration as ProductConfiguration;
use Magento\Catalog\Helper\Product\ConfigurationPool;
use Magento\Framework\App\ObjectManager;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class WishlistManagement implements WishlistManagementInterface
{
    private $wishlistFactory;
    private $productRepository;
    private $eventManager;
    private $productHelper;
    private $configurationPool;
    private $cartRepository;

    public function __construct(
        WishlistFactory $wishlistFactory,
        ProductRepositoryInterface $productRepository,
        ManagerInterface $eventManager,
        ProductConfiguration $productHelper,
        CartRepositoryInterface $cartRepository,
        ConfigurationPool $configurationPool
    ) {
        $this->wishlistFactory = $wishlistFactory;
        $this->productRepository = $productRepository;
        $this->eventManager = $eventManager;
        $this->cartRepository = $cartRepository;
        $this->productHelper = $productHelper;
        $this->configurationPool = $configurationPool;
    }

    public function get(int $customerId)
    {
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId);
        if (!$wishlist->getId()) {
            $wishlistnot[] = [
                'wishlist_id' => null,
                'customer_id' => $customerId,
                'is_shared' => null,
                'sharing_code' => null,
                'items' => [],
                'error' => ['Customer does not yet have a wishlist']
            ];
            return $wishlistnot;
        }

        $objectManager = ObjectManager::getInstance();
        $storeManager = $objectManager->get(\Magento\Store\Model\StoreManagerInterface::class);
        $store = $storeManager->getStore();
        $storeUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $mediaUrl = $storeUrl . 'catalog/product/';
        $currencyCode = $store->getCurrentCurrencyCode();

        $productItems = [];
        foreach ($wishlist->getItemCollection() as $item) {
            $productId = $item->getProductId();
            try {
                $product = $this->productRepository->getById($productId);

                $productItems[] = [
                    "wishlist_item_id" => (int)$item->getId(),
                    "product_id" => (int)$productId,
                    "store_id" => (int)$item->getStoreId(),
                    "added_at" => $item->getAddedAt(),
                    "qty" => (float)$item->getQty(),
                    "product_name" => $product->getName(),
                    "currency_type" => $currencyCode,
                    "price" => (float)number_format($item->getProduct()->getFinalPrice(), 2, '.', ''),
                    "special_price" => (float)number_format($product->getSpecialPrice() ?? 0, 2, '.', ''),
                    "image" => $product->getData('image') ? $mediaUrl . $product->getData('image') : "",
                    "thumbnail" => $product->getData('thumbnail') ? $mediaUrl . $product->getData('thumbnail') : "",
                    "small_image" => $product->getData('small_image') ? $mediaUrl . $product->getData('small_image') : "",
                    "product_type" => $product->getTypeId()
                ];
            } catch (\Exception $e) {
                // Log exception
            }
        }

        $productData[] = [
            'wishlist_id' => (int)$wishlist->getId(),
            'customer_id' => (int)$wishlist->getCustomerId(),
            'items' => $productItems
        ];
        return $productData;
    }

    private function getConfiguredOptions($item, $helperInstance)
    {
        $helper = $this->configurationPool->getByProductType($helperInstance);
        $options = $helper->getOptions($item);
        foreach ($options as $index => $option) {
            if (is_array($option) && array_key_exists('value', $option)) {
                if (!(array_key_exists('has_html', $option) && $option['has_html'] === true)) {
                    if (is_array($option['value'])) {
                        foreach ($option['value'] as $key => $value) {
                            $option['value'][$key] = strip_tags($value);
                        }
                    }
                }
                $options[$index]['value'] = $option['value'];
            }
        }
        return $options;
    }

    public function add(int $customerId, $item)
    {
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        $product = $this->productRepository->get($item->getProduct());
        if (!$product->isVisibleInCatalog()) {
            throw new LocalizedException(__("Sorry, this item can't be added to wishlists"));
        }
        $buyRequest = new DataObject();
        $customAttributes = $item->getCustomAttributes();
        if ($customAttributes) {
            $this->processBuyRequestAttributes($buyRequest, $customAttributes);
        }
        $result = $wishlist->addNewItem($product, $buyRequest);
        if (is_string($result)) {
            throw new LocalizedException(__($result));
        }
        if ($wishlist->isObjectNew()) {
            $wishlist->save();
        }
        $this->eventManager->dispatch(
            'wishlist_add_product',
            ['wishlist' => $wishlist, 'product' => $product, 'item' => $result]
        );

        // Fetch the added item details and format the response
        $item = $wishlist->getItemCollection()->getLastItem();
        $productId = $item->getProductId();
        $product = $this->productRepository->getById($productId);

        $storeManager = ObjectManager::getInstance()->get(\Magento\Store\Model\StoreManagerInterface::class);
        $storeUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        $store = $storeManager->getStore();
        $mediaUrl = $storeUrl . 'catalog/product/';
        $currencyCode = $store->getCurrentCurrencyCode();

        $addWishlistData[] = [
            "wishlist_item_id" => (int)$item->getId(),
            "wishlist_id" => (int)$wishlist->getId(),
            "product_id" => (int)$productId,
            "store_id" => (int)$item->getStoreId(),
            "added_at" => $item->getAddedAt(),
            "description" => null,
            "qty" => (string)$item->getQty(),
            "product" => [],
            "product_name" => $product->getName(),
            "name" => $product->getName(),
            "currency_type" => $currencyCode, 
            "price" => (string)number_format($item->getProduct()->getFinalPrice(), 6, '.', '')
        ];
        return $addWishlistData;
    }

    public function move(int $customerId, int $quoteId, int $wishlistItemId)
    {
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
        $buyRequest = new DataObject();
        $quote = $this->cartRepository->get($quoteId);
        $status = false;

        try {
            $item = $wishlist->getItem($wishlistItemId);
            if (!$item) {
                throw new NoSuchEntityException(__('No item with ID %1', $wishlistItemId));
            }

            $product = $this->productRepository->getById($item->getProductId());
            if (!$product->isVisibleInCatalog()) {
                throw new LocalizedException(__("Sorry, this item can't be added to the cart"));
            }

            $buyRequest = $item->getBuyRequest();
            $quoteItem = $quote->addProduct($product, $buyRequest);

            if (is_string($quoteItem)) {
                throw new LocalizedException(__($quoteItem));
            }

            // Save the quote to ensure the item is added
            $quote->collectTotals()->save();

            // Remove the item from the wishlist
            $item->delete();
            $wishlist->save();

            $status = true;
        } catch (\Exception $e) {
            // Log the exception for debugging
            error_log($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }

        return $status ? ['success' => true] : ['success' => false];
    }




    public function delete(int $customerId, int $itemId): bool
    {
        $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId);
        $item = $wishlist->getItem($itemId);
        if (!$item) {
            throw new NoSuchEntityException(__('No item with ID %1', $itemId));
        }
        $item->delete();
        return true;
    }

    private function getHelperInstance(string $productType): string
    {
        switch ($productType) {
            case "bundle":
                return \Magento\Bundle\Helper\Catalog\Product\Configuration::class;
            case "downloadable":
                return \Magento\Downloadable\Helper\Catalog\Product\Configuration::class;
            default:
                return \Magento\Catalog\Helper\Product\Configuration::class;
        }
    }

    private function processBuyRequestAttributes(DataObject $buyRequest, array $customAttributes)
    {
        $superAttributes = [];
        $bundleOptionQtys = [];
        $bundleOptions = [];

        foreach ($customAttributes as $customAttribute) {
            if (strpos($customAttribute->getAttributeCode(), 'super_attribute_') === 0) {
                $superAttributeId = str_replace('super_attribute_', '', $customAttribute->getAttributeCode());
                $superAttributes[$superAttributeId] = $customAttribute->getValue();
            } elseif (strpos($customAttribute->getAttributeCode(), 'bundle_option_qty_') === 0) {
                $bundleOptionQty = str_replace('bundle_option_qty_', '', $customAttribute->getAttributeCode());
                $bundleOptionQtys[$bundleOptionQty] = $customAttribute->getValue();
            } elseif (strpos($customAttribute->getAttributeCode(), 'bundle_option_') === 0) {
                $bundleOption = str_replace('bundle_option_', '', $customAttribute->getAttributeCode());
                $bundleOption = explode('_', $bundleOption);
                if (count($bundleOption) === 1) {
                    $bundleOptions[$bundleOption[0]] = $customAttribute->getValue();
                } elseif (count($bundleOption) === 2) {
                    $bundleOptions[$bundleOption[0]][$bundleOption[1]] = $customAttribute->getValue();
                }
            }
        }

        if ($superAttributes) {
            $buyRequest->setData('super_attribute', $superAttributes);
        }
        if ($bundleOptionQtys) {
            $buyRequest->setData('bundle_option_qty', $bundleOptionQtys);
        }
        if ($bundleOptions) {
            $buyRequest->setData('bundle_option', $bundleOptions);
        }
    }

    private function processBuyRequestOptions(DataObject $buyRequest)
    {
        $options = $buyRequest->getOptions();
        if ($options) {
            foreach ($options as $key => $option) {
                if (is_array($option) && isset($option['date_internal'])) {
                    $options[$key] = $option['date_internal'];
                }
            }
            $buyRequest->setData('options', $options);
        }
    }
}
