<?php
//新词条接口
class NewWrod {
    public $wordsiteurl = 'http://www.xdwine.com';
    //获取词库列表
    public function getWordList($pages=1,$pagenum=50){
        $cachekey = 'wordlist:' . $pages . ':' . $pagenum;
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600*3;
        $datajson = $redis->get($cachekey);
        $datajson="";
        if(!empty($datajson)){
            $data = json_decode($datajson, true);
        }else{
            $url = $url = $this->wordsiteurl . '/dataapi/NewwordApi.php?pages='.$pages.'&pagenum='.$pagenum;
            $dataJson = $this->sendGetUrl($url);
            $redis->set($cachekey, $dataJson,$cachetime); 
            $data = json_decode($dataJson, true);

        }
        return $data;
    }
    //获取信息内容
    public function getWordContent($id){
        $cachekey = 'id_content:id_' . $id;
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600*3;
        $datajson = $redis->get($cachekey);
        // $datajson="";
        if(!empty($datajson)){
            $data = json_decode($datajson, true);
        }else{
            $url = $url = $this->wordsiteurl . '/dataapi/NewWordInfoApi.php?id=' . $id;
            $dataJson = $this->sendGetUrl($url);
            $redis->set($cachekey, $dataJson,$cachetime); 
            $data = json_decode($dataJson, true);

        }
        return $data;
    }

    private function sendGetUrl($url){
        $headers = [
            'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 超时15秒
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $livedata = curl_exec($ch);
        curl_close($ch);
        return $livedata;
    }
}