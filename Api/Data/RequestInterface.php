<?php
namespace Kraftors\CustomRestApi\Api\Data;

interface RequestInterface
{
    /**
     * @return string
     */
    public function getProduct();

    /**
     * @param string $product
     * @return $this
     */
    public function setProduct($product);

    /**
     * @return int
     */
    public function getQty();

    /**
     * @param int $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * @return array
     */
    public function getCustomAttributes();

    /**
     * @param array $customAttributes
     * @return $this
     */
    public function setCustomAttributes(array $customAttributes);
}