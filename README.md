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
 

#### Setup
1. Clone the project
2. Configure the web server (NGINX snippet)
```
location /pdns/api2 {                                                                                                                                                         
    root /var/www/pdns.yourdns.com/pdns/api2;                                                                                                                                     
    try_files $uri /pdns/api2/index.php$is_args$args;
}
```
3. Fetch dependencies using composer (check composer.json to see what is needed)
4. Navigate to https://pdns.yourdns.com -> you should see the login prompt and be able to "sign up"
5. Save the received credentials to the '-sample.php' files and rename them by removing '-sample'
6. Enjoy...
