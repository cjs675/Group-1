Host machine setup:
make sure that bind dns is installed 
	if not run dnf install -y bind bind-utils

Config and zones:
	Files ending with .zone go into the directory /var/named
	coursehub-zones.conf goes into /etc/named/
	replace existing named.conf in /etc with the one within (configures bind to allow DNS queries from the private network and creation of zones)

Permissions:
	make both zone files are owned by root and have 640 permissions
		chown root:named /var/named/[zone names]
		chmod 640 /var/named/[zone names]

Firewall configuration
	add the following rich rule
		firewall-cmd --permanent --zone=public --add-rich-rule='rule family="ipv4" source address="192.168.56.0/24" service name="dns" accept'
	reload and verify
		firewall-cmd --reload
		firewall-cmd --zone=public --list-rich-rules

Start DNS Service
	systemctl enable --now named

Verify
	systemctl status named --no-pager
	named-checkconf -z
	named-checkconf lab.test /var/named/lab.test.zone
	named-checkconf 56.168.192.in-addr.arpa /var/named/56.168.192.in-addr.arpa.zone
		(the zone checks should output OK)

Client setup:
	nmcli connection modify "private-net" ipv4.dns "192.168.56.10" ipv4.dns-search "lab.test" ipv4.dns-priority 10
	nmcli connection up "private-net"

Client checks:
	dig resources.lab.test +short
	dig admin.lab.test +short
	dig -x 192.168.56.10 +short
	getent hosts redhat.com
