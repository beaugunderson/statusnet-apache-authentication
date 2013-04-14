Apache Authentication
=====================

The Apache Authentication plugin makes StatusNet let Apache handle authentication.

It also retrieves user attributes from LDAP during auto-registration and because of this
depends on the LdapCommon plugin.

Please note that this plugin has not been tested for security vulnerabilities and should be
considered alpha quality at best (though it's being used in my office of 60 or so people without
issue). *Please set it up on a test instance first so that it doesn't affect your existing users.*

It probably relies too heavily on your environment being like mine; if you get it to work please
let me know what you had to change to do so and I'll incorporate those changes (or simply fork and
send me a pull request).

Installation
------------

Copy these files to plugins/ApacheAuthentication within your StatusNet installation.

Add a configuration stanza like

    "addPlugin('apacheAuthentication',
       array('setting'=>'value', 'setting2'=>'value2', ...);"

to the bottom of your config.php.

Settings
--------

*   **provider_name**: required, this is an identifier designated to the connection.

    It's how StatusNet will refer to the authentication source.
    For the most part, any name can be used, so long as each authentication source has a different identifier.
    In most cases there will be only one authentication source used.
*   **authoritative** (false): Set to true if LDAP's responses are authoritative
    (if authorative and LDAP fails, no other password checking will be done).
*   **autoregistration** (false): Set to true if users should be automatically created
    when they attempt to login.
*   **domain**: The domain to append to a user's username to create their email address
*   **email_changeable** (true): Are users allowed to change their email address?
    (true or false)
*   **password_changeable** (true): Are users allowed to change their passwords?
    (true or false)
*   **password_encoding**: required if users are to be able to change their passwords
    Possible values are: crypt, ext_des, md5crypt, blowfish, md5, sha, ssha,
    smd5, ad, clear
*   **host**: required, LDAP server name to connect to. You can provide several hosts in an
    array in which case the hosts are tried from left to right.
*   **port**: Port on the server.
*   **version**: LDAP version.
*   **starttls**: TLS is started after connecting.
*   **binddn**: The distinguished name to bind as (username).
*   **bindpw**: Password for the binddn.
*   **basedn**: required, LDAP base name (root directory).
*   **options**
*   **filter**: Default search filter.
*   **scope**: Default search scope.
*   **schema_cachefile**: File location to store ldap schema.
*   **schema_maxage**: TTL for cache file.
*   **attributes**: an array that relates StatusNet user attributes to LDAP ones

    *   **username**: required, LDAP attribute value entered when authenticating to StatusNet
    *   **nickname**: required, LDAP attribute value shown as the user's nickname
    *   **email**
    *   **fullname**
    *   **homepage**
    *   **location**
    *   **password**: required if users are to be able to change their passwords

Default values are in (parenthesis)

See the [Net_LDAP2 manual](http://pear.php.net/manual/en/package.networking.net-ldap2.connecting.php) for additional information about these options.

For most LDAP installations, the "nickname" and "username" attributes should
be the same.

Example
-------

Here's an example apache2 configuration snippet that specifies Kerberos authentication using
[Likewise Open](http://www.likewise.com/products/likewise_open/):

       <Directory /home/www/statusnet>
          # This uses mod_auth_kerb from Likewise
          AuthType Kerberos
          AuthName "Kerberos Login"

          KrbAuthRealms GLOBAL.LOC
          KrbAuthoritative on
          KrbMethodNegotiate on
          KrbMethodK5Passwd on
          KrbVerifyKDC off
          Krb5Keytab /etc/apache2/status.ktb

          Require valid-user
       </Directory>

Here's an example of an Apache Authentication plugin configuration that connects to
Microsoft Active Directory.

    addPlugin('apacheAuthentication', array(
        'provider_name'=>'Example',
        'authoritative'=>true,
        'autoregistration'=>true,
        'domain'=>'global.loc',
        'binddn'=>'username',
        'bindpw'=>'password',
        'basedn'=>'OU=Users,OU=StatusNet,OU=US,DC=americas,DC=global,DC=loc',
        'host'=>array('server1', 'server2'),
        'password_encoding'=>'ad',
        'attributes'=>array(
            'username'=>'sAMAccountName',
            'nickname'=>'sAMAccountName',
            'email'=>'mail',
            'fullname'=>'displayName',
            'password'=>'unicodePwd')
    ));

## FAQ

Q: I get the following (or similar) error

    Fatal error: Class User_username contains 1 abstract method and must therefore be 
    declared abstract or implement the remaining methods (Managed_DataObject::schemaDef)
    in /path/to/User_username.php on line 63

A: This has been [observed](http://status.net/open-source/issues/3299) previously and 
a [patch](https://gitorious.org/statusnet/mainline/merge_requests/202) submitted.
