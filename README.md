# curl
PHP的并发curl类  
并发与普通的类差不多，都需要对每个数据实例一个curl类，配置，并发最后把每个单独的资源移交给multi做处理  
### 具体流程
1.初始化：curl_init(),curl_multi_init()  
2.配置：curl_setopt_array()  
3.移交：curl_multi_add_handle()  
4.执行：curl_multi_exec()  
5.结束清理资源：curl_close()
### 单个curl流程  
1.初始化：curl_init()  
2.配置：curl_setopt_array()  
3.执行：curl_exec()  
4.结束清理资源：curl_close()
