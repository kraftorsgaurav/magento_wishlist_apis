<?php

namespace Kraftors\CustomRestApi\Model;

use Magento\Wishlist\Model\Wishlist as MagentoWishlist;
use Kraftors\CustomRestApi\Api\Data\WishlistInterface;

class Wishlist extends MagentoWishlist implements WishlistInterface
{
    /**
     * Get wishlist ID.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData('wishlist_id');
    }

    /**
     * Set wishlist ID.
     *
     * @param int $id
     * @return $this
     */
    public function setId($id)
    {
        return $this->setData('wishlist_id', $id);
    }

    /**
     * Get customer ID.
     *
     * @return int
     */
    public function getCustomerId()
    {
        return $this->getData('customer_id');
    }

    /**
     * Set customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId($customerId)
    {
        return $this->setData('customer_id', $customerId);
    }

    /**
     * Get items.
     *
     * @return \Magento\Wishlist\Api\Data\ItemInterface[]
     */
    public function getItems()
    {
        return $this->getItemCollection()->getItems();
    }

    /**
     * Set items.
     *
     * @param \Magento\Wishlist\Api\Data\ItemInterface[] $items
     * @return $this
     */
    public function setItems(array $items)
    {
        return $this->setData('items', $items);
    }
}
