# PDNS-Manager
Lightweight PowerDNS management frontend


### Disclaimer
1. Use at your own risk
2. No input data has been validated (apart from adding . (dot) at the end of zones/records), so be careful
3. If unsure -> add https + basic auth in front of PDNS Manager directory


### 2DO
1. DDNS
2. Cleanup
3. PDNS_Helper -> prepare() -> array 

### High level logic overview
![High level logic overview](https://raw.githubusercontent.com/vbeskrovny/PDNS-Manager/main/PDNS_Manager_HL_Overview.png)


### Screenshots
![Sign in window](https://github.com/vbeskrovny/PDNS-Manager/blob/main/PDNS_Manager_login_window.png?raw=true)
![Sign up window](https://github.com/vbeskrovny/PDNS-Manager/blob/main/PDNS_Manager_signup_window.png?raw=true)
![Records editing window](https://github.com/vbeskrovny/PDNS-Manager/blob/main/PDNS_Manager_records_window.png?raw=true)


### Setup
1. Clone the project
2. Configure the web server (NGINX snippet)
```
location /pdns/api2 {
    root /var/www/pdns.yourdns.com/pdns/api2;                                                                                                                         
    try_files $uri /pdns/api2/index.php$is_args$args;
}

location / {               
    try_files $uri $uri/ =404;
}
```
3. Fetch dependencies using composer (check composer.json to see what is needed)
4. Navigate to https://pdns.yourdns.com -> you should see the login prompt and be able to "sign up"
5. Save the received credentials to the '-sample.php' files and rename them by removing '-sample'
6. Enjoy...
