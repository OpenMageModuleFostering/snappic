<?php
/**
 * This file is Copyright AltoLabs 2016.
 *
 * @category Mage
 * @package  AltoLabs_Snappic
 * @author   AltoLabs <hi@altolabs.co>
 */

class AltoLabs_Snappic_InventoryController extends Mage_Core_Controller_Front_Action
{
    public function indexAction()
    {
        $core = Mage::helper('core');
        $helper = Mage::helper('altolabs_snappic');
        $payload = $core->jsonDecode($this->getRequest()->getRawBody());
        $skus = $payload['skus'];
        $quantities = array();
        foreach ($skus as $sku) {
            $quantities[$sku] = $helper->getProductStockBySku($sku);
        }
        $this->getResponse()->setHeader('Content-type', 'application/json');
        $this->getResponse()->setBody(json_encode(array(
            'status' => 'success',
            'quantities' => $quantities
        )));
    }
}
