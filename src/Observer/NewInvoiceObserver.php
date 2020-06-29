<?php

namespace MyTinyWMS\API\Observer;

use GuzzleHttp\Client;
use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\Response;
use Magento\Sales\Model\Order;
use GuzzleHttp\Psr7\ResponseFactory;
use GuzzleHttp\Exception\GuzzleException;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Webapi\Rest\Request;
use MyTinyWMS\API\Helper\APIService;

class NewInvoiceObserver implements ObserverInterface {

    /**
     * @var APIService
     */
    protected $apiService;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * NewInvoiceObserver constructor.
     *
     *
     * @param APIService $apiService
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(APIService $apiService, \Magento\Store\Model\StoreManagerInterface $storeManager) {
        $this->apiService = $apiService;
        $this->storeManager = $storeManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {
        /** @var Order $order */
        $order = $observer->getEvent()['invoice']->getOrder();
        $items = $order->getAllVisibleItems();

        $note = 'Order '.$order->getRealOrderId().' in '.$this->storeManager->getStore()->getName();
        foreach($items as $item) {
            $this->apiService->changeQuantity($item->getSKU(), $item->getQtyInvoiced(), $note);
        }
    }
}