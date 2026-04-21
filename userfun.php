<?php
//---------------------------用户自定义标签函数文件
require_once('pinyouduo.class.php');
require_once('pinMatchTeam.class.php');//通过联赛参数获取联赛下的比赛数据
require_once('special.class.php');
require_once('word.class.php');
require_once('team.class.php');

function getPinyouduoData($timestr, $typepy='', $classid=0, $id=0, $num=0, $zu_id=0){
    $pinyouduo = new Pinyouduo($timestr, $typepy, $classid, $id, 1, $num, $zu_id);
    return $pinyouduo->data;
}
function getPinyouduoFixedData($num=10, $typepy='', $classid=0, $zu_id=0){
    $time = time();

    $data = [];
    if($num){
        $allnum = $num;
        
        for($i=0; $i<2; $i++){
            if($i != 0){
                $timestr = date('Y-m-d%2000:00:00', $time + 24*60*60*$i);
                $timestr_ = date('Y-m-d 00:00:00', $time + 24*60*60*$i);
            }else{
                $timestr = date('Y-m-d%20H:i:s', $time);
                $timestr_ = date('Y-m-d 00:00:00', $time);
            }
            $pinyouduo = new Pinyouduo($timestr, $typepy, $classid, $id, 1, $allnum, $zu_id);
            
            $data_ = [];
            if( isset($pinyouduo->data['data']) && $pinyouduo->data['data'] ){
                $data_ = array_filter($pinyouduo->data['data'], function ($value) use ($timestr_) {
                    return $value['matchtime'] >= $timestr_;
                });
                $data_num = count($data_);
                
                $data = array_merge($data, $data_);
                $allnum = $allnum - $data_num;
                if($allnum <= 0){
                    break;
                }
            }
        }
    }
    return $data;
}
function getPinyouduoHotData($timestart='', $timeend='', $typepy='', $qiuduipy='', $num=0, $zu_id=0){
    $pinyouduo = new Pinyouduo('', '', 0, 0, 0, $num, $zu_id);
    return $pinyouduo->getHotdata($timestart, $timeend, $typepy, $qiuduipy);
}

function getPinyouduoSpecialDate($id=''){
    $pinyouduo = new SpecialData($id);
    return $pinyouduo->specialdata;
}
function getPinyouduoSpecialURL(){
    $pinyouduo = new SpecialData($id);
    return $pinyouduo->getUrl();
}

function getPinyouduowordDate($id=''){
    $pinyouduo = new WordData($id);
    return $pinyouduo->worddata;
}
function getPinyouduoWordURL(){
    $pinyouduo = new WordData($id);
    return $pinyouduo->getUrl();
}

function getPinyouduoTeamDate($id='', $type='', $num=0){
    $pinyouduo = new TeamData($id, $type, $num);
    return $pinyouduo->teamdata;
}
function getPinyouduoPaging($page, $total_page, $path_page){
    $page_show = 5; // 每页显示链接数
    $page_offset = ceil(($page_show-1)/2);
    
    $banner_page = '';
    if($total_page > 1){
        $banner_page .= '<li><a href="/' .$path_page. '/">首页</a></li>';
        // if($page > 1){
        //     $banner_page .= '<li><a href="/' .$path_page. '/index_' .($page-1). '.html">上一页</a></li>';
        // }else{
        //     $banner_page .= '<li><a href="/' .$path_page. '/">上一页</a></li>';
        // }
    
        $page_start = 1;
        $page_end = $total_page;
        if($page > $page_offset){
            if($page + $page_offset >= $page_end){
                $page_start = $page_end - $page_show + 1;
                $page_start = $page_start > 0 ? $page_start : 1;
            }else{
                $page_start = $page - $page_offset;
            }
            $page_end = $total_page>($page+$page_offset) ? ($page+$page_offset) : $total_page;
        }else{
            $page_start = 1;
            $page_end = $total_page>$page_show ? $page_show : $total_page;
        }
        if($total_page>$page_show && $page>$page_offset+1){
            $banner_page .= '<li><a href="javascript:;">...</a></li>';
        }
        for($i=$page_start; $i<=$page_end; $i++){
            if($page == $i){
                $banner_page .= '<li class="active"><a href="/' .$path_page. '/index_' .$i. '.html">' .$i. '</a></li>';
            }else{
                $banner_page .= '<li><a href="/' .$path_page. '/index_' .$i. '.html">' .$i. '</a></li>';
            }
        }
        if($total_page>$page_show && $total_page>($page+$page_offset)){
            $banner_page .= '<li><a href="javascript:;">...</a></li>';
        }
        
        // if ($page < $total_page) {
        //     $banner_page .= '<li><a href="/' .$path_page. '/index_' .($page + 1). '.html">下一页</a></li>';
        // }else{
        //     $banner_page .= '<li><a href="/' .$path_page. '/index_' .$total_page. '.html">下一页</a></li>';
        // }
        $banner_page .= '<li><a href="/' .$path_page. '/index_' .$total_page. '.html">末页</a></li>';
        $banner_page = '<div class="fenye"><ul>' .$banner_page. '</ul></div>';
    }
    return $banner_page;
}
function getPinyouduoSignal(){
    $timestr = date('Y-m-d%20H:i:s', time());
    $pinyouduo = new Pinyouduo($timestr, '', 0, 0, 1);
    $livedata = $pinyouduo->data;
    $livedata['qita'] = isset($livedata['qita']) ? $livedata['qita'] : [];
    return $livedata['qita'];
}
//--------------------------------start----------------------------
//---------------------2026-04-15--------------------------
function getPinyouduoTotaljs(){
    $timestr = date('Y-m-d%20H:i:s', time());
    $pinyouduo = new Pinyouduo($timestr, '', 0, 0, 1);
    $livedata = $pinyouduo->data;
    $batchurl= isset($livedata['batchurl']['data']) ? $livedata['batchurl']['data'] : [];
    $zuid = intval(ReturnPublicAddVar('zu_id'));
    if($zuid <=1){
        $zuid = 1;
    }
    if($zuid > count($batchurl)){
        $zuid = count($batchurl);
    }
    $showArr = $batchurl[$zuid];
    
    return $showArr;
}

//---------------------2026-04-15--------------------------
//联赛信息
function getPinyouduoData_new($matchtype="",$timestr=""){
     $objmatch = new PinMatchTeam();
     $livedata = $objmatch->getMatchInfo( $matchtype,$timestr);
     return $livedata;
}
//------------------2026-04-17-------------------------------
//table:wanmo_ecms_type
//type=1 $params为  表里面classid  type=1, $params=11|12|13
//type=2 $params为  表里面matchpy  type=2  $params=zuxiebei|zhongyi
function getMatchBiSaiByClassid($params="",$type=0,$pageNum=50,$page=1){
    $objmatch = new PinMatchTeam();
    $livedata = $objmatch->getMatchBiSai($params,$type,$pageNum,$page);
    return $livedata;
}
//
//获取球队信息
//table:wanmo_ecms_team
//$optype =1  $param=|123|456|789   查询表 字段  classid
//$optype =2  $param=|zuxiebei|zhongyi|beijingguoan   查询表 字段  teampy 
function getTeamListInfoByClassid($optype=0,$params="",$pageNum=50,$page=1){
    $objmatch = new PinMatchTeam();
    $livedata = $objmatch->getTeamListInfoById($optype,$params,$pageNum,$page);
    return $livedata;
}
//-------------------2026-04-20------------------------------
//table:wanmo_ecms_team
//teamname 球队名称  查询表字段 zdpy = or kdpy 
function getMatchInfobyTeacmName($teamname,$timestr){
    $objmatch = new PinMatchTeam();
    $livedata = $objmatch->getMatchInfobyTeacmName($teamname,$timestr);
    return $livedata;
}

//--------------------------------end-----------------------


// 获取集锦数据
function getJijinOBJ(){
    include_once(ECMS_PATH.'e/class/jijin.class.php');

    $jijinOBJ = new JijinData();
    return $jijinOBJ;
}
function getJijinPagedata($jijinOBJ, $classid, $page=1, $pagenum=26){
    return $jijinOBJ->getPagedata($classid, $page, $pagenum);
}
function getJijinPagedataBytime($jijinOBJ, $classid, $page=1, $pagenum=26){
    return $jijinOBJ->getPagedataBytime($classid, $page, $pagenum);
}
function getJijinPagedataByliansai($jijinOBJ, $liansai, $classid, $page=1, $pagenum=26){
    return $jijinOBJ->getPagedataByliansai($liansai, $classid, $page, $pagenum);
}
function getJijinPagedataByzandk($jijinOBJ, $zhudui, $kedui, $liansai, $matchtime, $classid, $page=1, $pagenum=26){
    return $jijinOBJ->getPagedataByzandk($zhudui, $kedui, $liansai, $matchtime, $classid, $page, $pagenum);
}
function getJijinPagedataByzork($jijinOBJ, $zhudui, $kedui, $liansai, $matchtime, $classid, $page=1, $pagenum=26){
    return $jijinOBJ->getPagedataByzork($zhudui, $kedui, $liansai, $matchtime, $classid, $page, $pagenum);
}
function getJijinData($jijinOBJ, $id, $isartlist=false){
    return $jijinOBJ->getData($id, $isartlist);
}
// 获取录像数据
function getLuxiangOBJ(){
    include_once(ECMS_PATH.'e/class/luxiang.class.php');

    $luxiangOBJ = new LuxiangData();
    return $luxiangOBJ;
}
function getLuxiangPagedata($luxiangOBJ, $classid, $page=1, $pagenum=26){
    return $luxiangOBJ->getPagedata($classid, $page, $pagenum);
}
function getLuxiangPagedataBytime($luxiangOBJ, $classid, $page=1, $pagenum=26){
    return $luxiangOBJ->getPagedataBytime($classid, $page, $pagenum);
}
function getLuxiangPagedataByliansai($luxiangOBJ, $liansai, $classid, $page=1, $pagenum=26){
    return $luxiangOBJ->getPagedataByliansai($liansai, $classid, $page, $pagenum);
}
function getLuxiangPagedataByzandk($luxiangOBJ, $zhudui, $kedui, $liansai, $matchtime, $classid, $page=1, $pagenum=26){
    return $luxiangOBJ->getPagedataByzandk($zhudui, $kedui, $liansai, $matchtime, $classid, $page, $pagenum);
}
function getLuxiangPagedataByzork($luxiangOBJ, $zhudui, $kedui, $liansai, $matchtime, $classid, $page=1, $pagenum=26){
    return $luxiangOBJ->getPagedataByzork($zhudui, $kedui, $liansai, $matchtime, $classid, $page, $pagenum);
}
function getLuxiangData($luxiangOBJ, $id, $isartlist=false){
    return $luxiangOBJ->getData($id, $isartlist);
}
?>