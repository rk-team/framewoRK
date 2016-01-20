### Welcome to rk

This project is an early alpha developpement version of what aims to be a PHP + JS framework.

Tutorial (outdated and only available in french at the moment) is here : http://rk-team.github.io/framewoRK/


### Requirements

- apache2 with rewrite mode
- php 5.5+
- optionnal : java 1.4+ (for yui-compressor minifier)
 

### Installation

Let's say you want to install rk in */home/www/framewoRK*, and access it through *http://rkf.rk*

**Virtual Host**

Add this to your available sites (eg: /etc/apache2/sites-available/rkf.rk or /etc/apache2/sites-available/rkf.rk.conf)
```
<VirtualHost *:80>
	ServerName rkf.rk
	ServerAlias www.rkf.rk

	DocumentRoot /home/www/framewoRK/web/
	<Directory />
		Options +ExecCGI
		AllowOverride All
	</Directory>

	
	Alias /rk /home/www/framewoRK/rk/web
	<Directory "/home/www/framewoRK/rk/web">
		AllowOverride All
		Allow from All
	</Directory>

</VirtualHost>
```
Enable the site :
```
sudo a2ensite rkf.rk
sudo service apache2 reload
```

**Rights management**

Replace *www-data* by your apache user if necessary
```
cd /home/www/framewoRK
sudo chown -R :www-data .
sudo chmod -R 775 log cache
```

*You're now ready to go !*


### Contribution

When you clone framewoRK.git, you have to run the following command to get rk folder ready to use
```
git submodule update --init
```

You can then work in rk subFolder as it is a standard git repository and push your contribution on you feature branch

