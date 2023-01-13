<?php

namespace App\Services\Podcast;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Middleware;
use App\Services\API;
use Illuminate\Support\Facades\Log;

class PodcastApi extends API
{
    protected $base_url;
    protected $secret;
    private function xmlObjToArr($obj)
    {

        $namespace = $obj->getDocNamespaces(true);

        $namespace[NULL] = NULL;



        $children = array();

        $attributes = array();

        $name = strtolower((string)$obj->getName());



        $text = trim((string)$obj);

        if (strlen($text) <= 0) {

            $text = NULL;
        }



        // get info for all namespaces

        if (is_object($obj)) {

            foreach ($namespace as $ns => $nsUrl) {


                // atributes

                $objAttributes = $obj->attributes($ns, true);

                foreach ($objAttributes as $attributeName => $attributeValue) {

                    $attribName = strtolower(trim((string)$attributeName));

                    $attribVal = trim((string)$attributeValue);

                    if (!empty($ns)) {

                        $attribName = $ns . ':' . $attribName;
                    }

                    $attributes[$attribName] = $attribVal;
                }



                // children

                $objChildren = $obj->children($ns, true);

                foreach ($objChildren as $childName => $child) {
                

                        $childName = strtolower((string)$childName);

                        if (!empty($ns)) {

                            $childName = $ns . ':' . $childName;
                        }
                        $children[$childName][] = $this->xmlObjToArr($child);
                    
                }
            }
        }



        return array(

            'name' => $name,

            'text' => $text,

            'attributes' => $attributes,

            'children' => $children

        );
    }

    public function __construct()
    {
        // 
        $this->base_url = "";
    }

    public function baseUrl(): string
    {
        return '';
    }

    public function fetchPodcastData(string $rssLink, string $type = 'object')
    {
        try {
            $response = $this->getClient()->GET($rssLink);
            $stringResponse = $response->getBody()->getContents();
            $objectResponse = simplexml_load_string($stringResponse);
            if ($type == 'xml') {
                return $objectResponse;
            }
            $array = $this->xmlObjToArr($objectResponse);
            if ($array["name"] == 'rss') {
                return $array["children"]["channel"][0]["children"];
            }
        } catch (ClientException $exception) {
            return response()->json([
                'status' => false,
                'status_code' => $exception->getCode(),
                'message' => $exception->getMessage(),
            ])->getData();
        }
    }
}
