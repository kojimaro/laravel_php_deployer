<?php
namespace Deployer;

// larave向けのレシピ（テンプレート）を読み込む
require 'recipe/laravel.php';
// npm向けのレシピ（テンプレート）を読み込む
require 'contrib/npm.php';

// Config

// デプロイ時に取得するGitリポジトリのURL
set('repository', 'git@github.com:kojimaro/laravel_php_deployer.git');

// releaseのバージョンを保持する数
set('keep_releases', 3);

// ブランチ
set('branch', 'main');

// アプリケーションの更新に関わらず
// 継続で利用したいものを置くディレクトリやファイル（.env storage など）を指定する
add('shared_files', ['.env']);
add('shared_dirs', ['storage']);

// デプロイ時に書き込み権限を設定するディレクトリ（storage など）を指定する
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// Hosts

// デプロイ先のサーバーのアドレス（IPまたはホスト名）を設定する
host('192.168.64.53')
    ->set('identity_file', '~/.ssh/local_amazon_linux_key') //SSH接続に使用する秘密鍵
    ->set('remote_user', 'ec2-user') //SSH接続するユーザー名
    ->set('deploy_path', '~/xxx.com.kojima'); //デプロイ先のディレクトリ

// Tasks
after('deploy:vendors', 'key:init');
after('artisan:migrate', 'npm:install');
after('npm:install', 'npm:run:build');

// APP_KEYが設定されていない場合に、key:generateを実行するタスク
desc('key generate if not exists');
task('key:init', function () {
    $key = run("grep '^APP_KEY=' {{deploy_path}}/shared/.env | cut -d '=' -f2-");
    if (empty($key)) {
        artisan('key:generate')();
    }
});

// npm run buildを実行するタスク
task('npm:run:build', function () {
    cd('{{release_path}}');
    run('npm run build');
});

// Hooks

// deployerでは、デプロイプロセスが同時に実行されないように「ロック」をかける
// デプロイが失敗するとロックが残るため解除する処理
after('deploy:failed', 'deploy:unlock');
