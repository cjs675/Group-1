# COP 3350 — RHEL 9 - Web & DNS Infrastructure (`lab.test`)

This repository contains the configuration files, web application source code, lecture metadata index, and deployment runbook for our **COP 3350 (Systems Administration)** group project. The infrastructure deploys an authoritative BIND DNS service and a hardened, multi-tenant Apache (`httpd`) + PHP-FPM web tier across two Red Hat Enterprise Linux 9 (RHEL 9) virtual machines on an isolated `192.168.56.0/24` subnet.

---

## Architecture Overview

| Hostname | Role | Private IP (`enp0s2` / `private-net`) | Active Services |
| :--- | :--- | :--- | :--- |
| **`rhel-server.lab.test`** | Authoritative DNS & Web/App Server | `192.168.56.10` | `named`, `httpd`, `php-fpm`, `firewalld` |
| **`rhel-client.lab.test`** | Test Client & Verification Workstation | `192.168.56.20` | `NetworkManager`, `bind-utils`, `curl`, `firefox` |

### Hosted Services & Endpoints
* **Authoritative DNS (`lab.test` & `56.168.192.in-addr.arpa`):** Resolves forward (`A`) and reverse (`PTR`) records across `192.168.56.0/24` with recursion restricted to `localhost` and `192.168.56.0/24`.
* **Static Portal (`https://info.lab.test/`):** Serves course overview content over TLS with HTTP (`:80`) permanently redirecting (`301`) to HTTPS (`:443`) and HTTP Basic Authentication protecting `/admin/`.
* **Dynamic Resource Hub (`https://app.lab.test/`):** Executes PHP via `php-fpm` to query `/var/www/lecture-index/lectures.csv` and serve searchable lecture metadata and downloadable PDF slides (`/var/www/app.lab.test/html/slides/L01.pdf` – `L21.pdf`)[cite: 5].

---

## Repository Structure

```text
Group-1/
├── apache/                     # Apache VirtualHost & global ServerName configs
│   ├── info.lab.test.conf
│   ├── app.lab.test.conf
│   └── servername.conf
├── dns-setup/                  # Authoritative BIND 9 configuration & zone files
│   ├── named.conf
│   ├── Readme.txt
│   └── named/
│       ├── coursehub-zones.conf
│       ├── lab.test.zone
│       └── 56.168.192.in-addr.arpa.zone
├── sites/                      # Web root assets and CSV metadata
│   ├── info.lab.test/
│   └── app.lab.test/
└── README.md

---

## DNS Zone Configuration (`lab.test`)

* **Forward Zone (`/var/named/lab.test.zone`):** Defines `NS` record `rhel-server.lab.test.` and `A` records mapping `rhel-server`, `info`, `app`, `resources`, and `admin` to `192.168.56.10`, plus `rhel-client` to `192.168.56.20`.
* **Reverse Zone (`/var/named/56.168.192.in-addr.arpa.zone`):** Defines `PTR` records mapping host octet `10` to `rhel-server.lab.test.` and octet `20` to `rhel-client.lab.test.`.
* **Zone Include (`/etc/named/coursehub-zones.conf`):** Declares master zones for `lab.test` and `56.168.192.in-addr.arpa` with `allow-update { none; };`, included by `/etc/named.conf`.

---

## 1. Network Architecture & Host Overview

```mermaid
%%{init: {
  'theme': 'base',
  'themeVariables': {
    'primaryColor': '#FFFFFF',
    'primaryTextColor': '#0F172A',
    'primaryBorderColor': '#475569',
    'lineColor': '#334155',
    'edgeLabelBackground': '#F1F5F9',
    'fontFamily': 'Inter, Arial, sans-serif'
  }
}}%%
flowchart LR
    subgraph Subnet["Private Lab Network: 192.168.56.0/24 (Domain: lab.test)"]
        direction LR

        subgraph Client["rhel-client.lab.test (192.168.56.20) — RHEL 9"]
            C1["Firefox Browser<br/>HTTPS Sites & /admin Auth"]
            C2["CLI Tools (dig, nslookup, curl)<br/>DNS & HTTP Status Validation"]
            C3["SSH Client <br/>Non-Root Remote Admin"]
        end

        subgraph Server["rhel-server.lab.test (192.168.56.10) — RHEL 9 | SELinux Enforcing"]
            FW["firewalld Allowed: ssh (22), dns (53), http (80), https (443)"]
            DNS["BIND DNS (named)<br/>Forward: lab.test (A) | Reverse: 56.168.192.in-addr.arpa (PTR)"]
            WEB1["VirtualHost 1: info.lab.test<br/>Static Portal + /admin (.htpasswd Basic Auth)"]
            WEB2["VirtualHost 2: app.lab.test<br/>Dynamic PHP-FPM Hub + /var/www/lecture-index"]
            SEC["TLS Certs (443) + HTTP->HTTPS 301 Redirects (80)<br/>Backups: /var/backups/web-project/*.tar.gz"]
        end

        C2 <-->|"1. DNS Queries (UDP/TCP 53)"| DNS
        C3 <-->|"2. SSH Admin (TCP 22)"| FW
        C1 <-->|"3a. HTTP (80->301) & HTTPS (443 TLS)"| WEB1
        C1 <-->|"3b. HTTPS PHP Search (443 TLS)"| WEB2
    end

    style Subnet fill:#F8FAFC,stroke:#94A3B8,stroke-width:1.5px,stroke-dasharray: 5 5,color:#1E293B
    style Client fill:#EFF6FF,stroke:#3B82F6,stroke-width:1.5px,color:#0F172A
    style Server fill:#F1F5F9,stroke:#64748B,stroke-width:1.5px,color:#0F172A

    style C1 fill:#FFFFFF,stroke:#3B82F6,stroke-width:1.2px,color:#0F172A
    style C2 fill:#FFFFFF,stroke:#3B82F6,stroke-width:1.2px,color:#0F172A
    style C3 fill:#FFFFFF,stroke:#3B82F6,stroke-width:1.2px,color:#0F172A
    style DNS fill:#FFFFFF,stroke:#475569,stroke-width:1.2px,color:#0F172A
    style FW fill:#FFFFFF,stroke:#475569,stroke-width:1.2px,color:#0F172A
    style WEB1 fill:#FFFFFF,stroke:#475569,stroke-width:1.2px,color:#0F172A
    style WEB2 fill:#FFFFFF,stroke:#475569,stroke-width:1.2px,color:#0F172A
    style SEC fill:#E2E8F0,stroke:#475569,stroke-width:1.2px,color:#0F172A
``` 


| Hostname / VirtualHost | IP Address | Role & Services | Key Paths |
| :--- | :--- | :--- | :--- |
| **`rhel-server.lab.test`** (`ns1.lab.test`) | `192.168.56.10` | Primary Infrastructure Server (`named`, `httpd`, `php-fpm`, `sshd`, `firewalld`) | `/etc/named.conf`, `/var/named/`, `/etc/httpd/conf.d/` |
| **`info.lab.test`** | `192.168.56.10` | VirtualHost 1: Static Informational Portal + Restricted `/admin` Dashboard | `/var/www/info.lab.test/html`, `/etc/httpd/conf/.htpasswd` |
| **`app.lab.test`** | `192.168.56.10` | VirtualHost 2: Dynamic PHP-FPM COP 3350 Lecture Resource Hub | `/var/www/app.lab.test/html`, `/var/www/lecture-index/lectures.csv` |
| **`rhel-client.lab.test`** | `192.168.56.20` | Verification & Administration Client (`dig`, `nslookup`, `curl`, `firefox`, `ssh`) | Queries `192.168.56.10` exclusively for DNS |

---


## 2. Key Technical Decisions

* **Dual-NIC Network Isolation:** Each VM uses a NAT adapter (`enp0s1`) strictly for `dnf` package installation and a Host-Only/Internal adapter (`enp0s2`) configured on `192.168.56.0/24` for all lab service traffic.
* **Authoritative Split-Zone DNS (BIND):** Instead of relying on static `/etc/hosts` overrides on the client, `rhel-server` runs BIND (`named`) with both a forward zone (`lab.test`) and reverse zone (`56.168.192.in-addr.arpa`) so all hostnames and VirtualHosts resolve dynamically over port 53.
* **Name-Based VirtualHosting & Mandatory TLS:** Both `info.lab.test` and `app.lab.test` share `192.168.56.10`. Port `80` VirtualHosts issue strict `301 Moved Permanently` redirects to port `443` (`mod_ssl`), preventing plaintext exposure of HTTP Basic Auth credentials on `/admin`.
* **Out-of-Root Lecture Metadata Index:** The CSV catalog (`/var/www/lecture-index/lectures.csv`) used by `app.lab.test` is stored outside the public `DocumentRoot` (`/var/www/app.lab.test/html`) so users can search metadata via PHP without directly downloading the raw database file.
* **SELinux Enforcing & Least-Privilege Permissions:** SELinux remains in `Enforcing` mode at all times. Custom directories use persistent `httpd_sys_content_t` file contexts (`semanage fcontext` + `restorecon`), directories require `755` (`drwxr-xr-x`) traversal permissions so PHP `is_file()` checks succeed on `/slides`, and regular files use `644` (`-rw-r--r--`).

--- 


## 3. Required Packages & Tools

Before deploying the configurations on **`rhel-server`** (`192.168.56.10`), install the required server daemons and SELinux management utilities:

```bash
sudo dnf install -y httpd mod_ssl php php-fpm php-cli \
    bind bind-utils firewalld policycoreutils-python-utils tar curl
```

On **`rhel-client`** (`192.168.56.20`), ensure validation tools are installed:

```bash
sudo dnf install -y bind-utils curl firefox openssh-clients
```

--- 


## 4. Server Setup & Deployment `rhel-server`

If you are pulling this repository onto a fresh VM to replicate or test the environment, run the steps below from the root of the `Group-1` repository.

### Step 1: Enable Core Services & Configure `firewalld`
```bash
sudo systemctl enable --now firewalld httpd php-fpm sshd

sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --permanent --add-service=dns
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --reload
sudo firewall-cmd --list-all
```

### Step 2: Create Web Directories & Copy Site Assets
```bash
sudo mkdir -p /var/www/info.lab.test/html/admin
sudo mkdir -p /var/www/app.lab.test/html/slides
sudo mkdir -p /var/www/lecture-index

# Copy static portal, dynamic PHP hub, and lecture index from repo
sudo cp -r info.lab.test/* /var/www/info.lab.test/html/
sudo cp -r app.lab.test/* /var/www/app.lab.test/html/
sudo cp lecture-index/lectures.csv /var/www/lecture-index/
``` 

### Step 3: Generate TLS Certificates & Basic Auth Credentials
```bash
# Generate self-signed certificates for info.lab.test and app.lab.test
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/pki/tls/private/info.lab.test.key \
    -out /etc/pki/tls/certs/info.lab.test.crt \
    -subj "/C=US/ST=FL/L=FortMyers/O=FGCU/OU=COP3350/CN=info.lab.test"

sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/pki/tls/private/app.lab.test.key \
    -out /etc/pki/tls/certs/app.lab.test.crt \
    -subj "/C=US/ST=FL/L=FortMyers/O=FGCU/OU=COP3350/CN=app.lab.test"

# Create HTTP Basic Auth password file for https://info.lab.test/admin
sudo htpasswd -c /etc/httpd/conf/.htpasswd admin
sudo chown root:apache /etc/httpd/conf/.htpasswd
sudo chmod 640 /etc/httpd/conf/.htpasswd
```

### Step 4: Deploy Apache VirtualHost Configs
```bash
sudo cp apache/*.conf /etc/httpd/conf.d/
echo "ServerName rhel-server.lab.test" | sudo tee /etc/httpd/conf.d/servername.conf
```

### Step 5: Enforce Ownership, Permissions & SELinux Contexts
> **Important:** Directories (`html`, `slides`, `admin`, `lecture-index`) **must** keep `755` (`+x`) permissions so Apache and PHP-FPM can traverse them and stat PDF files.

```bash
sudo chown -R apache:apache /var/www/info.lab.test /var/www/app.lab.test /var/www/lecture-index

sudo find /var/www/info.lab.test /var/www/app.lab.test /var/www/lecture-index -type d -exec chmod 755 {} \;
sudo find /var/www/info.lab.test /var/www/app.lab.test /var/www/lecture-index -type f -exec chmod 644 {} \;

sudo semanage fcontext -a -t httpd_sys_content_t "/var/www/info\.lab\.test(/.*)?"
sudo semanage fcontext -a -t httpd_sys_content_t "/var/www/app\.lab\.test(/.*)?"
sudo semanage fcontext -a -t httpd_sys_content_t "/var/www/lecture-index(/.*)?"
sudo restorecon -Rv /var/www/info.lab.test /var/www/app.lab.test /var/www/lecture-index

sudo httpd -t && sudo systemctl restart php-fpm httpd 
```

## 5. Verification Commands (`rhel-client`)

Run these checks from **`rhel-client`** (`192.168.56.20`) (or locally on `rhel-server` by replacing `192.168.56.10` with `127.0.0.1` in `--resolve` before DNS is live):

```bash
# 1. Verify DNS forward & reverse resolution (once BIND is active)
dig @192.168.56.10 info.lab.test +short
dig @192.168.56.10 app.lab.test +short
dig @192.168.56.10 -x 192.168.56.10 +short

# 2. Verify Port 80 -> 443 (301 Moved Permanently) Redirects
curl -I --resolve info.lab.test:80:192.168.56.10 http://info.lab.test/
curl -I --resolve app.lab.test:80:192.168.56.10 http://app.lab.test/

# 3. Verify Unauthorized (401) vs. Authorized (200) Access on /admin
curl -k -I --resolve info.lab.test:443:192.168.56.10 https://info.lab.test/admin/
curl -k -I -u admin --resolve info.lab.test:443:192.168.56.10 https://info.lab.test/admin/

# 4. Verify Dynamic PHP Search on app.lab.test
curl -k --resolve app.lab.test:443:192.168.56.10 "https://app.lab.test/?search=Linux" | grep "Available Lectures"
```

--- 


## 6. Backup & Disaster Recovery Procedure

We maintain compressed, permission-preserving (`-p`) archives of all web roots, lecture metadata, and Apache configurations under `/var/backups/web-project/`.

### Create a Full Backup Archive
```bash
sudo mkdir -p /var/backups/web-project

sudo tar -czpvf /var/backups/web-project/web-backup-$(date +%F).tar.gz \
    /var/www/info.lab.test \
    /var/www/app.lab.test \
    /var/www/lecture-index \
    /etc/httpd/conf.d/
```

### Restore from Backup & Re-Verify SELinux Labels
```bash
sudo tar -xzpvf /var/backups/web-project/web-backup-*.tar.gz -C /
sudo restorecon -Rv /etc/httpd/conf.d /var/www/info.lab.test /var/www/app.lab.test /var/www/lecture-index
sudo httpd -t && sudo systemctl reload httpd
```

---
