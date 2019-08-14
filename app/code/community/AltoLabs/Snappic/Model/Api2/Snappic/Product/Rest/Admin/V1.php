<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_Model_Api2_Snappic_Product_Rest_Admin_V1 extends Mage_Catalog_Model_Api2_Product_Rest_Admin_V1
{
    /**
     * Collection page sizes.
     *
     * @var int
     */
    const PAGE_SIZE_DEFAULT = 100;

    /**
     * Retrieve list of products.
     *
     * @return array
     */
    protected function _retrieveCollection()
    {
        $this->_setDefaultPageSize();
        /** @var Mage_Catalog_Model_Resource_Product_Collection $collection */
        $collection = Mage::getResourceModel('catalog/product_collection');
        $store = $this->_getStore();
        $collection->setStoreId($store->getId());
        $collection->addAttributeToSelect(
            array_keys(
                $this->getAvailableAttributes(
                    $this->getUserType(),
                    Mage_Api2_Model_Resource::OPERATION_ATTRIBUTE_READ
                )
            )
        );

        $this->_applyCategoryFilter($collection);
        $this->_applyCollectionModifiers($collection);
        // We only want configurables
        $collection->addFieldToFilter('type_id', Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE);

        $output = array();
        foreach ($collection as $product) { /* @var Mage_Catalog_Model_Product $product */
            $product->load($product->getId());
            $output[] = Mage::helper('altolabs_snappic')->getSendableProductData($product);
        }

        return (array) $output;
    }

    /**
     * LSB prevents us from using ::PAGE_SIZE_DEFAULT so must set the size to the query string if it's not
     * there already.
     *
     * @return self
     */
    protected function _setDefaultPageSize()
    {
        if (!$this->getRequest()->getPageSize()) {
            $this->getRequest()->setQuery(Mage_Api2_Model_Request::QUERY_PARAM_PAGE_SIZE, self::PAGE_SIZE_DEFAULT);
        }

        return $this;
    }

    /**
     * Retrieve a single product.
     *
     * @return array
     */
    protected function _retrieve()
    {
        $product = $this->_getProduct();
        $helper = Mage::helper('altolabs_snappic');

        return (array) $helper->getSendableProductData($product);
    }
}
