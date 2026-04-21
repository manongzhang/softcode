<?php
class WordData {
    public $wordsiteid;
    public $wordsiteurl;
    public $wordid;
    public $worddata=[];
    
    public function __construct($id=''){
        $this->wordsiteid = '';
        $this->wordsiteurl = '';
        $this->wordid = $id;
        $this->worddata = $this->getPinyouduoData();
    }
    public function getPinyouduoData(){
        $this->wordsiteid = 'Word:pinyouduo:';
        $this->wordsiteurl = 'http://www.xdwine.com';
        return $this->getData();
    }
    public function getData(){
        try {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379); 
        } catch (Exception $e) {
            $worddata = [];
            $worddata['redisdata_'] = -1; // redis服务出错
            return $worddata;
        }

        // 缓存key
        $wordKeyabc = date('G', time()); // 0 - 23
        $wordKey = $this->wordsiteid . 'word:' . $wordKeyabc;
        // key过期时间23小时
        $wordKeytime = 60*60*23;
        // 获取数据
        $redisValue = $redis->get($wordKey);
        // 判断缓存是否存在
        if(!$redisValue){
            // 接口频率控制
            $isapiKeyStatus = true;
            $isapiKey = $this->wordsiteid . ":isapiKey";
            $isapiKeyRedis = $redis->get($isapiKey);
            if(!$isapiKeyRedis){
                $url = $this->wordsiteurl . '/dataapi/wordApi.php';
                $headers = [
                    'user-agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ];
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 超时15秒
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $worddata = curl_exec($ch);
                curl_close($ch);
                $worddata = json_decode($worddata, true);
                
                if(isset($worddata['data'])){
                    $isapiKeyStatus = false;

                    // 存入redis
                    $redis->set($wordKey, json_encode($worddata));
                    // 设置过期时间
                    $redis->expire($wordKey, $wordKeytime);
                    $worddata['redisdata_'] = 0; // 不是缓存
                }else{
                    // 当接口超时访问时,暂停10分钟请求接口,并使用以前的数据
                    $isapikeytime = 60*10;
                    $redis->set($isapiKey, 1);
                    $redis->expire($isapiKey, $isapikeytime);
                }
            }
            if($isapiKeyStatus){
                $worddata = [];
                for($i=0; $i<23; $i++){
                    $wordKeyabc = $wordKeyabc>=1 ? $wordKeyabc-1 : $wordKeyabc-1+24;
                    $wordKey = $this->wordsiteid . 'word:' . $wordKeyabc;
                    $redisValue = $redis->get($wordKey);
                    if($redisValue){
                        $worddata = json_decode($redisValue, true);
                        break;
                    }
                }
                $worddata['redisdata_'] = 2; // 旧的缓存
            }
        }else{
            $worddata = json_decode($redisValue, true);
            $worddata['redisdata_'] = 1; // 缓存
        }
        
        if($this->wordid){
            foreach($worddata['data'] as $worddata_k=>$worddata_v){
                if( ($worddata_v[2] !== $this->wordid) && ($worddata_v[3] !== $this->wordid) ){
                    unset($worddata['data'][$worddata_k]);
                }
            }
            $worddata['count'] = count($worddata['data']);
            $worddata['data'] = array_values($worddata['data']);
        }
        return $worddata;
    }
    
    public function getUrl(){
        $urldata = [];
        foreach($this->worddata['data'] as $worddata_k=>$worddata_v){
            if(trim($worddata_v[2])){
                $urldata[] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https://' : 'http://') .$_SERVER['HTTP_HOST']. '/' .$worddata_v[2]. '.html';
            }else if(trim($worddata_v[3])){
                $urldata[] = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https://' : 'http://') .$_SERVER['HTTP_HOST']. '/' .$worddata_v[3]. '.html';
            }
        }
        return $urldata; 
    }
    //----------------------------------------------//
    //by add 2026-04-21
    //获取词库列表
    public function getWordList($pages=1,$pagenum=50){
        $cachekey = 'wordlist:' . $pages . ':' . $pagenum;
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600*3;
        $datajson = $redis->get($cachekey);
        //$datajson="";
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