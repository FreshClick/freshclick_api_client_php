<?php
namespace FreshClick;
/**
 * Класс для отправки данных в API FreshClick
 *
 * @see http://freshclick.ru/api
 */
class FreshClick {
    const DEFAULT_TARGET = 'https://api.freshclick.ru';
    
    public $goods=[]; 
    public $params=[];
    public $type;
    public $error_info;

    private $api_target;
    private $auth_token;
    private $verify_ssl = true;
    private $request_timeout = 15;  
        
    function __construct($auth_token, $api_target = self::DEFAULT_TARGET) {
        $this->api_target = $api_target;
        $this->auth_token = $auth_token;
    }
    
    public function addGood($good_id,$name,$price,$count=1,$self_price=0){
        $this->goods[]=["good_id"=>$good_id,"name"=>$name,"price"=>$price,
            "count"=>$count,"self_price"=>$self_price];
        return $this;
    }
    
    public function client($name,$email=null,$phone=null){
        $this->params['_name']=$name;        
        if($email){
            $this->params['_email']=$email;
        }
        if($phone){
            $this->params['_phone']=$phone;
        }        
        return $this;
    }

    public function order($id,$price,$goods=[],$self_price=0) {
        $this->type='order';
        $this->params['_id']=$id;
        $this->params['_price']=$price;
        if($self_price){
            $this->params['_self_price']=$self_price;        
        }
        if($goods){
            $this->goods=$goods;
        }
        return $this;        
    }

    public function send($event=null,$params=[]) {
        if(empty($event)){
            if(empty($this->event)){
                throw new InvalidParamException("Type of event not set");
            }
            else{
                $event= $this->event;
                $this->event=null;
            }
        }
        $send_event=["_type"=>$event,
                    "_uid"=>isset($_COOKIE["_freshclick_id"])?$_COOKIE["_freshclick_id"]:null,
                    "_sess"=>isset($_COOKIE["_freshclick_s"])?$_COOKIE["_freshclick_s"]:null];  
        $data=  array_merge($send_event,$params);
        
        if(!empty($this->goods)){
            $data['_goods']=  $this->goods;
            $this->goods=[];
        }
        
        if(!empty($this->params)){
            $data=  array_merge($data,  $this->params);            
            $this->params=[];
        }    
        
        $response=$this->curl_request($this->api_target.'/set',  
                json_encode($data, JSON_UNESCAPED_UNICODE));
        if($response===false){
            return false;
        }

        $result=json_decode($response);
        if(!empty($result->error)){
            $this->error_info=$result->error;
            return false;
        }            
        return $result;
    }


    public function disableVerifySSL() {
        $this->verify_ssl=false;
        return $this;
    }
    
    public function setRequestTimeout($timeout) {
        $this->request_timeout = (int)$timeout;
        return $this;
    } 
    
    protected function curl_request($url, $post_data = null) {
        $ch = curl_init($url);
        if (is_array($post_data)) {
            $post_data = array_map([$this, 'sanitize_curl_parameter'], $post_data);
        }        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->request_timeout);
        if($this->verify_ssl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);            
        }
        else{
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);            
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);                        
        }

        if (!empty($post_data)) {
          curl_setopt($ch, CURLOPT_POST, 1);
          curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, 
            ["Authorization: Bearer ".$this->auth_token,
             "Content-Type: application/json; charset=utf-8"]);           
        $response = curl_exec($ch);

        if (strlen($response) == 0) {
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $this->error_info="CURL error: $errno - $error";
            return false;
        }

        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code != 200) {
            $this->error_info="HTTP status code: $code, response=$response";
            return false;
        }
        curl_close($ch);
        return $response;
    }
    
    /**
     * Sanitizes the given value as cURL parameter.
     *
     * The first value may not be a "@". PHP would treat this as a file upload
     *
     * @link http://www.php.net/manual/en/function.curl-setopt.php CURLOPT_POSTFIELDS
     *
     * @param string $value
     * @return string
     */
    private function sanitize_curl_parameter ($value) {
        if ((strlen($value) > 0) && ($value[0] === '@')) {
          return substr_replace($value, '&#64;', 0, 1);
        }
        return $value;
    }    
}
