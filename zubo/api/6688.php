<?php
$base = "http://110.42.52.180:6688/live/live.php?token=76AYudAHan&id=";

$channels = [
    // CCTV
    "cctv1" => "CCTV1综合", "cctv2" => "CCTV2财经",
    "cctv3" => "CCTV3综艺", "cctv4" => "CCTV4中文国际",
    "cctv4oz" => "CCTV4欧洲", "cctv4mz" => "CCTV4美洲",
    "cctv5" => "CCTV5体育", "cctv5p" => "CCTV5+体育赛事",
    "cctv6" => "CCTV6电影", "cctv7" => "CCTV7国防军事",
    "cctv8" => "CCTV8电视剧", "cctv9" => "CCTV9纪录",
    "cctv10" => "CCTV10科教", "cctv11" => "CCTV11戏曲",
    "cctv12" => "CCTV12社会与法", "cctv13" => "CCTV13新闻",
    "cctv14" => "CCTV14少儿", "cctv15" => "CCTV15音乐",
    "cctv16" => "CCTV16奥林匹克", "cctv17" => "CCTV17农业农村",
    // CGTN
    "cgtn" => "CGTN",
    "cgtnwyjl" => "CGTN外语记录",
    "cgtnalby" => "CGTN阿拉伯语",
    "cgtnxbyy" => "CGTN西班牙语",
    "cgtnfy" => "CGTN法语",
    "cgtney" => "CGTN俄语",
    // 卫视
    "zjws" => "浙江卫视", "bjws" => "北京卫视",
    "dfws" => "东方卫视", "jsws" => "江苏卫视",
    "hunws" => "湖南卫视", "gdws" => "广东卫视",
    "jxws" => "江西卫视", "henws" => "河南卫视",
    "shanxws" => "陕西卫视", "hubws" => "湖北卫视",
    "jlws" => "吉林卫视", "qhws" => "青海卫视",
    "hainws" => "海南卫视", "xjws" => "新疆卫视",
    "sxws" => "山西卫视", "scws" => "四川卫视",
    "gzws" => "贵州卫视", "lnws" => "辽宁卫视",
    "ahws" => "安徽卫视", "gxws" => "广西卫视",
    "tjws" => "天津卫视", "cqws" => "重庆卫视",
    "gsws" => "甘肃卫视", "dnws" => "东南卫视",
    "ynws" => "云南卫视", "nmgws" => "内蒙古卫视",
    "nxws" => "宁夏卫视", "szws" => "深圳卫视",
    // CHC
    "chcymdy" => "CHC影迷电影",
    "chcdzdy" => "CHC动作电影",
    "chcjtyy" => "CHC家庭影院",
    // 轮播
    "24hlb" => "24小时轮播",
    "cqjc" => "传奇剧场",
    "rxjc" => "热血剧场",
    "wxyy" => "往昔影院",
    "shjc" => "生活剧场",
    "hjjd" => "怀旧经典",
    "hsjd" => "红色经典",
];

// ========== ?id= 直接播放 ==========
$id = isset($_GET['id']) ? $_GET['id'] : '';
if ($id !== '' && isset($channels[$id])) {
    $realId = 'zb-' . $id;
    header('Location: ' . $base . $realId);
    exit;
}

// ========== 默认输出 TXT ==========
header('Content-Type: text/plain; charset=utf-8');

// 央视（CCTV + CGTN）
echo "央视,#genre#\n";
foreach ($channels as $id => $name) {
    if (strpos($name, 'CCTV') !== false || strpos($name, 'CGTN') !== false) {
        echo "{$name},{$base}zb-{$id}\n";
    }
}

// 卫视
echo "\n卫视,#genre#\n";
foreach ($channels as $id => $name) {
    if (strpos($name, '卫视') !== false) {
        echo "{$name},{$base}zb-{$id}\n";
    }
}

// CHC
echo "\nCHC电影,#genre#\n";
foreach ($channels as $id => $name) {
    if (strpos($name, 'CHC') !== false) {
        echo "{$name},{$base}zb-{$id}\n";
    }
}

// 轮播
echo "\n轮播剧场,#genre#\n";
foreach ($channels as $id => $name) {
    if (strpos($name, 'CCTV') === false && strpos($name, 'CGTN') === false && strpos($name, '卫视') === false && strpos($name, 'CHC') === false) {
        echo "{$name},{$base}zb-{$id}\n";
    }
}
?>