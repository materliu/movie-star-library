# 本地准备工作
项目使用mongo数据库，需要在本地创建对应数据库， 这里我的操作如下：

```
mongo

use admin;

db.createUser({ user: 'root', pwd: '*******', roles: [ { role: "root", db: "admin" } ] });

exit;

mongo -u root -p **** admin;

use movie-star;

db.createUser(
  {
    user: "movie",
    pwd: "****",
    roles: [
      { role: "readWrite", db: "movie-star" }
    ]
  }
);

exit;
```

# 环境准备，除PHP环境外， 要安装mongo和zip的PHP扩展

# 项目run起来， 后边的工作跟跑一个laravel项目无异了， copy .env.example .env 配置相关配置项， 这里连上我们的mongo数据库

```
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=movie-star
DB_USERNAME=movie
DB_PASSWORD=****
```

# nginx 配置
```
```
