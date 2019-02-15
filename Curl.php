<?php


/**
 * Created by PhpStorm.
 * User: ThinkPad
 * Date: 2019/2/15
 * Time: 11:36
 * @method get ($data = array(), $option = array())
 * @method post ($data = array(), $option = array())
 * @return array single->array('success'=>true,'code'=>200, 'msg'=>$res) multi->array('success'=>true,'number'=>'777888','code'=>200, 'msg'=>$res)
 * sample:
 * $curl->url("http://www.baidu.com")->method(Curl::MULTI_REQUEST)->post($data,$option);
 * $curl->post($data,$option);
 */
class Curl
{
    const MULTI_REQUEST = '_multi';
    const SINGLE_REQUEST = '_single';

    /**
     * curl配置内容
     * @var array
     */
    private $_options = array(
        CURLOPT_HEADER => false,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    );

    /**
     * 需要传输的数据
     * @var array
     */
    private $_data = array();
    private $_type = self::SINGLE_REQUEST;
    private $_url = '';


    /**
     * get/post等方法的调用
     * @param $method
     * @param $args
     * @return array|mixed
     */
    public function __call($method, $args)
    {
        //设置数据
        $this->_data = $args[0] ?: array();
        $this->_prepareParams($args[1] ?: array());
        return $this->_request($this->_type, $method);
    }

    /**
     * 设置并发请求
     * @param $type string single/multi
     * @return $this
     */
    public function method($type)
    {
        $this->_type = $type;
        return $this;
    }

    public function url($url)
    {
        $this->_url = $url;
        return $this;
    }


    /**
     * 组织传入的参数
     * @param $option
     */
    private function _prepareParams($option)
    {
        //设置参数
        if ($option) {
            foreach ($option as $k => $v) {
                $this->_options[$k] = $v;
            }
        }

    }


    /**
     * 请求主体
     * @param $type
     * @param $method
     * @return mixed
     */
    private function _request($type, $method)
    {
        return $this->$type($method);
    }


    /**
     * 单线程请求
     * @param $method
     * @return array|mixed
     */
    private function _single($method)
    {

        $mh = curl_init();
        $this->_setMethod($method, $this->_data);
        curl_setopt_array($mh, $this->_options);
        $res = curl_exec($mh);

        //异常回传
        if (curl_errno($mh)) {
            $res = array('success' => false, 'code' => curl_errno($mh), 'msg' => curl_error($mh));
        } else {
            $res = array('success' => true, 'code' => 200, 'msg' => $res);
        }

        curl_close($mh);
        return $res;
    }

    /**
     * 多线程请求
     * @param $method
     * @return array
     */
    private function _multi($method)
    {

        $mh = curl_multi_init(); //1 创建批处理cURL句柄
        $ch = $res = array();
        foreach ($this->_data as $k => $v) {
            $ch[$k] = curl_init();
            $this->_setMethod($method, $v);
            curl_setopt_array($ch[$k], $this->_options);
            curl_multi_add_handle($mh, $ch[$k]); //2 增加句柄
        }


        $active = null;
        do {
            while (($mrc = curl_multi_exec($mh, $active)) == CURLM_CALL_MULTI_PERFORM) ;

            if ($mrc != CURLM_OK) {
                break;
            }

            //找到已经完成的请求
            while ($done = curl_multi_info_read($mh)) {
                // 处理返回数据
                $res[] = array(
                    'success' => curl_errno($done['handle']) ? false : true,
                    'number' => array_search($done['handle'], $ch),//从句柄资源中找到当前的句柄的键，可以用来处理指定句柄的回传
                    'code' => curl_errno($done['handle']) ?: 200,
                    'msg' => curl_error($done['handle']) ?: curl_multi_getcontent($done['handle'])
                );

                // 清理curl资源
                curl_multi_remove_handle($mh, $done['handle']);
                curl_close($done['handle']);
            }

            // 阻塞等待直到有活动连接为止
            if ($active > 0) {
                curl_multi_select($mh);
            }

        } while ($active);

        return $res;
    }

    /**
     * 设置请求的方式
     * @param $method
     * @param array $data
     * @return bool
     */
    private function _setMethod($method, $data = array())
    {
        //设置安全认证为不可以用
        if (isset($data['url']) && stripos($data['url'], "https://") !== FALSE) {
            $this->_options[CURLOPT_SSL_VERIFYPEER] = false;
            $this->_options[CURLOPT_SSL_VERIFYHOST] = false;
        }

        //如果是每个独立链接，参数中需要去掉对应的链接值
        switch (strtolower($method)) {
            case 'post':
                $this->_options[CURLOPT_URL] = $this->_url ?: $data['url'];
                unset($data['url']);
                $this->_options[CURLOPT_POST] = true;
                count($data) ? $this->_options[CURLOPT_POSTFIELDS] = http_build_query($data) : null;
                break;
            case 'get':
                $url = ($this->_url ?: $data['url']);
                unset($data['url']);
                $this->_options[CURLOPT_URL] = count($data) ? $url . '?' . http_build_query($data) : $url;
                break;
            default:
                return false;
                break;
        }
    }
}
