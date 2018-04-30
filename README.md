# DSB-Untis2Telegram

[![GitHub license](https://img.shields.io/github/license/wasserbombe/DSB-Untis2Telegram.svg)](https://github.com/wasserbombe/DSB-Untis2Telegram/blob/master/LICENSE)
![GitHub last commit](https://img.shields.io/github/last-commit/wasserbombe/DSB-Untis2Telegram.svg)
![GitHub code size in bytes](https://img.shields.io/github/languages/code-size/wasserbombe/DSB-Untis2Telegram.svg)
![GitHub top language](https://img.shields.io/github/languages/top/wasserbombe/DSB-Untis2Telegram.svg)

This is a collection of simple scripts to get data of substitute plans that are using "Digitales Schwarzes Brett" and "Untis" - and to push alerts to a Telegram chat. 

**Please note: If you are using these scripts, it's on your own risk. There is no guarantee that this really works and possibly you need some permission by the used services (such as "Digitales Schwarzes Brett") before you are allowed to access/use these services this way!**

## config.php
Both scripts ([getData.php](../master/getData.php) and [alert.php](../master/alert.php)) are based on a single config.php which should be created by renaming the [config.sample.php](../master/config.sample.php). Here is an overview what parts are currently configured there. 

### Database Connection
A database is used to store the data. The database connection and their credentials are defined as a simple array. As this project uses [Interdose/Dominik Deobald's DB class](https://github.com/Interdose/DB), you can find more information [there](https://github.com/Interdose/DB/blob/master/README.md). 
Tip: I've added [database_structure.sql](../master/database_structure.sql) to the repository. You can use it to initialize your database. 

### Telegram
To be able to push updates to Telegram chats, you must provide a Telegram Bot Token in this section of the config file. You can find more information about Telegram Bots and their API [here](https://core.telegram.org/bots/api). 

### Alerts
In this section you can configure the classes and subjects the scripts should observe for your Telegram updates. Please note, that the current implementation supports only **one class** (which should not be a problem, because one users typically visits just one class ;-)). 
```php
'alerts' => array(
  array(
    // id of the telegram chat (bot must be added to this chat!); single user chats and groups are possible. 
    'chat_id' => 12345678,
    'name' => 'Name this chat to identify it later',
    // classes to search for
    'classes' => array("k1"),
    // subjects to search for 
    'subjects' => array("bio1","2rrk1","rrk1","rk","4ph1","ph1","m2","4m2","d2","4d2","2mu1","mu1","4f1","f","4e2","e2","2g2","g2","2psy1","psy1","2s1","s1","sp1","gk2","sf1","2b1","b1","2geo3","3s")
  ),
  // ...
)
```

## Running scripts periodically
If you have configured and tested your implementation and want to automatically run these scripts, here is an example for your ```/etc/crontab```:
```bash
# Refresh Data every 10 min
10 * * * *      www-data    php7.0 /path/to/getData.php
# Good Morning message on weekdays
15 7 * * 1-5    www-data    php7.0 /path/to/alert.php --mode="morning"
# Good Evening / next day message So-Do
00 19 * * 0-4   www-data    php7.0 /path/to/alert.php --mode="evening"
# Alert on updates
*/2 6-17 * * 1-5        www-data    php7.0 /path/to/alert.php
```
Of course, you can also run the script manually with these commands!
