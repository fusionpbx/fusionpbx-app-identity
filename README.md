# fusionpbx-app-identity

Integrate Stir/Shaken identity sign and verify

## Install
```
cd /var/www/fusionpbx/app
git clone https://github.com/fusionpbx/fusionpbx-app-identity.git identity
cd /var/www/fusionpbx
php core/upgrade/upgrade.php --permissions
php core/upgrade/upgrade.php
```

## /etc/fusionpbx/certs

Make sure your certificate directory exists.
```
mkdir -p /etc/fusionpbx/certs
```
## 

Once you have obtained your official keys, move them into the /etc/fusionpbx/certs directory.
By default the private key should be called `identity_private.key` and the certificate_name should be `identity_certificate.pem`

## /etc/fusionpbx/config.conf

The allowed_cidr allows multiple comma delimitted addresses. Remember to add /32 or other notation.
The certificate_url is used by the server receiving the call to verify the identity.

```
#identity - Stir/Shaken
identity.path = '/etc/fusionpbx/certs'
identity.private_key_name = 'identity_private.key'
identity.certificate_name = 'identity_certificate.pem'
identity.certificate_url = 'https://certs.urlto.local/public_key.pem'
identity.allowed_cidr = '127.0.0.1/32'
identity.debug = false
```

## Curl Command Line

The `source` is your Outbound Caller ID Number `destination` is the number being called. Make sure to update these numbers.
The numbers should include the country code the numbers must be e.164 format though the + is optional.
```
curl -X POST \
     -d "source=18005551212" \
     -d "destination=18005551313" \
     http://127.0.0.1/app/identity/sign.php
```

## Outbound Routes

Add the following to your outbound route to sign outbound calls.
```
<action application="unset" data="sip_h_Identity"/>
<action application="curl" data="http://127.0.0.1/app/identity/sign.php POST source=${effective_caller_id_number}&destination=1$1"/>
<action application="export" data="exclude_outgoing_extra_header=sip_h_Identity"/>
<action application="set" data="sip_h_Identity=${curl_response_data}"/>
```
