<?php
require '../app/vendor/autoload.php';

$app = new \Slim\Slim([
    'debug' => true,
    'view' => new \Slim\Views\Twig(),
]);

$app->environment()['sites_path'] = '/var/www/htdocs/';
$app->environment()['sites_excluded'] = [
	'.', '..', 'htdig', 'index.html', 'manual', 'slim'
];

$app->view()->parserOptions = [
    'debug' => true,
    'cache' => '../app/data/cache',
];

$get_sites = function($app) { 
    $sites = [];
    if ($handle = opendir($app->environment()['sites_path'])) {
        $i = 0;
        while (false !== ($site = readdir($handle))) {
            if (in_array($site, $app->environment()['sites_excluded'])) { 
                continue;
	    }
            $sites[$i][0] = $site;
            $sites[$i][1] ='http://'.$_SERVER['SERVER_NAME'].'/' . $site;
            $i++;
        }
    }
    return $sites;    
};

$get_sys_out_array = function($command) {
    exec($command, $out);
    foreach ($out as $key => $value) {
        $value = explode(':', $value);
        $out[$value[0]] = ltrim($value[1]);
        unset($out[$key]);
    }
    return $out;
};

$get_env = function($app) {
    return [
        'os.kernel' => php_uname('s').' '.php_uname('r').' '.php_uname('m'),
        'php.version' => phpversion(),
        'php.sapi_name' => php_sapi_name(),
        'slim.version' => $app::VERSION,
        'slim.mode' => $app->getMode(),
    ];
};

$app->get('/', function () use ($app, $get_sites, $get_env, $get_sys_out_array) {
    $sites = $get_sites($app);
    $cpu = $get_sys_out_array('lscpu');
    $os_release = $get_sys_out_array('lsb_release -a');
    $env = $get_env($app) + $os_release + $cpu;
    $body = $app->view()->render('home.twig', ['sites' => $sites, 'env' => $env]);
    $app->response->body($body);
});

$app->get('/info', function () use ($app) {
	ob_start();
	phpinfo();
	$body = ob_get_flush();
	$app->response->body($body);
});

$app->get('/opcache', function () use ($app) {
    require('../app/lib/OpCacheDataModel.php');
    $dataModel = new OpCacheDataModel();
    $data = [
        'PageTitle' => $dataModel->getPageTitle(),
        'StatusDataRows' => $dataModel->getStatusDataRows(),
        'ConfigDataRows' => $dataModel->getConfigDataRows(),
        'ScriptStatusCount' => $dataModel->getScriptStatusCount(),
        'ScriptStatusRows' => $dataModel->getScriptStatusRows(),
        'GraphDataSetJson' => $dataModel->getGraphDataSetJson(),
        'HumanUsedMemory' => $dataModel->getHumanUsedMemory(),
        'HumanFreeMemory' => $dataModel->getHumanFreeMemory(),
        'HumanWastedMemory' => $dataModel->getHumanWastedMemory(),
        'WastedMemoryPercentage' => $dataModel->getWastedMemoryPercentage(),
        'D3Scripts' => $dataModel->getD3Scripts(),
    ];
    $body = $app->view()->render('opcache.twig', ['data' => $data]);
    $app->response->body($body);
});

$app->run();
