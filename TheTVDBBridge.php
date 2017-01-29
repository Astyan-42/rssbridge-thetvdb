<?php

function date_compare($a, $b)
{
    $t1 = $a['timestamp'];
    $t2 = $b['timestamp'];
    return $t1 < $t2;
} 

class TheTVDBBridge extends BridgeAbstract{
    const MAINTAINER = "Astyan";
    const NAME = "TheTVDB";
    const URI = "https://api.thetvdb.com/";
    const CACHE_TIMEOUT = 43200; // 12h
    const DESCRIPTION = "Returns latest episode of a serie on TheTVDB";
    const PARAMETERS = array(array(
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
        //login and get token, don't use curlJob to do less adaptations
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
        
    private function curlJob($token, $url){
        $token_header = "Authorization: Bearer ".$token;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept: application/json',
            $token_header)
        );
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        $result_array = (array)json_decode($result);
        if(isset($result_array["Error"])){
			throw new Exception($result_array["Error"]);
			die;
		}
        return $result_array;
    }
    
    private function getLatestSeasonNumber($token, $serie_id){
        // get the last season
        $url = $this->getURI().'series/'.$serie_id.'/episodes/summary';
        $summary = $this->curlJob($token, $url);
        return max($summary['data']->airedSeasons);
    }
    
    private function getSeasonEpisodes($token, $serie_id, $season, &$episodelist, $nbepisodemin, $page=1){
        $url = $this->getURI().'series/'.$serie_id.'/episodes/query?airedSeason='.$season.'?page='.$page;
        $episodes = $this->curlJob($token, $url);
        // we don't check the number of page because we suppose there is less than 100 episodes in every season
        $episodes = (array)$episodes['data'];
        $episodes = array_slice($episodes, -$nbepisodemin, $nbepisodemin);
        foreach($episodes as $episode){
            $episodedata = array();
            $episodedata['uri'] = 'http://thetvdb.com'; //should link to the episodes on thetvdb
            $episodedata['title'] = 'S'.$episode->airedSeason.'E'.$episode->airedEpisodeNumber.'('.$episode->absoluteNumber.') : '.$episode->episodeName;
            $episodedata['author'] = ''; // should be the name of the serie
            $date = DateTime::createFromFormat('Y-m-d H:i:s', $episode->firstAired.' 00:00:00');
            $episodedata['timestamp'] = $date->getTimestamp();
            $episodedata['content'] = $episode->overview;
            $episodelist[] = $episodedata;            
        }
    }
 
    public function collectData(){
        $serie_id = $this->getInput('serie_id');
        $nbepisode = 10; // put it as a param ?
        $episodelist = array();
        $token = $this->getToken();
        $maxseason = $this->getLatestSeasonNumber($token, $serie_id);
        $season = $maxseason;
        while(sizeof($episodelist) < $nbepisode and $season >= 1){
            $nbepisodetmp = $nbepisode - sizeof($episodelist);
            $this->getSeasonEpisodes($token, $serie_id, $season, $episodelist, $nbepisodetmp);
            $season = $season - 1;
        }
        // add the 10 last specials episodes
        $this->getSeasonEpisodes($token, $serie_id, 0, $episodelist, $nbepisode);
        // sort and keep the 10 last, works bad with the netflix serie (all episode lauch at once)
        usort($episodelist, 'date_compare');
        $episodelist = array_slice($episodelist, 0, $nbepisode);
        
        $this->items = $episodelist;
    }
    
}
?>
    
    
    
    
