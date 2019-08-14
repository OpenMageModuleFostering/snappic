<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class Altolabs_Snappic_Model_Observer
{
    /**
     * Defunct!
     * @return self
     */
    public function createCustomerSession(Varien_Event_Observer $observer) { return $this; }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onControllerActionPredispatch(Varien_Event_Observer $observer)
    {
        $session = Mage::getSingleton('core/session');
        $url = Mage::helper('core/url')->getCurrentUrl();
        $landingPage = $session->getLandingPage();
        if (!$landingPage || strpos($url, '/shopinsta') !== false) {
            $session->setLandingPage($url);
        }
        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onAfterProductAddToCart(Varien_Event_Observer $observer)
    {
        Mage::getSingleton('core/session')->setCartProductJustAdded(true);
        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onAfterOrderPlace(Varien_Event_Observer $observer)
    {
        /** @var Mage_Sales_Model_Order $order */
        $order = $observer->getEvent()->getOrder();
        $sendable = $this->getHelper()->getSendableOrderData($order);
        $this->getConnect()
            ->setSendable($sendable)
            ->notifySnappicApi('orders/paid');
        return $this;
    }


    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onAdminPageDisplayed(Varien_Event_Observer $observer) {
        if (!Mage::getSingleton('admin/session')->isLoggedIn()) { return; }
        $flag = Mage::getModel('core/variable')->loadByCode('snappic_completion_message');
        if ($flag->getPlainValue() == 'displayed') {
            return $this;
        } elseif (!$flag->getId()) {
          $flag->setCode('snappic_completion_message')
               ->setPlainValue('displayed')
               ->save();
        }

        $domain = $this->getHelper()->getDomain();
        $consumer = Mage::getModel('oauth/consumer')->load('Snappic', 'name');
        $secret = urlencode($consumer->getSecret());
        $link = 'https://'.$domain.'/shopinsta/oauth?secret='.$secret;
        $html = <<<HTML
<img src="http://snappic.io/static/img/general/logo.svg" style="padding:10px;background-color:#E85B52;">
<div style="font-size:16px;font-weight:400;letter-spacing:1.2px;line-height: 1.2;border:0;padding:0;margin:24px 4px">Almost done!</div>
<script>window.Snappic={};window.Snappic.signup=function(){window.location='$link';};</script>
<img src="http://store.snappic.io/images/magento_continue_signup.png" style="width:100%;max-width:460px;cursor:pointer;" onclick="Snappic.signup()">
HTML;
        Mage::getSingleton('adminhtml/session')->addSuccess($html);
        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onProductAfterSave(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        if ($product->hasDataChanges()) {
            $product->load($product->getId());
            $this->getConnect()
                ->setSendable(array($this->getHelper()->getSendableProductData($product)))
                ->notifySnappicApi('products/update');
        }

        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onProductAfterAttributeUpdate(Varien_Event_Observer $observer)
    {
        $productIds = $observer->getEvent()->getProductIds();
        /** @var Mage_Catalog_Model_Product $productModel */
        $productModel = Mage::getModel('catalog/product');
        $sendable = array();

        foreach ($productIds as $id) {
            $product = $productModel->load($id);
            $sendable[] = $this->getHelper()->getSendableProductData($product);
        }

        $this->getConnect()->setSendable($sendable)->notifySnappicApi('products/update');
        return $this;
    }

    /**
     * @param  Varien_Event_Observer $observer
     * @return self
     */
    public function onProductAfterDelete(Varien_Event_Observer $observer)
    {
        /** @var Mage_Catalog_Model_Product $product */
        $product = $observer->getEvent()->getProduct();
        $sendable = array($this->getHelper()->getSendableProductData($product));
        $this->getConnect()
            ->setSendable($sendable)
            ->notifySnappicApi('products/delete');

        return $this;
    }

    /**
     * Get an instance of the Snappic Connect model.
     *
     * @return AltoLabs_Snappic_Model_Connect
     */
    public function getConnect()
    {
        return Mage::getSingleton('altolabs_snappic/connect');
    }

    /**
     * Get an instance of the Snappic data structure helper.
     *
     * @return AltoLabs_Snappic_Helper_Data
     */
    public function getHelper()
    {
        return Mage::helper('altolabs_snappic');
    }
}
