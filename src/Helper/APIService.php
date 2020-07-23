<?php

namespace MyTinyWMS\API\Helper;

use GuzzleHttp\ClientFactory;
use GuzzleHttp\Psr7\ResponseFactory;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use GuzzleHttp\Psr7\Response;
use Magento\Framework\Webapi\Rest\Request;
use GuzzleHttp\Exception\GuzzleException;

class APIService extends AbstractHelper {

    /**
     * @var ClientFactory
     */
    protected $clientFactory;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var \MyTinyWMS\API\Helper\Data
     */
    protected $helperData;

    /**
     * APIService constructor.
     *
     * @param Context $context
     * @param \MyTinyWMS\API\Helper\Data $helperData
     * @param ResponseFactory $responseFactory
     * @param ClientFactory $clientFactory
     */
    public function __construct(Context $context, \MyTinyWMS\API\Helper\Data $helperData, ResponseFactory $responseFactory, ClientFactory $clientFactory) {
        $this->clientFactory = $clientFactory;
        $this->responseFactory = $responseFactory;
        $this->helperData = $helperData;

        parent::__construct($context);
    }

    /**
     * @param string $sku
     * @param integer $quantity
     * @param string $note
     * @return bool
     */
    public function changeQuantity($sku, $quantity, $note) {
        /**
         * check if we have a article group
         */
        $response = $this->doRequest('article-group', ['filter[external_article_number]' => $sku]);
        if ($response->getStatusCode() == 200) {
            $articleGroups = json_decode($response->getBody()->getContents(), true);
            if (is_array($articleGroups) && count($articleGroups) == 1) {
                $id = $articleGroups[0]['id'];
                $this->_logger->info('Updating Article Group '.$id.' in MyTinyWMS');
                $response = $this->doRequest("article-group/{$id}/changeQuantity", [
                    'change' => $quantity,
                    'note' => $note,
                    'type' => 2
                ], 'POST');

                return $response->getStatusCode() == 200;
            }
        }

        /**
         * no group found, check for single article
         */
        $response = $this->doRequest('article', ['filter[external_article_number]' => $sku]);
        if ($response->getStatusCode() == 200) {
            $articles = json_decode($response->getBody()->getContents(), true);
            if (is_array($articles) && count($articles) == 1) {
                $id = $articles[0]['id'];
                $this->_logger->info('Updating Article '.$id.' in MyTinyWMS');
                $response = $this->doRequest("article/{$id}/changeQuantity", [
                    'change' => $quantity,
                    'note' => $note,
                    'type' => 2
                ], 'POST');

                return $response->getStatusCode() == 200;
            }
        }

        $this->_logger->notice('No article or article group in MyTinyWMS found for SKU '.$sku);

        return false;
    }

    /**
     * Do API request with provided params
     *
     * @param string $uriEndpoint
     * @param array $params
     * @param string $requestMethod
     *
     * @return Response
     */
    private function doRequest(string $uriEndpoint, array $params = [], string $requestMethod = Request::HTTP_METHOD_GET): Response {
        $options = [];
        $options['headers'] = [
            'Authorization' => 'Bearer ' . $this->helperData->getGeneralConfig('api_key'),
            'Accept'        => 'application/json',
        ];

        if ($requestMethod == 'GET') {
            $options['query'] = $params;
        } elseif ($requestMethod == 'POST') {
            $options['json'] = $params;
        }

        $baseUri = rtrim($this->helperData->getGeneralConfig('main_url'),"/").'/';

        /** @var Client $client */
        $client = $this->clientFactory->create(['config' => [
            'base_uri' => $baseUri
        ]]);

        try {
            $response = $client->request(
                $requestMethod,
                $uriEndpoint,
                $options
            );
        } catch (GuzzleException $exception) {
            $this->_logger->error('['.$exception->getCode().'] '.$exception->getMessage());

            /** @var Response $response */
            $response = $this->responseFactory->create([
                'status' => $exception->getCode(),
                'reason' => $exception->getMessage()
            ]);
        }

        return $response;
    }
}