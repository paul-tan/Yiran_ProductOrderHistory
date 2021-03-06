<?php

class Yiran_ProductOrderHistory_Block_History extends Mage_Core_Block_Template
{
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $pager = $this->getLayout()->createBlock('page/html_pager', 'custom.pager');
        $pager->setAvailableLimit([10=>10]);
        $pager->setShowAmounts(false);
        $pager->setCollection($this->getHistoryCollection());

        $this->setChild('pager', $pager);
        // $this->getCollection()->load();

        return $this;
    }

    public function getPagerHtml()
    {
        return $this->getChildHtml('pager');
    }

    function getCurrentProductId()
    {
        $currentProduct = Mage::registry('current_product');

        if ($currentProduct) {
            $productId = $currentProduct->getId();
        }

        return $productId;
    }

    public function getHistoryCollection()
    {
        $collection = Mage::getModel('sales/order')->getCollection();
        $collection->addFieldToSelect(['state', 'status','customer_firstname', 'customer_lastname', 'created_at']);

        $collection->join(['order_item' => 'sales/order_item'], 'order_item.order_id = main_table.entity_id', ['product_id', 'sum(qty_ordered) AS qty'])
            ->join(['order_address' => 'sales/order_address'], 'order_address.parent_id = order_item.order_id', ['parent_id', 'country_id']);

        $productId = $this->getCurrentProductId();

        $collection->addAttributeToFilter('product_id', $productId)
            ->addAttributeToFilter('address_type', 'shipping')
            ->addAttributeToFilter('status', ['complete', 'processing'])
            ->addAttributeToSort('created_at', 'DESC')
            ->distinct(TRUE);

        $collection->getSelect()->group('main_table.entity_id');

        // $this->setCollection($collection);

        return $collection;
    }

    public function getProductSoldQuantity($productId = '')
    {
        if ($productId == '') {
            $productId = $this->getCurrentProductId();
        }

        $collection = Mage::getModel('sales/order_item')->getCollection();
        $collection->getSelect()->columns('SUM(qty_invoiced) AS qty');
        $product = $collection->addFieldToFilter('product_id', $productId)->getFirstItem();

        $qty_invoiced = $product->qty;

        return (int)$qty_invoiced;
    }
}