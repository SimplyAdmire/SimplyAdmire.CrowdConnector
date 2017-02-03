<?php
namespace SimplyAdmire\CrowdConnector\Crowd;

use Neos\Utility\Arrays;

class Response
{

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $responseInfo;

    public function __construct(array $data, array $responseInfo = [])
    {
        $this->data = $data;
        $this->responseInfo = $responseInfo;
    }

    public static function createFromResponseContent($responseBody, array $responseInfo)
    {
        $statusCode = Arrays::getValueByPath($responseInfo, 'http_code');

        if ($statusCode === 200) {
            $responseBody = \json_decode($responseBody, true);
            if (\is_array($responseBody)) {
                return new static($responseBody, $responseInfo);
            } else {
                $statusCode = 500;
            }
        }

        return new static([], ['http_code' => $statusCode]);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getResponseInfo()
    {
        return $this->responseInfo;
    }

    public function isSuccess()
    {
        return Arrays::getValueByPath($this->responseInfo, 'http_code') === 200;
    }

}
