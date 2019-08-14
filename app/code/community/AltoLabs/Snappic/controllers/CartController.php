<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_CartController extends Mage_Core_Controller_Front_Action
{

    protected $_cookieCheckActions = array('add', 'total', 'clear');

    public function preDispatch()
    {
        parent::preDispatch();
        $cart = $this->_getCart();
        if ($cart->getQuote()->getIsMultiShipping()) {
            $cart->getQuote()->setIsMultiShipping(false);
        }
        return $this;
    }

    public function totalAction()
    {
        $this->_output(array(
            'status' => 'success',
            'total' => ($this->_getCart()->getQuote()->getSubtotal() ?: '0.00')
        ));
    }

    public function addAction()
    {
        $cart = $this->_getCart();
        $payload = Mage::helper('core')->jsonDecode($this->getRequest()->getRawBody());
        $product = Mage::helper('altolabs_snappic')
                      ->getProductBySku($payload['sku'])
                      ->setStoreId(Mage::app()->getStore()->getId());
        if ($product->getId()) {
            try {
                $cart->addProduct($product);
                if (!$cart->getCustomerSession()->getCustomer()->getId() && $cart->getQuote()->getCustomerId()) {
                    $cart->getQuote()->setCustomerId(null);
                }
                $cart->save();
                $this->_getSession()->setCartWasUpdated(true);
                Mage::dispatchEvent('checkout_cart_add_product_complete', array(
                    'product' => $product,
                    'request' => $this->getRequest(),
                    'response' => $this->getResponse())
                );
                $this->_output(array(
                    'status' => 'success',
                    'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
                ));
            } catch (Exception $e) {
                $this->_output(array(
                    'error' => $e->getMessage(),
                    'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
                ));
            }
        } else {
            $this->_output(array(
                'error' => 'The product was not found.',
                'total' => ($cart->getQuote()->getSubtotal() ?: '0.00')
            ));
        }
    }

    public function clearAction() {
        $this->_getCart()->truncate()->save();
        $this->_getSession()->setCartWasUpdated(true);
        $this->_output(array(
            'status' => 'success',
            'total' => ($this->_getCart()->getQuote()->getSubtotal() ?: '0.00')
        ));
    }

    protected function _getSession()
    {
        return Mage::getSingleton('checkout/session');
    }

    protected function _getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    protected function _output($data)
    {
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode($data));
    }
}
