<?php
/*
   获取联赛对应比赛
*/
class PinMatchTeam {
    public $timestr;//时间
    public $typepy; //联赛类型数组形式
    public $data;
    public function __construct(){
        $this->dburl = 'https://www.xdwine.com/dataapi';
        $this->timestr = $timestr;
        $this->typepy = $typepy;
    }
    //篮球直播数据接口
    public function getMatchInfo($matchtype,$timestr){
        $cachekey = explode("%20", $timestr);
        $hour = explode(":",$cachekey[1])[0];
        $cachekey= "lanqiu_zibodata_matchkey:".$matchtype.'_time:'.$cachekey[0]."H:$hour";
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600;
        $datajson = $redis->get($cachekey);
        $datajson="";
        if(!empty($datajson)){
            $hddata = json_decode($datajson, true);
        }else{
            $url = $this->dburl;
            $url = $url."/zhibo.php?typepy={$matchtype}&time={$timestr}";
            $jsondata =  $this->sendGetUrl($url);
            $data = json_decode($jsondata, true);


            $hddata = $this->groupMatchesByDate($data);
            $hddata = $this->checkTime($hddata,$timestr);
            $hddata = $this->sortMatchesByOnclickAndTime($hddata);
            $cachdata = json_encode($hddata);
            $redis->set($cachekey, $cachdata, $cachetime); // 3600
        }
        return $hddata;
    }

    public function sortMatchesByOnclickAndTime($data) {
        // 收集所有比赛到一个一维数组
        $allMatches = [];
        foreach ($data as $date => $matches) {
            foreach ($matches as $match) {
                $match['date'] = $date; // 保存日期信息，便于后续重建
                $allMatches[] = $match;
            }
        }
        
        // 使用 usort 进行排序
        usort($allMatches, function($a, $b) {
            // 按 onclick 倒序
            if ($a['onclick'] != $b['onclick']) {
                return $b['onclick'] - $a['onclick'];
            }
            
            // 相同 onclick，按 matchtime 正序
            return strtotime($a['matchtime']) - strtotime($b['matchtime']);
        });
        
        // 重新构建原始数据结构（按日期分组）
        $sortedData = [];
        foreach ($allMatches as $match) {
            $date = $match['date'];
            unset($match['date']); // 移除临时添加的日期字段
            $sortedData[$date][] = $match;
        }
        
        return $sortedData;
    }

    //通过球队找联赛
    public function qiuduiFindMatch($qiuname,$timestr){
        $cachekey = explode("%20", $timestr);
        $hour = explode(":",$cachekey[1])[0];
        $cachekey= "qiudui_find_match_matchkey:".$qiuname.'_time:'.$cachekey[0]."H:$hour";
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =600;
        $datajson = $redis->get($cachekey);
        if(!empty($datajson)){
            $hddata = json_decode($datajson, true);
        }else{
            $url = $this->dburl;
            $url = $url."/teamfindmatch.php?qiiuname={$qiuname}&time={$timestr}";
            $jsondata =  $this->sendGetUrl($url);
            $redis->set($cachekey, $jsondata, $cachetime); // 3600
            $hddata = json_decode($jsondata, true);
        }
        return $hddata;
    }




    //检查数据时间篮球默认三个小时,足球提前2小时
    private function checkTime($data,$timestr){
        $timestr =str_replace("%20","",$timestr);
        $privTwoTime = strtotime($timestr)-9600;//提前9600
        //$privTwoTime=0;
        foreach($data as &$items){
            foreach($items as $kk=> &$vv){
                if($vv["classid"] == 1){//足球
                    $matchtime = strtotime($vv["matchtime"]);//足球开始时间
                    if($privTwoTime > $matchtime){
                        unset($items[$kk]);
                    }
                }
                
            }
        }
        return $data;
    }

    private function groupMatchesByDate($data) {
        $grouped = [];
        // 检查数据格式是否正确
        if (!isset($data['data']) || !is_array($data['data'])) {
            return $grouped;
        }
        foreach ($data['data'] as $match) {
            // 提取日期部分（Y-m-d格式）
            if (isset($match['matchtime'])) {
                $date = substr($match['matchtime'], 0, 10);
                
                // 将当前比赛添加到对应日期的分组中
                $grouped[$date][] = $match;
            }
        }
        ksort($grouped, SORT_REGULAR);
        return $grouped;
    }








    //发送get请求
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

    public function getMatchBiSai($params="",$type=0,$pageNum=50,$page=1){
        $time=Date("YmdH",time());
        $cachekey= "saishi_:".'type:'.$type.'_params:'.$params.'_time:'.$time;
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600;
        $datajson = $redis->get($cachekey);
        $datajson="";
        if(!empty($datajson)){
            $data = json_decode($datajson, true);
        }else{
            $url = $this->dburl . '/saishi.php?params='.$params.'&types='.$type.'&pagenum='.$pageNum.'&pages='.$page;
            $data= $this->sendGetUrl($url);
            $redis->set($cachekey, $data, $cachetime); // 3600
            $data = json_decode($data, true);
        }
        return $data;
    }

    //通过liansaiid获取球队列表信息
    public function getTeamListInfoById($optype,$params,$pageNum=50,$page=1){
       $time=Date("YmdH",time());
        $cachekey= "qiudui_:".'optype:'.$optype.'_params:'.$params.'_time:'.$time.'_pagenum:'.$pageNum.'_pages:'.$page;
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600;
        $datajson = $redis->get($cachekey);
        if(!empty($datajson)){
            $data = json_decode($datajson, true);
        }else{
            $url = $this->dburl . '/teamlist.php?optype='.$optype.'&params='.$params.'&pagenum='.$pageNum.'&pages='.$page;
            $data= $this->sendGetUrl($url);
            $redis->set($cachekey, $data, $cachetime);
            $data = json_decode($data, true);
        }
        return $data;

    }

    //通过球队名称获取所在联赛列表
    public function getMatchInfobyTeacmName($teamname,$timestr){
        $cachekey = explode("%20", $timestr);
        $cachekey= "qiudui_matchkey:".$teamname.'_time:'.$cachekey[0];
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        $cachetime =3600;
        $datajson = $redis->get($cachekey);
        // $datajson="";
        if(!empty($datajson)){
            $hddata = json_decode($datajson, true);
        }else{
            $url = $this->dburl;
            $url = $url."/team.php?typepy={$teamname}&time={$timestr}";
            $jsondata =  $this->sendGetUrl($url);
            $redis->set($cachekey, $jsondata, $cachetime); // 3600
            $hddata = json_decode($jsondata, true);
        }
        return $hddata;
    }

    
}
