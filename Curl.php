<?php
/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2018/10/23
 * Time: 11:36
 * @method get ($url, $data, $option)
 * @method post ($url, $data, $option = array())
 */
class Curl
{
    /**
     * curl配置内容
     * @var array
     */
    private $_options = array(
        'CURLOPT_HEADER' => false,
        'CURLOPT_RETURNTRANSFER' => true,
        'CURLOPT_TIMEOUT' => 10,
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
        $this->_prepareParams($args[0], $args[1] ?: array(), $args[2] ?: array());
        $this->_setMethod($method);
        return $this->_request();
    }

    /**
     * 组织传入的参数
     * @param $url
     * @param $data
     * @param $option
     * @return bool
     */
    private function _prepareParams($url, $data, $option)
    {
        //设置数据
        $this->_data = $data;
        if (is_string($url) && strlen($url)) {
            $this->_options['CURLOPT_URL'] = $url;
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
            $this->_options['CURLOPT_SSL_VERIFYPEER'] = false;
            $this->_options['CURLOPT_SSL_VERIFYHOST'] = false;
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
                $this->_options['CURLOPT_POST'] = true;
                $this->_options['CURLOPT_POSTFIELDS'] = $this->_data;
                break;
            case 'get':
                $this->_options['CURLOPT_URL'] = $this->_options['CURLOPT_URL'] . '?' . http_build_query($this->_data);
                break;
            case 'put':
                $this->_options['CURLOPT_PUT'] = true;
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
        curl_close($ch);
        //异常回传
        if (curl_errno($ch)) {
            return array(curl_error($ch), curl_errno($ch));
        }
        return $res;
    }
}
