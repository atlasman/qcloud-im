<?php
/**
 * 腾讯云即时通讯Restful接口
 */
namespace Atlasman\Qcloud\Im;
use GuzzleHttp\Client as HttpClient;
use Atlasman\Qcloud\IM\Usersig;

class Client
{
    #app基本信息
    protected $sdkappid = 0;
    protected $identifier = '';
    protected $usersig = '';
    protected $private_key = '';
    protected $public_key = '';

    #开放IM https接口参数, 一般不需要修改
    CONST BASEURI = 'https://console.tim.qq.com';
    CONST VERSION = 'v4';
    CONST CONTENTTYPE = 'json';

    public function __construct($sdkappid, $identifier, $private_key, $public_key = null)
    {
        $this->sdkappid = $sdkappid;
        $this->identifier = $identifier;
        $this->private_key = $private_key;
        $this->public_key = $public_key;
        
    }

    /**
     * 获取usersig
     * @param  [type] $expireTime 过期时间
     * @return [type]              [description]
     */
    public function genSig ($expireTime = 180 * 24 * 3600) {
        try{
            $api = new Usersig($this->sdkappid, $this->private_key, $this->public_key);
            $this->usersig = $api->genSig($this->identifier, $expireTime);
            return $this->usersig;
        }catch(Exception $e){
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function request($serviceName, $command, array $data)
    {
        try {
            $this->genSig();

            $requestUrl = $this->buildRequestUri($serviceName, $command);
            
            $client = new HttpClient();
            $options = [
                'json'  =>  $data
            ];
            
            $response = $client->request('POST', $requestUrl, $options);
            $body = $response->getBody();
            $content = $body->read(10240);
            $res = json_decode($content, true);
            if ($res['ErrorCode'] > 0 || $res['ActionStatus'] == 'FAIL') {
                throw new \Exception($res['ErrorInfo'], $res['ErrorCode']);
            } else {
                return $res;
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
        
    }

    protected function buildRequestUri($serviceName, $command)
    {
        $url = implode('/', [self::BASEURI, self::VERSION, $serviceName, $command]);
        $parameter = [
            'usersig'       =>  $this->usersig,
            'identifier'    =>  $this->identifier,
            'sdkappid'      =>  $this->sdkappid,
            'contenttype'   =>  self::CONTENTTYPE,
            'random'        =>  mt_rand(100000000, 999999999)
        ];
        $parameter = http_build_query($parameter);
        return "{$url}?{$parameter}";
    }
}



