<?php


/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/10/23
 * Time: 11:36
 * @method get ($url, $data= array(), $option= array())
 * @method post ($url, $data= array(), $option = array())
 * @return array array('success'=>true,'code'=>200, 'msg'=>$res)
 */
class Curl
{
    /**
     * curl配置内容
     * @var array
     */
    private $_options = array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER=> true,
        CURLOPT_TIMEOUT => 10,
    );

    /**
     * 需要传输的数据
     * @var array
     */
    private $_data = array();

    /**
     * get/post等方法的调用
     * @param $method
     * @param $args
     * @return array|mixed
     */
    public function __call($method, $args)
    {
        //设置数据
        $this->_data = $args[1] ?: array();
        $this->_prepareParams($args[0], $args[2] ?: array());
        $this->_setMethod($method);
        return $this->_request();
    }

    /**
     * 组织传入的参数
     * @param $url
     * @param $option
     * @return bool
     */
    private function _prepareParams($url, $option)
    {

        if (is_string($url) && strlen($url)) {
            $this->_options[CURLOPT_URL] = $url;
        } else {
            return false;
        }
        //设置参数
        if ($option) {
            foreach ($option as $k => $v) {
                $this->_options[$k] = $v;
            }
        }
        if (stripos($url, "https://") !== FALSE) {
            $this->_options[CURLOPT_SSL_VERIFYPEER] = false;
            $this->_options[CURLOPT_SSL_VERIFYHOST] = false;
        }
    }

    /**
     * 设置请求的方式
     * @param $method
     * @return bool
     */
    private function _setMethod($method)
    {
        switch (strtolower($method)) {
            case 'post':
                $this->_options[CURLOPT_POST] = true;
                $this->_options[CURLOPT_POSTFIELDS] = $this->_data;
                break;
            case 'get':
                $this->_options[CURLOPT_URL] = count($this->_data)?$this->_options[CURLOPT_URL] . '?' . http_build_query($this->_data):$this->_options[CURLOPT_URL];
                break;
            case 'put':
                $this->_options[CURLOPT_PUT] = true;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * 请求主体
     * @return array|mixed
     */
    public function _request()
    {
        $ch = curl_init();
        curl_setopt_array($ch, $this->_options);
        $res = curl_exec($ch);

        //异常回传
        if (curl_errno($ch)) {
            $res = array('success'=>false,'code'=>curl_errno($ch), 'msg'=>curl_error($ch));
        }else{
            $res = array('success'=>true,'code'=>200, 'msg'=>$res);
        }

        
        curl_close($ch);
        return $res;
    }
}
