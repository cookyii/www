<?php
/**
 * build.php
 * @author Revin Roman
 * @link https://rmrevin.com
 */

if (file_exists(__DIR__ . '/.env.php')) {
    require __DIR__ . '/.env.php';
}

defined('YII_ENV') || define('YII_ENV', 'dev');

/** @var array $apps list of existing applications */
$apps = ['frontend', 'backend'];

/** Automatic detection applications */
automaticDetectionApplications($apps);

/** @var array $config build configuration */
$config = [
    'default' => [
        '.description' => 'Default build',
        '.depends' => [sprintf('env/%s', YII_ENV)],
    ],

    'map' => [
        '.description' => 'Show map of all tasks in current build config',
        '.task' => 'cookyii\build\tasks\MapTask',
    ],

    'self' => [
        '.description' => 'Internal tasks',
        '.task' => [
            'class' => 'cookyii\build\tasks\SelfTask',
            'composer' => '../composer.phar',
        ],
    ],

    'env' => [
        'prod' => [
            '.description' => 'Build project with production environment',
            '.depends' => [
                'environment/check',
                'clear',
                'composer/selfupdate', 'composer/update-fxp', 'composer/install-prod',
                'npm/install', 'bower/update', 'less',
                'migrate', 'rbac',
            ],
        ],
        'demo' => [
            '.description' => 'Build project with demo environment',
            '.depends' => [
                'environment/check',
                'clear',
                'composer/selfupdate', 'composer/update-fxp', 'composer/install',
                'npm/install', 'bower/update', 'less',
                'migrate', 'rbac',
            ],
        ],
        'dev' => [
            '.description' => 'Build project with developer environment',
            '.depends' => [
                'environment/check',
                'clear',
                'composer/selfupdate', 'composer/update-fxp', 'composer/install',
                'npm/install', 'bower/update', 'less',
                'migrate', 'rbac',
            ],
        ],
    ],

    'environment' => [
        'check' => [
            '.description' => 'Check file exists `.environment.php`',
            '.task' => [
                'class' => 'cookyii\build\tasks\FileExistsTask',
                'filename' => '.env.php',
                'message' => 'Warning!' . "\n"
                    . 'Need fill environment file' . "\n"
                    . '%s' . "\n"
                    . 'Template is .env.dist.php',
            ],
        ],
    ],

    'clear' => [
        '.description' => 'Delete all temporary files and remove installed packages',
    ],

    'composer' => [
        '.description' => 'Install all depending composer for development environment (with `required-dev`)',
        '.task' => [
            'class' => 'cookyii\build\tasks\ComposerTask',
            'composer' => '../composer.phar',
        ],
        'update-fxp' => [
            '.description' => 'Update `fxp/composer-asset-plugin`',
            '.task' => [
                'class' => 'cookyii\build\tasks\CommandTask',
                'commandline' => '../composer.phar global require "fxp/composer-asset-plugin:~1.1.0"',
            ],
        ],
    ],

    'npm' => [
        '.description' => 'Tasks for npm',
        'install' => [
            '.description' => 'Install all npm dependencies',
            '.task' => [
                'class' => 'cookyii\build\tasks\CommandTask',
                'commandline' => 'npm install',
            ],
        ],
        'update' => [
            '.description' => 'Update all npm dependencies',
            '.task' => [
                'class' => 'cookyii\build\tasks\CommandTask',
                'commandline' => 'npm update',
            ],
        ],
    ],

    'bower' => [
        '.description' => 'Tasks for bower',
        'install' => [
            '.description' => 'Install all bower dependencies',
            '.task' => [
                'class' => 'cookyii\build\tasks\CommandTask',
                'commandline' => './node_modules/.bin/bower install',
            ],
        ],
        'update' => [
            '.description' => 'Update all bower dependencies',
            '.task' => [
                'class' => 'cookyii\build\tasks\CommandTask',
                'commandline' => './node_modules/.bin/bower update',
            ],
        ],
    ],

    'less' => [
        '.description' => 'Compile all less styles',
    ],

    'migrate' => [
        '.description' => 'Execute all migrations',
        '.task' => [
            'class' => 'cookyii\build\tasks\CommandTask',
            'commandline' => './yii migrate --interactive=0',
        ],
    ],

    'rbac' => [
        '.description' => 'Update rbac rules',
        '.task' => [
            'class' => 'cookyii\build\tasks\CommandTask',
            'commandline' => './yii rbac/update',
        ],
    ],
];

// create applications tasks
if (!empty($apps)) {
    foreach ($apps as $app) {
        appendClearTask($config, 'clear', $app);
        appendLessTask($config, 'less', $app);
    }
}

/**
 * @param string $app
 * @param string|null $key
 * @return array|string|null
 */
function getPath($app, $key = null)
{
    $base_path = __DIR__;
    $app_path = $base_path . DIRECTORY_SEPARATOR . sprintf('%s-app', $app);

    $list = [
        'base' => $base_path,
        'app' => $app_path,
        'assets' => $app_path . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . '_sources',
        'node' => $base_path . DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR . '.bin',
    ];

    return empty($key)
        ? $list
        : (isset($list[$key]) ? $list[$key] : null);
}

/**
 * @param array $config
 * @param string $task_name
 * @param string $app
 */
function appendClearTask(array &$config, $task_name, $app)
{
    appendEmptyTask($config, $task_name);

    $path_list = getPath($app);

    $config[$task_name]['.depends'][] = sprintf('*/%s', $app);
    $config[$task_name][$app] = [
        '.description' => 'Remove all temp files',
        '.task' => [
            'class' => 'cookyii\build\tasks\DeleteTask',
            'deleteDir' => false,
            'fileSets' => [
                ['dir' => sprintf('%s/runtime', $path_list['app']), 'exclude' => ['.gitignore']],
                ['dir' => sprintf('%s/web/assets', $path_list['app']), 'exclude' => ['.gitignore']],
                ['dir' => sprintf('%s/web/minify', $path_list['app']), 'exclude' => ['.gitignore']],
            ],
        ],
    ];
}

/**
 * @param array $config
 * @param string $task_name
 * @param string $app
 */
function appendLessTask(array &$config, $task_name, $app)
{
    appendEmptyTask($config, $task_name);

    $config[$task_name]['.depends'][] = sprintf('*/%s', $app);
    $config[$task_name][$app] = [
        '.description' => sprintf('Compile all less styles for `%s` application', $app),
        '.task' => [
            'class' => 'cookyii\build\tasks\CommandTask',
            'commandline' => [
                cmd($app, '{node}/gulp less --app {a}'),
            ],
        ],
    ];
}

/**
 * @param array $config
 * @param string $task_name
 */
function appendEmptyTask(array &$config, $task_name)
{
    if (!isset($config[$task_name])) {
        $config[$task_name] = [];
    }

    if (!isset($config[$task_name]['.depends'])) {
        $config[$task_name]['.depends'] = [];
    }
}

/**
 * @param string $app
 * @param string $command
 * @return string
 */
function cmd($app, $command)
{
    $path_list = getPath($app);

    $command = str_replace(
        ['{a}'],
        [$app],
        $command
    );

    return str_replace(
        array_map(function ($val) {
            return sprintf('{%s}', $val);
        }, array_keys($path_list)),
        array_values($path_list),
        $command
    );
}

/**
 * @param array $apps
 */
function automaticDetectionApplications(array &$apps)
{
    $handler = opendir(__DIR__);
    if (is_resource($handler)) {
        while (($file = readdir($handler)) !== false) {
            if (preg_match('|^([a-zA-Z0-9\-]+)\-app$|i', $file, $m)) {
                $app = $m[1];
                $cmd = __DIR__ . DIRECTORY_SEPARATOR . $app;
                if (!in_array($app, $apps, true) && file_exists($cmd)) {
                    $apps[] = $m[1];
                }
            }
        }

        closedir($handler);
    }
}

return $config;