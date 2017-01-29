<?php
class TheTVDBBridge extends BridgeAbstract{
    const MAINTAINER = "Astyan";
    const NAME = "TheTVDB";
    const URI = "https://api.thetvdb.com/";
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = "Returns latest episode of a serie on TheTVDB";
    const PARAMETERS = array(
        'Serie ID'=>array(
            'serie_id'=>array(
                'type'=>'number',
                'name'=>'ID',
                'required'=>true,
            )
        )
    );
    const APIACCOUNT = "RSSBridge";
    const APIKEY = "76DE1887EA401C9A";
    const APIUSERKEY = "B52869AC6005330F";
    
    private function getToken(){
        //login and get token
        $login_array = array("apikey" => self::APIKEY, 
                             "username" => self::APIACCOUNT, 
                             "userkey" => self::APIUSERKEY);
        $login_json = json_encode($login_array);
        $ch = curl_init($this->getURI().'login');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $login_json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Accept: application/json')
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        $token_json = (array)json_decode($result);
        if(isset($token_json["Error"])){
			throw new Exception($token_json["Error"]);
			die;
		}
        $token = $token_json['token'];
        return $token;
    }
    
 
    public function collectData(){
        $token = $this->getToken();
        //need to get the tv serie after the login. A function with token as arg
        // and yield if it exist in php
        
        $item = array();
        $item['uri'] = "test";
        $item['title'] = $token;
        $item['author'] = $token;
        $item['timestamp'] = "ffs";
        $item['content'] = "";
        
        $this->items[] = $item;
    }
    
}
?>
    
    
    
    
