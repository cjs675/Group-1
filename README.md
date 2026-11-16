# COP 3350 — Multi-Service RHEL 9 Infrastructure (`lab.test`)

This repository contains the configuration files, web application source code, lecture metadata index, and deployment runbook for our **COP 3350 (Systems Administration)** group project. The infrastructure deploys an authoritative BIND DNS service and a hardened, multi-tenant Apache (`httpd`) + PHP-FPM web tier across two Red Hat Enterprise Linux 9 (RHEL 9) virtual machines on an isolated `192.168.56.0/24` subnet.

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

| Hostname / VirtualHost | IP Address | Role & Services | Key Paths |
| :--- | :--- | :--- | :--- |
| **`rhel-server.lab.test`** (`ns1.lab.test`) | `192.168.56.10` | Primary Infrastructure Server (`named`, `httpd`, `php-fpm`, `sshd`, `firewalld`) | `/etc/named.conf`, `/var/named/`, `/etc/httpd/conf.d/` |
| **`info.lab.test`** | `192.168.56.10` | VirtualHost 1: Static Informational Portal + Restricted `/admin` Dashboard | `/var/www/info.lab.test/html`, `/etc/httpd/conf/.htpasswd` |
| **`app.lab.test`** | `192.168.56.10` | VirtualHost 2: Dynamic PHP-FPM COP 3350 Lecture Resource Hub | `/var/www/app.lab.test/html`, `/var/www/lecture-index/lectures.csv` |
| **`rhel-client.lab.test`** | `192.168.56.20` | Verification & Administration Client (`dig`, `nslookup`, `curl`, `firefox`, `ssh`) | Queries `192.168.56.10` exclusively for DNS |

---


