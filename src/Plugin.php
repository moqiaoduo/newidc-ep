<?php

namespace NewIDC\EasyPanel;

use NewIDC\Plugin\Server;

class Plugin extends Server
{
    protected $name = 'Easypanel';

    protected $composer = 'newidc/easypanel';

    protected $description = 'Easypanel对接插件';

    public function activate()
    {
        $ep = new EasyPanel($this->getHost(), $this->getPort(), $this->server->access_key);
        $params = $this->product->server_configs;
        $params['name'] = $this->service->username;
        $params['passwd'] = $this->service->password;
        $params['vhost_domains'] = $this->service->domain;
        $params['access'] = 1;
        $result = $ep->add_vh($params);
        if (is_null($result)) return ['code' => 1, 'msg' => 'HTTP无响应，可能是服务器不在线或者网络拥堵'];
        switch ($result['result']) {
            case 200:
                return ['code' => 0];
            case 403:
                return ['code' => 2, 'msg' => $result['msg']];
            case 505:
                return ['code' => 3, 'msg' => '产品配置错误'];
            default:
                return ['code' => 4, 'msg' => $result['msg'] ?? '开通失败'];
        }
    }

    public function suspend()
    {
        $ep = new EasyPanel($this->getHost(), $this->getPort(), $this->server->access_key);
        $result = $ep->update_vh($this->service->username, 1);

        if (is_null($result)) return ['code' => 1, 'msg' => 'HTTP无响应，可能是服务器不在线或者网络拥堵'];
        switch ($result['result']) {
            case 200:
                return ['code' => 0];
            case 403:
                return ['code' => 2, 'msg' => $result['msg']];
            default:
                return ['code' => 4, 'msg' => $result['msg'] ?? '暂停失败'];
        }
    }

    public function unsuspend()
    {
        $ep = new EasyPanel($this->getHost(), $this->getPort(), $this->server->access_key);
        $result = $ep->update_vh($this->service->username, 0);

        if (is_null($result)) return ['code' => 1, 'msg' => 'HTTP无响应，可能是服务器不在线或者网络拥堵'];
        switch ($result['result']) {
            case 200:
                return ['code' => 0];
            case 403:
                return ['code' => 2, 'msg' => $result['msg']];
            default:
                return ['code' => 4, 'msg' => $result['msg'] ?? '解除暂停失败'];
        }
    }

    public function terminate()
    {
        $ep = new EasyPanel($this->getHost(), $this->getPort(), $this->server->access_key);
        $result = $ep->del_vh($this->service->username);

        if (is_null($result)) return ['code' => 1, 'msg' => 'HTTP无响应，可能是服务器不在线或者网络拥堵'];
        switch ($result['result']) {
            case 200:
                return ['code' => 0];
            case 403:
                return ['code' => 2, 'msg' => $result['msg']];
            default:
                return ['code' => 4, 'msg' => $result['msg'] ?? '销毁失败'];
        }
    }

    public function userLogin()
    {
        return <<<HTML
<form method="post" action="http://{$this->getHost(false)}:{$this->getPort()}/vhost/?c=session&a=login" target="_blank">
<input type="hidden" name="username" value="{$this->service->username}">
<input type="hidden" name="passwd" value="{$this->service->password}">
<button type="submit" class="layui-btn">登录面板</button>
</form>
HTML;
    }

    public function adminLogin()
    {
        return <<<HTML
<form method="post" action="http://{$this->getHost(false)}:{$this->getPort()}/admin/?c=session&a=login" target="_blank">
<input type="hidden" name="username" value="{$this->server->username}">
<input type="hidden" name="passwd" value="{$this->server->password}">
<button type="submit" class="btn btn-success">登录管理</button>
</form>
HTML;
    }

    public function changePassword($password)
    {
        $ep = new EasyPanel($this->getHost(), $this->getPort(), $this->server->access_key);
        $result = $ep->change_password($this->service->username, $password);

        if (is_null($result)) return ['code' => 1, 'msg' => 'HTTP无响应，可能是服务器不在线或者网络拥堵'];
        switch ($result['result']) {
            case 200:
                return ['code' => 0];
            case 403:
                return ['code' => 2, 'msg' => $result['msg']];
            default:
                return ['code' => 4, 'msg' => $result['msg'] ?? '修改密码失败'];
        }
    }

    public function upgradeDowngrade()
    {
        // TODO: Implement upgradeDowngrade() method.
    }

    public static function productConfig()
    {
        return array (
            'cdn' =>
                array (
                    'label' => 'CDN',
                    'help' => '是否开设CDN服务',
                    'type' => 'switch',
                    'default' => 0,
                ),
            'web_quota' =>
                array (
                    'label' => 'web配额',
                    'help' => '单位MB',
                    'type' => 'text',
                ),
            'db_type' =>
                array (
                    'label' => '数据库类型',
                    'options' =>
                        array (
                            'mysql' => 'MySQL',
                            'sqlsrv' => 'SQL SERVER',
                        ),
                    'type' => 'radio',
                    'default' => 'mysql',
                ),
            'db_quota' =>
                array (
                    'label' => '数据库配额',
                    'help' => '单位MB，0表示不开通',
                    'type' => 'text',
                ),
            'ftp' =>
                array (
                    'label' => 'FTP',
                    'type' => 'switch',
                    'default' => 1,
                ),
            'ftp_connect' =>
                array (
                    'label' => 'FTP连接数',
                    'help' => '单位kb，0为不限',
                    'type' => 'text',
                ),
            'ftp_usl' =>
                array (
                    'label' => 'FTP上传速度',
                    'help' => '单位kb，0为不限',
                    'type' => 'text',
                ),
            'ftp_dsl' =>
                array (
                    'label' => 'FTP下载速度',
                    'help' => '单位kb，0为不限',
                    'type' => 'text',
                ),
            'log_file' =>
                array (
                    'label' => '独立日志',
                    'type' => 'switch',
                    'default' => 0,
                ),
            'domain' =>
                array (
                    'label' => '绑定域名数',
                    'help' => '-1表示不限',
                    'type' => 'text',
                ),
            'max_connect' =>
                array (
                    'label' => '最大连接数',
                    'help' => '其实就是HTTP并发数',
                    'type' => 'text',
                ),
            'speed_limit' =>
                array (
                    'label' => '带宽限制',
                    'help' => '单位kb/s,0无限',
                    'type' => 'text',
                ),
            'subdir' =>
                array (
                    'label' => '默认绑定到子目录',
                    'help' => '例如wwwroot',
                    'type' => 'text',
                    'default' => 'wwwroot',
                ),
            'subdir_flag' =>
                array (
                    'label' => '允许绑定子目录',
                    'help' => '是否允许绑定域名到子目录',
                    'type' => 'switch',
                    'default' => 1,
                ),
            'max_subdir' =>
                array (
                    'label' => '最多绑定子目录数',
                    'help' => '0表示不限',
                    'type' => 'text',
                ),
            'flow_limit' =>
                array (
                    'label' => '流量限制',
                    'help' => '单位G/每月，需商业版支持',
                    'type' => 'text',
                ),
            'max_worker' =>
                array (
                    'label' => '工作数',
                    'type' => 'text',
                ),
            'htaccess' =>
                array (
                    'label' => '启用htaccess',
                    'type' => 'switch',
                    'default' => 1,
                ),
            'log_handle' =>
                array (
                    'label' => '是否开启日志分析功能',
                    'type' => 'switch',
                    'default' => 0,
                ),
            '<p>下面的设置可能会有冲突，请注意</p>' =>
                array (
                    'label' => '注意',
                    'type' => 'html',
                ),
            'templete' =>
                array (
                    'label' => '空间类型',
                    'help' => '语言模板,如php',
                    'type' => 'text',
                ),
            'subtemplete' =>
                array (
                    'label' => '语言引擎',
                    'help' => '如php52,php53,如果语言模板没有语言引擎,可为空',
                    'type' => 'text',
                ),
            'module' =>
                array (
                    'label' => '模块',
                    'help' => '如果使用空间类型和语言引擎的话请不要选!!选中将无法使用,并且选择没安装的话就无法使用了',
                    'type' => 'select',
                    'options' => [arrayKeyValueSame(['php','iis'])],
                ),
        );
    }

    protected function defaultPort()
    {
        return 3312;
    }
}