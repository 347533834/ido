环境配置

NGINX、PHP7.1+、MYSQL

PHP扩展
php7.1-curl
php7.1-mysql
...

站点配置
host  mapping.servhain.net
root  ./public

计划任务 10min 执行一次
*/10 * * * *  ./public/cron_*.sh


