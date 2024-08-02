<?php

namespace Kraftors\CustomRestApi\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface WishlistInterface extends ExtensibleDataInterface
{
    /**
     * Get wishlist ID.
     *
     * @return int
     */
    public function getId();

    /**
     * Set wishlist ID.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id);

    /**
     * Get customer ID.
     *
     * @return int
     */
    public function getCustomerId();

    /**
     * Set customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId);

    /**
     * Get items.
     *
     * @return \Magento\Wishlist\Api\Data\ItemInterface[]
     */
    public function getItems();

    /**
     * Set items.
     *
     * @param \Magento\Wishlist\Api\Data\ItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
    
}
