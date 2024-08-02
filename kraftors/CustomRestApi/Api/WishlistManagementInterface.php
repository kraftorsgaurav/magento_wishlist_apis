<?php
namespace Kraftors\CustomRestApi\Api;

interface WishlistManagementInterface
{
    /**
     * @param int $customerId
     * @return \Kraftors\CustomRestApi\Api\Data\WishlistInterface
     */
    public function get(int $customerId);

    /**
     * @param int $customerId
     * @param \Kraftors\CustomRestApi\Api\Data\RequestInterface $item
     * @return \Kraftors\CustomRestApi\Api\Data\WishlistInterface
     */
    public function add(int $customerId, $item);

    /**
     * @param int $customerId
     * @param int $quoteId
     * @param int $itemId
     * @return \Kraftors\CustomRestApi\Api\Data\WishlistInterface
     */
    public function move(int $customerId, int $quoteId, int $itemId);

    /**
     * @param int $customerId
     * @param int $itemId
     * @return bool
     */
    public function delete(int $customerId, int $itemId): bool;
}