<?php

namespace Pim\Bundle\PowerlingBundle\Api;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use GuzzleHttp\Client;
use Pim\Bundle\PowerlingBundle\Project\ProjectInterface;

/**
 * Calls to Powerling php API
 *
 * @author    Arnaud Lejosne <a.lejosne@powerling.com>
 * @copyright 2019 Powerling (https://powerling.com)
*/
class WebApiRepository implements WebApiRepositoryInterface
{
    /** @var ConfigManager */
    protected $configManager;

    private $baseUri;

    /**
     * @param ConfigManager $configManager
     * @param string $baseUri
     */
    public function __construct(ConfigManager $configManager, $baseUri)
    {
        $this->configManager = $configManager;
        $this->baseUri = $baseUri;
    }

    /**
     * @param ProjectInterface $project
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendProjectDocuments(ProjectInterface $project)
    {
        list($sourceLanguage, $targetLanguage) = explode('$', $project->getLangAssociationId());

        foreach ($project->getDocuments() as $document) {
            $xliff = $this->documentToXliff($document, $sourceLanguage, $targetLanguage);
            $this->doRequest('order/'.$project->getCode().'/upload-file', [
                'sourcelang' => $sourceLanguage,
                'targetlang' => $targetLanguage,
                'file' => base64_encode($xliff)
            ], true);
        }

        $this->doRequest('order/'.$project->getCode().'/submit', []);
    }

    private function documentToXliff($document, $sourceLanguage, $targetLanguage)
    {
        $xml = new \DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $xmlTranslationProject = $xml->createElement('xliff');
        $xmlTranslationProject->setAttribute('version', '1.2');
        $xml->appendChild($xmlTranslationProject);
        $xmlSheet = $xml->createElement('file');
        $xmlSheet->setAttribute('source-language', $sourceLanguage);
        $xmlSheet->setAttribute('target-language', $targetLanguage);
        $xmlSheet->setAttribute('id', $document['title']);
        $xmlTranslationProject->appendChild($xmlSheet);
        $xmlBody = $xml->createElement('body');
        $xmlSheet->appendChild($xmlBody);

        foreach ($document['original_content'] as $field => $data) {
            $xmlTransItem = $xml->createElement('trans-unit');
            $xmlTransItem->setAttribute('id', $field);
            $xmlTransSource = $xml->createElement('source');
            $xmlTransSource->setAttribute('xml:lang', $sourceLanguage);
            $xmlTransSource->appendChild($xml->createCDATASection($data['original_phrase']));
            $xmlTransItem->appendChild($xmlTransSource);

            $xmlTransDest = $xml->createElement('target');
            $xmlTransDest->setAttribute('xml:lang', $targetLanguage);
            $xmlTransItem->appendChild($xmlTransDest);

            $xmlBody->appendChild($xmlTransItem);
        }

        return $xml->saveXML();
    }

    /**
     * @param array $data
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createProject(array $data)
    {
        return $this->doRequest('order/create', ['name' => $data['name']]);
    }

    /**
     * @param string $projectCode
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDocuments($projectCode)
    {
        $response = $this->doRequest('order/'.$projectCode);
        $documentsToRetrieve = [];
        $documents = [];

        foreach ($response['data'] as $projectFile) {
            if ($projectFile['status'] === 'complete') {
                $documentsToRetrieve[] = $projectFile['id'];
            }
        }
        foreach ($documentsToRetrieve as $documentId) {
            $response = $this->doRequest('order/'.$projectCode.'/file/'.$documentId.'/status');

            if (array_key_exists('targetfile', $response)) {
                list($productId, $document) = $this->processTranslatedDocument($projectCode, $response['targetfile']);
                $documents[$productId] = $document;
            }
        }

        return [$documents, count($response['data']) === count($documents)];
    }

    /**
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getLangAssociations()
    {
        $langAssociations = [];
        $response = $this->doRequest('language-pairs');

        foreach ($response['data'] as $langAssociation) {
            $langAssociations[$langAssociation['source']['code'].'$'.$langAssociation['target']['code']] = [
                'id'            => $langAssociation['source']['code'].'$'.$langAssociation['target']['code'],
                'language_from' => $langAssociation['source']['code'],
                'language_to'   => $langAssociation['target']['code']
            ];
        }

        return $langAssociations;
    }

    /**
     * @param $projectCode
     * @param $documentCode
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function processTranslatedDocument($projectCode, $documentCode)
    {
        $client = new \GuzzleHttp\Client();
        $targetFileResponse = $client->request('GET', $this->baseUri.'/target/'.$projectCode.'-'.$documentCode.'.xliff');

        $doc = new \DOMDocument();
        $doc->loadXML($targetFileResponse->getBody()->__toString());
        $productId = $doc->getElementsByTagName('file')->item(0)->getAttribute('id');
        $fields = [];

        /** @var \DOMElement $transUnit */
        foreach ($doc->getElementsByTagName('trans-unit') as $transUnit) {
            $field = $transUnit->getAttribute('id');
            $value = $transUnit->getElementsByTagName('target')[0]->nodeValue;
            $fields[$field] = $value;
        }

        return [$productId, $fields];
    }

    /**
     * @param $endpoint
     * @param null $postData
     * @param bool $isMultipart
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function doRequest($endpoint, $postData = null, $isMultipart = false)
    {
        $apiKey = $this->configManager->get('pim_powerling.api_key');
        $client = new Client();
        $requestOptions = ['headers' =>
            [
                'Authorization' => "Bearer " . $apiKey
            ]
        ];

        if (!is_null($postData)) {
            if ($isMultipart) {
                $requestOptions['multipart'] = [];

                foreach ($postData as $key => $value) {
                    $requestOptions['multipart'][] = [
                        'name' => $key,
                        'contents' => $value
                    ];
                }
            } else {
                $requestOptions['form_params'] = $postData;
            }
        }

        $httpResponse = $client->request(is_null($postData) ? 'GET' : 'POST', $this->baseUri.'/v1/'.$endpoint, $requestOptions);
        return json_decode($httpResponse->getBody(), true);
    }
}
