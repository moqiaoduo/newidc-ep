<?php

namespace NewIDC\EasyPanel;

class EasyPanel
{
    public $protocol ;
    public $ip;
    public $port;
    public $r;
    public $skey;

    public function __construct($ip = '127.0.0.1',$port = '3312',$skey = 'test',$protocol = 'http')
    {
        $this->protocol = $protocol; //仅支持http和https
        $this->ip = $ip;
        $this->port = $port;
        $this->r = rand(100000,999999);
        $this->skey = $skey;
    }
    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }
    public function url($info=array()){
        $url = '';
        foreach ($info as $k=>$v){
            $url .= $k.'='.$v.'&';
        }
        return $this->protocol.'://'.$this->ip.':'.$this->port.'/api/index.php?'.$url.'r='.$this->r.'&s='.md5($info['a'].$this->skey.$this->r).'&json=1';
    }

    public function open($info=array()){
        $url = $this->url($info);
        if(function_exists('curl_init')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            if ($this->protocol == 'https' or $this->protocol == 'HTTPS'){
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
            }
            $r = curl_exec($ch);
            curl_close($ch);
            return json_decode($r,true);
        }else{
            return json_decode(file_get_contents($url),true);
        }
    }

    //获取easypanel的信息(包括了kangle的信息)
    public function info(){
        /*
         * c : whm
         * a : info
         * 调用成功后你将得到如下返回数据:
         * {"result":200,"server":[{"0":"kangle"}],"version":[{"0":"3.4.8"}],"type":[{"0":"enterprise"}],"os":[{"0":"windows"}],"license_id":[{"0":"73646_1387957818"}],"license_name":[{"0":"dsdds"}],"total_run":[{"0":"611038"}],"connect":[{"0":"4"}],"request":[{"0":"14105"}],"accept":[{"0":"4722"}],"vh":[{"0":"43"}],"kangle_home":[{"0":"D:\\\easypanel\\"}],"update_code":[{"0":"win_x64"}],"easypanel_version":"2.6.17"}
         * 其中result=200表示调用成功。
         * version是kangle的版本
         * type是kangle的商业版还是免费版(enterprise  or free)
         * total_run 是运行时间
         * vh 是虚拟主机数量
         * connect是当前有多少请求进来。
         *
         */

        return $this->open(array('c'=>'whm','a'=>'info'));

    }

    //获取站点信息
    public function getvh($name = null){
        /*
         * c : whm
         * a : getVh
         * name : (虚拟主机名称)
         *
         * 返回的虚拟主机的参数说明
         * name : 主机名称
         * doc_root:主机的主目录
         * uid:系统生成(用户在服务器上的账户ID)，权限控制使用。不可更改。
         * gid :系统账户组ID
         * module:是属于哪个模块，目前有php和iis两个模块。
         * templete，subtemplete，已废弃
         * create_time ：创建时间
         * expire_time2 :过期时间
         * status:站点状态，0正常，1为关闭。
         * subdir_flag 是否允许子域名,1为允许，0为不允许
         * subdir 默认子目录名称
         * web_quota 空间大小
         * db_quota 数据库大小
         * domain :允许绑定域名数量 -1为不限
         * max_connect 最多连接数
         * max_worker 最多工作者
         * ftp : 是否开通ftp,1为开通，0为不开通
         * db_name 数据库名称(一般和主机名称等同,sql server 特殊)
         */
        return $this->open(array('c'=>'whm','a'=>'getVh','name'=>$name));
    }

    //创建站点
    public function add_vh($info = array()){
        /*
         * 固定值：
         * c : whm
         * a : add_vh
         * r : 随机
         * s : 秘钥
         * init : 1  //表示创建
         * name:网站账号,数据库账号,ftp账号同步
         * passwd:网站密码,数据库密码，ftp密码同步(只创建时同步)
         * product_id 和 product_name 都存在参数时报 [result] => 505，product_id 和 product_name 只能存在一个或者 一个都不存在，当一个都不存在时 详细配置虚拟主机参数
         */
        if(isset($info['product_id']) && isset($info['product_name']) && trim(intval($info['product_id'])) != null and trim($info['product_name']) != null){
            return array('result'=>'505');
        }elseif(isset($info['product_id']) && trim(intval($info['product_id'])) != null){
            return $this->open(array('c'=>'whm','a'=>'add_vh','init'=>1,'name'=>$info['name'],'passwd'=>$info['passwd'],'product_id'=>$info['product_id']));
        }elseif (isset($info['product_name']) && trim($info['product_name']) != null){
            return $this->open(array('c'=>'whm','a'=>'add_vh','init'=>1,'name'=>$info['name'],'passwd'=>$info['passwd'],'product_id'=>$info['product_name']));
        }else{
            /*
             * 详细配置模式下的传入值列表
             * cdn:是否为CDN空间,是则发送1
             * Templete:语言(html|php|iis)
             * Subtemplete:语言引擎,php52|php53|php5217
             * web_quota:网页空间大小,数字
             * db_quota：数据库大小,数字
             * db_type:数据库类型,mysql|sqlsrv
             * subdir_flag:是否允许绑定子目录,1为是
             * Subdir:默认绑定目录,可为空,例:wwwroot
             * max_subdir:最多子目录数
             * domain:需要绑定的域名，可空,默认会绑定到subdir指 定的目录下。
             * ftp:是否开启ftp,1为是
             * ftp_connect：ftp最多连接数
             * ftp_usl:ftp上传速度限制,单位kb
             * ftp_dsl:ftp下载限制。单位kb
             * access:是否启用自定义控制，如果是请输入自定义控制文件名access.xml
             * speed_limit:带宽限制,数字型，默认为不限(kb)
             * log_handle:是否开启日志析分功能，1为是
             * flow_limit:流量限制,数字型,默认不限(kb)
             */
            $info=array_merge($info,array('c'=>'whm','a'=>'add_vh','init'=>1));
            //dd($info);
            //$info['c']='whm';$info['a']='add_vh';$info['init']='1';
            //$info=array('c'=>'whm','a'=>'add_vh','init'=>1,'name'=>$info['name'],'passwd'=>$info['passwd']);
            return $this->open($info);

        }
    }

    //修改站点信息
    public function edit_vh($info = array()){
        /*
         * 固定值：
         * c : whm
         * a : add_vh
         * r : 随机
         * s : 秘钥
         * edit : 1  //表示修改
         * name:网站账号,数据库账号,ftp账号同步
         * passwd:网站密码,数据库密码，ftp密码同步(只创建时同步)
         *  product_id 和 product_name 都存在参数时报 [result] => 505，product_id 和 product_name 只能存在一个或者 一个都不存在，当一个都不存在时 详细配置虚拟主机参数
         */
        if(trim(intval($info['product_id'])) != null and trim($info['product_name']) != null){
            return array('result'=>'505');
        }elseif(trim(intval($info['product_id'])) != null){
            return $this->open(array('c'=>'whm','a'=>'add_vh','edit'=>'1','name'=>$info['name'],'passwd'=>$info['passwd'],'product_id'=>$info['product_id']));
        }elseif (trim($info['product_name']) != null){
            return $this->open(array('c'=>'whm','a'=>'add_vh','edit'=>'1','name'=>$info['name'],'passwd'=>$info['passwd'],'product_id'=>$info['product_name']));
        }else{
            /*
             * 详细配置模式下的传入值列表
             * cdn:是否为CDN空间,是则发送1
             * Templete:语言(html|php|iis)
             * Subtemplete:语言引擎,php52|php53|php5217
             * web_quota:网页空间大小,数字
             * db_quota：数据库大小,数字
             * db_type:数据库类型,mysql|sqlsrv
             * subdir_flag:是否允许绑定子目录,1为是
             * Subdir:默认绑定目录,可为空,例:wwwroot
             * max_subdir:最多子目录数
             * domain:需要绑定的域名，可空,默认会绑定到subdir指 定的目录下。
             * ftp:是否开启ftp,1为是
             * ftp_connect：ftp最多连接数
             * ftp_usl:ftp上传速度限制,单位kb
             * ftp_dsl:ftp下载限制。单位kb
             * access:是否启用自定义控制，如果是请输入自定义控制文件名access.xml
             * speed_limit:带宽限制,数字型，默认为不限(kb)
             * log_handle:是否开启日志析分功能，1为是
             * flow_limit:流量限制,数字型,默认不限(kb)
             */
            $info=array_merge($info,array('c'=>'whm','a'=>'add_vh','edit'=>'1'));
            //$info['c']='whm';$info['a']='add_vh';$info['edit']='1';
            //$info=array('c'=>'whm','a'=>'add_vh','edit'=>'1','name'=>$info['name'],'passwd'=>$info['passwd']);
            return $this->open($info);
        }
    }

    //获取站点列标
    public function listvh(){
        /*
         * a : whm
         * c : listVh
         */
        return $this->open(array('c'=>'whm','a'=>'listVh'));
    }

    //修改站点密码
    public function change_password($name,$passwd){
        /*
         * c :whm
         * a : change_password
         * name :站点名称
         * passwd :新的密码
         */
        return $this->open(array('c'=>'whm','a'=>'change_password','name'=>$name,'passwd'=>$passwd));
    }

    //修改站点状态
    public function update_vh($name,$status){
        /*
         * c :whm
         * a : update_vh
         * name :站点名称
         * status : 新状态(0正常，1关闭)
         */
        return $this->open(array('c'=>'whm','a'=>'update_vh','name'=>$name,'status'=>$status));
    }


    //删除站点
    public function del_vh($name){
        /*
         * c :whm
         * a : del_vh
         * name :站点名称
         */
        return $this->open(array('c'=>'whm','a'=>'del_vh','name'=>$name));
    }


    //获取站点的数据库使用量
    public function getDbUsed($name){
        /*
         * c :whm
         * a : getDbUsed
         * name :站点名称
         */
        return $this->open(array('c'=>'whm','a'=>'getDbUsed','name'=>$name));
    }
}