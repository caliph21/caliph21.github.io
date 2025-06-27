// 全局常量配置
const PROXY_URL = '/proxy/';    // 适用于 Cloudflare, Netlify (带重写), Vercel (带重写)
// const HOPLAYER_URL = 'https://hoplayer.com/index.html';
const SEARCH_HISTORY_KEY = 'videoSearchHistory';
const MAX_HISTORY_ITEMS = 5;

// 密码保护配置
const PASSWORD_CONFIG = {
    localStorageKey: 'passwordVerified',  // 存储验证状态的键名
    verificationTTL: 90 * 24 * 60 * 60 * 1000,  // 验证有效期（90天，约3个月）
};

// 网站信息配置
const SITE_CONFIG = {
    name: 'LibreTV',
    url: 'https://libretv.is-an.org',
    description: '免费在线视频搜索与观看平台',
    logo: 'https://images.icon-icons.com/38/PNG/512/retrotv_5520.png',
    version: '1.0.3'
};

// API站点配置
const API_SITES = {
    qhzyw: {
        api: 'https://caiji.qhzyapi.com/api.php/provide/vod',
        name: '奇虎资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕速播资源网: {
        api: 'https://subocaiji.com/api.php/provide/vod',
        name: '⊕速播资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕天空资源网5: {
        api: 'https://api.tiankongapi.com/api.php/provide/vod',
        name: '⊕天空资源网5',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕光速资源网8: {
        api: 'https://api.guangsuapi.com/api.php/provide/vod',
        name: '⊕光速资源网8',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕金鹰资源网: {
        api: 'https://jyzyapi.com/provide/vod',
        name: '⊕金鹰资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕鸭鸭资源网: {
        api: 'https://cj2.yayazy.net/api.php/provide/vod/from/yym3u8',
        name: '⊕鸭鸭资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕快车资源网: {
        api: 'https://caiji.kczyapi.com/api.php/provide/vod',
        name: '⊕快车资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕AGE动漫1: {
        api: 'https://ageapi.omwjhz.com/v2/search',
        name: '⊕AGE动漫1',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕极速资源网: {
        api: 'https://jszyapi.com/api.php/provide/vod',
        name: '⊕极速资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕闪电资源网: {
        api: 'https://xsd.sdzyapi.com/api.php/provide/vod',
        name: '⊕闪电资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕索尼资源网: {
        api: 'https://suoniapi.com/api.php/provide/vod/from/snm3u8',
        name: '⊕索尼资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕360资源网: {
        api: 'https://360zy.com/api.php/provide/vod',
        name: '⊕360资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕新浪资源网: {
        api: 'https://api.xinlangapi.com/xinlangapi.php/provide/vod',
        name: '⊕新浪资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕虎牙资源网: {
        api: 'https://www.huyaapi.com/api.php/provide/vod',
        name: '⊕虎牙资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕量子资源网: {
        api: 'https://cj.lziapi.com/api.php/provide/vod',
        name: '⊕量子资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕豪华资源网: {
        api: 'https://hhzyapi.com/api.php/provide/vod',
        name: '⊕豪华资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕云解资源网: {
        api: 'https://m3u8.apiyhzy.com/api.php/provide/vod',
        name: '⊕云解资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕卧龙资源网: {
        api: 'https://collect.wolongzyw.com/api.php/provide/vod',
        name: '⊕卧龙资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕黑木耳资源站: {
        api: 'https://xml02.heimuer.xyz/api.php/provide/vod',
        name: '⊕黑木耳资源站',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕无尽资源网: {
        api: 'https://api.wujinapi.me/api.php/provide/vod',
        name: '⊕无尽资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕淘片资源网: {
        api: 'https://taopianapi.com/cjapi/mc/vod/xml',
        name: '⊕淘片资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕八戒资源网: {
        api: 'http://cj.bajiecaiji.com/inc/seacmsapi.php',
        name: '⊕八戒资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕四九资源网: {
        api: 'https://49zyw.com/api.php/provide/vod',
        name: '⊕四九资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕HW8资源: {
        api: 'https://huawei8.live/api.php/provide/vod',
        name: '⊕HW8资源',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕暴风资源网: {
        api: 'https://bfzyapi.com/api.php/provide/vod',
        name: '⊕暴风资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕最大资源网: {
        api: 'https://api.zuidapi.com/api.php/provide/vod',
        name: '⊕最大资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕U酷资源网: {
        api: 'https://api.ukuapi.com/api.php/provide/vod',
        name: '⊕U酷资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕魔都动漫: {
        api: 'https://caiji.moduapi.cc/api.php/provide/vod',
        name: '⊕魔都动漫',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕IKun资源网: {
        api: 'https://www.ikunzy.com/api.php/seaxml/vod',
        name: '⊕IKun资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    ⊕红牛资源网: {
        api: 'https://www.hongniuzy3.com/api.php/provide/vod',
        name: '⊕红牛资源网',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅TV酷影视ᴴ: {
        api: 'https://www.tvkuys.xyz/api.php/app',
        name: '🔅TV酷影视ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅飘零影院ᴴ: {
        api: 'https://p2100.net/api.php/provide/vod',
        name: '🔅飘零影院ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅段友影视ᴴ: {
        api: 'https://shangjihuoke.com/api.php/tv.vod',
        name: '🔅段友影视ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅影图资源ᴴ: {
        api: 'https://cj.vodimg.top/api.php/provide/vod',
        name: '🔅影图资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅樱花资源ᴴ: {
        api: 'https://yhzy.cc/api.php/provide/vod',
        name: '🔅樱花资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅苹果资源ᴴ: {
        api: 'https://pg.fenwe078.cf/api.php/provide/vod',
        name: '🔅苹果资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅TK资源ᴴ: {
        api: 'https://m3u8.tiankongapi.com/api.php/provide/vod',
        name: '🔅TK资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅华为吧影视ᴴ: {
        api: 'https://cjhwba.com/api.php/provide/vod',
        name: '🔅华为吧影视ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅木耳资源ᴴ: {
        api: 'https://json02.heimuer.xyz/api.php/provide/vod',
        name: '🔅木耳资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅春莹淘片ᴴ: {
        api: 'https://taopianapi.com/cjapi/mc10/vod/xml.html',
        name: '🔅春莹淘片ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🔅牛牛资源ᴴ: {
        api: 'https://api.niuniuzy.me/api.php/provide/vod/from/nnm3u8',
        name: '🔅牛牛资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 电影天堂资源ᴴ: {
        api: 'http://caiji.dyttzyapi.com/api.php/provide/vod',
        name: '🌀 电影天堂资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 如意资源ᴴ: {
        api: 'https://cj.rycjapi.com/api.php/provide/vod',
        name: '🌀 如意资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 天涯资源ᴴ: {
        api: 'https://tyyszy.com/api.php/provide/vod',
        name: '🌀 天涯资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 非凡影视ᴴ: {
        api: 'http://ffzy5.tv/api.php/provide/vod',
        name: '🌀 非凡影视ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 iqiyi资源ᴴ: {
        api: 'https://www.iqiyizyapi.com/api.php/provide/vod',
        name: '🌀 iqiyi资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 豆瓣资源ᴴ: {
        api: 'https://dbzy.tv/api.php/provide/vod',
        name: '🌀 豆瓣资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 魔爪资源ᴴ: {
        api: 'https://mozhuazy.com/api.php/provide/vod',
        name: '🌀 魔爪资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 百度云资源ᴴ: {
        api: 'https://api.apibdzy.com/api.php/provide/vod',
        name: '🌀 百度云资源ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
    🌀 旺旺短剧ᴴ: {
        api: 'https://wwzy.tv/api.php/provide/vod',
        name: '🌀 旺旺短剧ᴴ',
        detail: 'http://caiji.dyttzyapi.com',
    },
};
// 添加聚合搜索的配置选项
const AGGREGATED_SEARCH_CONFIG = {
    enabled: true,             // 是否启用聚合搜索
    timeout: 8000,            // 单个源超时时间（毫秒）
    maxResults: 10000,          // 最大结果数量
    parallelRequests: true,   // 是否并行请求所有源
    showSourceBadges: true    // 是否显示来源徽章
};

// 抽象API请求配置
const API_CONFIG = {
    search: {
    	// 修改搜索接口为返回更多详细数据（包括视频封面、简介和播放列表）
        path: '/api.php/provide/vod/?ac=videolist&wd=',
        headers: {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept': 'application/json'
        }
    },
    detail: {
    	// 修改详情接口也使用videolist接口，但是通过ID查询，减少请求次数
        path: '/api.php/provide/vod/?ac=videolist&ids=',
        headers: {
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
            'Accept': 'application/json'
        }
    }
};

// 优化后的正则表达式模式
const M3U8_PATTERN = /\$https?:\/\/[^"'\s]+?\.m3u8/g;

// 添加自定义播放器URL
const CUSTOM_PLAYER_URL = 'player.html'; // 使用相对路径引用本地player.html

// 增加视频播放相关配置
const PLAYER_CONFIG = {
    autoplay: true,
    allowFullscreen: true,
    width: '100%',
    height: '600',
    timeout: 15000,  // 播放器加载超时时间
    filterAds: true,  // 是否启用广告过滤
    autoPlayNext: true,  // 默认启用自动连播功能
    adFilteringEnabled: true, // 默认开启分片广告过滤
    adFilteringStorage: 'adFilteringEnabled' // 存储广告过滤设置的键名
};

// 增加错误信息本地化
const ERROR_MESSAGES = {
    NETWORK_ERROR: '网络连接错误，请检查网络设置',
    TIMEOUT_ERROR: '请求超时，服务器响应时间过长',
    API_ERROR: 'API接口返回错误，请尝试更换数据源',
    PLAYER_ERROR: '播放器加载失败，请尝试其他视频源',
    UNKNOWN_ERROR: '发生未知错误，请刷新页面重试'
};

// 添加进一步安全设置
const SECURITY_CONFIG = {
    enableXSSProtection: true,  // 是否启用XSS保护
    sanitizeUrls: true,         // 是否清理URL
    maxQueryLength: 100,        // 最大搜索长度
    // allowedApiDomains 不再需要，因为所有请求都通过内部代理
};

// 添加多个自定义API源的配置
const CUSTOM_API_CONFIG = {
    separator: ',',           // 分隔符
    maxSources: 5,            // 最大允许的自定义源数量
    testTimeout: 5000,        // 测试超时时间(毫秒)
    namePrefix: 'Custom-',    // 自定义源名称前缀
    validateUrl: true,        // 验证URL格式
    cacheResults: true,       // 缓存测试结果
    cacheExpiry: 5184000000,  // 缓存过期时间(2个月)
    adultPropName: 'isAdult' // 用于标记成人内容的属性名
};

// 新增隐藏内置黄色采集站API的变量，默认为true
const HIDE_BUILTIN_ADULT_APIS = true;
