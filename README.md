WEB System
===========================
这是一个平台系统

需求REQUIREMENTS
-------------------

web server 

PHP 5.3 and some extension

mysql
 

目录结构DIRECTORY STRUCTURE
-------------------

```
bin/                 tools
docs/                documentation
src/                 source code
tests/               tests of this code
```

安装Install
-------------------
for nginx 
add to virtual host configure file
```
location /{
               index index.html index.htm index.php;
               if (-e $request_filename) {
                       break;
               }
               if (!-e $request_filename) {
                       rewrite ^/(.*)$ /index.php/$1 last;
                       break;
               }
       }
location ~ .+\.php($|/) {
   root           /data/gm;
   fastcgi_index index.php;
   fastcgi_split_path_info ^(.+\.php)(.*)$;
   fastcgi_param   SCRIPT_FILENAME $document_root$fastcgi_script_name;
   fastcgi_param   PATH_INFO               $fastcgi_path_info;
   fastcgi_param   PATH_TRANSLATED $document_root$fastcgi_path_info;
   fastcgi_pass   127.0.0.1:9000;
   include        fastcgi_params;
}
```

执行迁移脚本