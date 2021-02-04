# PDNS-Manager
Lightweight PowerDNS management frontend


### Disclaimer
1. Use at your own risk


### 2DO
1. DDNS
2. Cleanup
3. PDNS_Helper -> prepare() -> array 
 

#### Setup
1. Clone the project
2. Configure the web server
```
location /pdns/api2 {                                                                                                                                                         
    root /var/www/pdns.yourdns.com/pdns/api2;                                                                                                                                     
    try_files $uri /pdns/api2/index.php$is_args$args;
}
```
