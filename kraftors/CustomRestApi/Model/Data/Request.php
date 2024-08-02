<?php
namespace Kraftors\CustomRestApi\Model\Data;

use Kraftors\CustomRestApi\Api\Data\RequestInterface;
use Magento\Framework\DataObject;

class Request extends DataObject implements RequestInterface
{
    /**
     * @return string
     */
    public function getProduct()
    {
        return $this->getData('product');
    }

    /**
     * @param string $product
     * @return $this
     */
    public function setProduct($product)
    {
        return $this->setData('product', $product);
    }

    /**
     * @return int
     */
    public function getQty()
    {
        return $this->getData('qty');
    }

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty)
    {
        return $this->setData('qty', $qty);
    }

    /**
     * @return array
     */
    public function getCustomAttributes()
    {
        return $this->getData('custom_attributes') ?? [];
    }

    /**
     * @param array $customAttributes
     * @return $this
     */
    public function setCustomAttributes(array $customAttributes)
    {
        return $this->setData('custom_attributes', $customAttributes);
    }
}